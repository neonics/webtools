# (C) 2011 Kenney Westerhof <kenney@neonics.com>
#
#
# Simple database system for Perl.
#
# Uses AnyDBM_File (i.e. BDB etc..).
#
# Creates one database (a simple key/value pair table) for metadata,
# currently simply the table names and the database filenames,
#
# and one database (again a simple key/value hash) per table.
# The real database tables store indexed hashes:  $key => { hash }.
#
#
# Usage:
#
# tie %db, 'DB', $dbprefix;  # database tables will be $dbprefix-tablename
#
#
# $db{tablename}{key} = { };
#
# %user = $db{users}{username};
# $password = $db{users}{username}{password};

package DB;

use diagnostics;
use strict;
use warnings;
use Carp;
use POSIX;
use AnyDBM_File;
use DB_File;
use Fcntl ':flock';

use DataCoder;

my $dbformat = 'AnyDBM_File';

# This is a hash for hashes.
# It uses the key as the table name, and the value is the hash
# that is tied to the table on disk.

sub debug
{
	my ($msg)=@_;
#	print " o DB: $msg\n";
}

sub TIEHASH
{
	my ($classname, $dbfilename, @args) = @_;

	croak "DB filename not defined" unless defined $dbfilename;

	my ($dbpath, undef, $dbfileprefix) = $dbfilename =~ /^((.*?)\/)?([^\/]+)$/;
	$dbpath = $dbpath || ".";

	debug "DB Path: $dbpath; File Prefix: $dbfileprefix";

	-d $dbpath || mkdir ($dbpath) or die "Cannot create db path '$dbpath': $!";

	debug "Opening lockfile";
	sysopen my $lockFile, $dbfilename.".lock", O_WRONLY | O_CREAT
		or die "Cannot open lockfile: $!";
	debug "Lock file opened";

	my $self = bless {
		lockFile => $lockFile,
		autocreate => 0,
		clobber => 0, # enable to delete tables
		dbpath => $dbpath,
		dbfileprefix => $dbfileprefix,
		dbfilename => $dbfilename,
		dbties => {}
	}, $classname;

	$self->openDB;

	return $self;
}

sub lock
{
	my ($self, $flags) = @_;

	my %h = ( 2 => 'ex', 8 => 'un' );
	debug "lock " . $h{$flags};
	unless (flock $self->{lockFile}, $flags | LOCK_NB )
	{
		debug "Waiting for lock..";
		flock( $self->{lockFile}, $flags )
			or die "Lock failed: $!";
		debug " ok.";
	}
}

sub createTable
{
	my ( $self, $key ) = @_;

	if ( $self->{metadb}{$key} )
	{
		debug "Table '$key' already created";
	}
	else
	{
		my $cleankey = $key;
		$cleankey =~ s/[^a-zA-Z]/_/g;
		# check if the cleankey is unique
		if ( defined $self->{metadb}{$cleankey} )
		{
			#not unique.
			$cleankey .= 1 + scalar %{ $self->{metadb} };
		}

		$self->{metadb}{$key} = $self->{dbfileprefix}."-".$cleankey;

		debug "Creating table " . $self->{metadb}{$key};
	}
}

sub dumpMeta
{
	my ($self) = @_;

	debug "Stored metadata for tables: ";
	map {
		debug "  $_ : $self->{metadb}{$_}";
	} keys %{ $self->{metadb} };
}

sub openDB
{
	my ($self) = @_;

	$self->lock( LOCK_EX );

	debug "Opening db $self->{dbfilename}";

	tie my %metadb, $dbformat, $self->{dbfilename}, O_CREAT|O_RDWR, 0600
		or die "Cannot tie: $!";

	debug "Tied metadb '$self->{dbfilename}': ".\%metadb;

	$self->{metadb} = \%metadb;

	map {
		debug "META: $_ : $self->{metadb}{$_}";
	} keys %{ $self->{metadb} };
}

sub closeDB
{	
	my $self = shift;

	do
	{
		untie %{ $self->{metadb} };
		delete $self->{metadb};
	} if defined $self->{metadb};

	$self->lock( LOCK_UN );
}

sub openTable
{
	my ($self, $key) = @_;


	if ( ! defined $self->{dbties}{$key} )
	{
		debug "dbtie $key not found";
		if ( ! defined $self->{metadb}{$key} )
		{
			debug "metadb $key not found, creating table";
			$self->createTable( $key );
		}
		else
		{
			debug "metadb contains $key";
		}

		my %tie;
		tie %tie, 'DBTable', $self->{dbpath}."/".$self->{metadb}{$key}, $key
			or die "Cannot tie: $!";
		$self->{dbties}{$key} = \%tie;

		debug "Opening table $key: ".$self->{metadb}{$key}.": ".\%tie;
	}
	else
	{
		# already opened
	}


	return $self->{dbties}{$key};
}

sub closeTable
{
	my ($self, $key) = @_;

	if ( defined $self->{dbties}{$key} )
	{
#		my %t = %{ $self->{dbties}{$key} };
#		debug "Closing table $key: " . \%t;
#		map {
#			debug "  data: $_ = ".$t{$_};
#		} keys %t;
		untie %{ $self->{dbties}{$key} };
		delete $self->{dbties}{$key};
	}
}


sub deleteTable
{
	my ($self, $key) = @_;

	$self->closeTable( $key );

	if ( $self->{metadb}{$key} )
	{
		if ( $self->{clobber} )
		{
			debug "Deleting table ".$self->{metadb}{$key};
			unlink $self->{dbpath}."/".$self->{metadb}{$key};
		}
	}
}

sub FETCH
{
	my ($self, $key) = @_;

	debug "Fetch table: $key";

	$self->openTable($key);
	my $ret= \%{ $self->{dbties}{$key} };

	return $ret;
}


sub STORE
{
	my ($self, $key, $value) = @_;

	debug "Store: $key = ".$value;

	if ( $self->openTable($key) )
	{
		my $t = \%{ $self->{dbties}{$key} };
		debug "DBTie for $key = ". $t . "  " . $self->{dbties}{$key} ;

		# $$t{$key} = $value;
		map {
			$$t{$_} = ${ $value }{$_};
			debug "  store $_ = ${ $value }{$_}";
		} keys %{ $value };
	}
	else
	{
		debug "Table not opened";
	}
}

sub DELETE
{
	my ($self, $key) = @_;

	debug "Delete $key";
	$self->deleteTable( $key );
}

sub CLEAR
{
	my $self = shift;

	croak "Won't delete all tables"
		unless $self->{CLOBBER};

	foreach my $key ( keys %{$self->{dbties}} )
	{
		$self->DELETE( $key );
	}
}

sub EXISTS
{
	my ($self, $key ) = @_;

	debug "Exists: $key";

	return defined $self->{metadb}{$key};
}

sub FIRSTKEY
{
	my $self = shift;

	debug "Firstkey";
	# reset each() iteration

	my $a = scalar keys %{ $self->{metadb} };

	each %{ $self->{metadb} };
}

sub NEXTKEY
{
	my ($self, $lastkey) = @_;

	debug "Nextkey last=$lastkey";
	each %{ $self->{metadb} };
}

sub SCALAR
{
	my $self = shift;

	carp "Scalar $self->{metadb}";

	scalar %{ $self->{metadb} };
}

sub UNTIE
{
	my ($self, $refcount) = @_;

	carp "Untie attempted while $refcount inner references still exist"
		if $refcount;

	debug "Untie";

	for my $k ( keys %{ $self->{dbties} } )
	{
		$self->closeTable( $k );
	}

	$self->closeDB;

}

sub DESTROY
{
	my $self = shift;
	debug "Destroy";
	# no need..
	$self->UNTIE;

	close $self->{lockFile} if ( defined $self->{lockFile} );
}


sub new
{
	my $this = shift;
	my $class = ref($this) || $this;

	my %tie;
	tie %tie, $class;

	my $self = {
		db => \%tie,
		};

	bless $self, $class;
	$self->constructor( @_ );
	return $self;
}

package DBTable;

use diagnostics;
use strict;
use Carp;
use POSIX;
use AnyDBM_File;


# Tie proxu for DB files.
#
# Custom load/store; the rest is deferred.

sub debug
{
	my ($msg)=@_;
##	print " o DBTable: $msg\n";
}

sub TIEHASH
{
	my ($classname, $dbfilename, $table, @args) = @_;

	croak "DB filename not defined" unless defined $dbfilename;

	my %tie;
	tie %tie, $dbformat, $dbfilename, O_CREAT|O_RDWR, 0600;

	debug "Tied metadb: $dbfilename: ".\%tie;

	return bless {
		table => $table,
		tie => \%tie
	}, $classname;
}

sub FETCH
{
	my ($self, $key) = @_;

	debug "Fetch: $key: $self->{tie}";

	if ( defined $self->{tie}{$key} )
	{
		if ( $self->{tiedhashes}{$key} )
		{
			return $self->{tiedhashes}{$key};
		}



		my $h = DataCoder::decode ($self->{tie}{$key});

		# now we have a decoded hash, but changes to it
		# won't work, unless it becomes blessed..

		my %tie;

		my $coderef = sub
		{
			my ($upd) = @_;

			#carp "Hash changed, recoding & storing";
			$self->STORE( $key, $upd );
		};
		tie %tie, 'TiedHash', $h, $coderef;

		$self->{tiedhashes}{$key} = \%tie;

		return \%tie;
	}
	return undef;
}


sub STORE
{
	my ($self, $key, $value) = @_;

	# assuming value is a hashmap.
	# let's encode it.

	$self->{tie}{$key} = DataCoder::encode( $value );

	debug "Store $key = $self->{tie}{$key}";
}

sub DELETE
{
	my ($self, $key) = @_;

	debug "Delete $key";
	untie $self->{tiedhashes}{$key};
	delete $self->{tiedhashes}{$key};
	delete $self->{tie}{$key};
}

sub CLEAR
{
	my $self = shift;

	croak "Won't delete all entries"
		unless $self->{CLOBBER};
	map
	{
		delete $self->{tie}{$_};
	} keys %{ $self->{tie} };
}

sub EXISTS
{
	my ($self, $key ) = @_;

	debug "Exists: $key";

	return defined $self->{tie}{$key};
}

sub FIRSTKEY
{
	my $self = shift;

	debug "Firstkey: $self, $self->{tie}";
	my $a = keys %{ $self->{tie} };

	each %{ $self->{tie} };
}

sub NEXTKEY
{
	my ($self, $lastkey) = @_;

	debug "Nextkey last=$lastkey";
	each %{ $self->{tie} };
}

sub SCALAR
{
	my $self = shift;
	return scalar %{ $self->{tie} };
}

sub UNTIE
{
	my ($self, $refcount) = @_;

	#confess "Untie attempted while $refcount inner references still exist"
	#	if $refcount;
	debug("Untie table ".$self->{table});
	if ( $self->{tie} )
	{
		untie %{ $self->{tie} };
		delete $self->{tie};
	}
}

sub DESTROY
{
	my $self = shift;
	# no need..
	$self->UNTIE;
}

#################

package TiedHash;

use diagnostics;
use Carp;

sub debug
{
	my ($msg)=@_;
#	print $msg."\n";
}


sub TIEHASH
{
	my ($classname, $hashref, $updateSub, @args) = @_;
	return bless {
		update => $updateSub,
		tie => $hashref
	}, $classname;
}

sub FETCH
{
	my ($self, $key) = @_;

	return $self->{tie}{$key};
}


sub STORE
{
	my ($self, $key, $value) = @_;

	$self->{tie}{$key} = $value;
	$self->{update}->( $self->{tie} );
}

sub DELETE
{
	my ($self, $key) = @_;
	$self->{tie}->DELETE($key);
	&$self->{update}( $self->{tie} );
}

sub CLEAR
{
	my $self = shift;

	map
	{
		delete $self->{tie}{$_};
	} keys %{ $self->{tie} };
	&$self->{update}( $self->{tie} );
}

sub EXISTS
{
	my ($self, $key ) = @_;
	return defined $self->{tie}{$key};
}

sub FIRSTKEY
{
	my $self = shift;
	my $a = keys %{ $self->{tie} };
	each %{ $self->{tie} };
}

sub NEXTKEY
{
	my ($self, $lastkey) = @_;
	return each %{ $self->{tie} };
}

sub SCALAR
{
	my $self = shift;
	return scalar %{ $self->{tie} };
}

sub UNTIE
{
	my ($self, $refcount) = @_;

	carp "Untie attempted while $refcount inner references still exist"
		if $refcount;
	#debug("Untie ".$self);
	if ( $self->{tie} )
	{
		untie %{ $self->{tie} };
		delete $self->{tie};
	}
}

sub DESTROY
{
	my $self = shift;
	# no need..
	$self->UNTIE;
}


#########################################################

1;

__END__

# locktest

while ( 1 )
{
tie my %h1, 'DB', 'testdb';
map {
	my %tmp = %{ $h1{test}{$_} };
	print "* $_:\n";
	map {
	print "* $_ => $tmp{$_}\n";
	} keys %tmp;
}
keys %{$h1{test}};
sleep 1;
}


printf "Hello\n";

my %h;
tie %h, 'DB', 'testdb';

#$h{users} = {kenney=> {name=>'Kenney',password=>'DaPass'}};
#$h{users}{someone} = {name=>'Someone', attr=>"Attribute"};

untie %h;
print "\n";

tie %h, 'DB', 'testdb';
print "-----------\n";

my $u = $h{users}{kenney};

print "* USER: ".DataCoder::encode($u)."\n";

$h{users}{kenney}{password}='baaaaaaaaaaaar';
#$$u{password}='foo';

print "* USER: ".DataCoder::encode($u)."\n";


map {
	print "LIST User: $_\n";
	my $u = $_;

	map {
		print "  $_ = $h{users}{$u}{$_}\n";
	} keys %{ $h{users}{$u} };

} keys %{ $h{users} };

