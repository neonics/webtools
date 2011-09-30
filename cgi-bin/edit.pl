#!/usr/bin/perl

use lib '.';
use CGI;

my $cgi = CGI->new();

$cgiURL = $cgi->scriptURL;

$directory = "../draft/tmp/content/";

@allowed = `ls $directory`;
chomp @allowed;


my $session = $cgi->getSession();

print "Content-Type: text/html\n\n";

if ( !defined($session) )
{
	my $url = $cgi->encodeURI( $cgi->currentURL() );

	my $debug = $session ? "User: $session->{username}\n":"";

	#map { $debug .= "  $_ = $ENV{$_}\n" } keys %ENV;


		print <<"EOF";
	<html>
	  <head>
	    <meta http-equiv="REFRESH" content="1;login.pl?url=$url"/>
	  </head>
	  <body>
		<pre style="color: grey">$debug</pre>
	    Redirecting to login page.
	  </body>
	</html>
EOF
}
else
{

	$base = $cgi->{form}{base};

	$f = $cgi->{form}{f};

	if ( defined ($f) && grep { $_ eq $f } @allowed )
	{
		$filename = $directory . $f;
	}

	$result = "FN: $filename  ";


	if ( defined ($filename) )
	{
		if ( defined ($cgi->{form}{'content'}) )
		{
			my $co = $cgi->{form}{'content'};
			$co =~ s/\r//mg;
			$co =~ s/\xa0//mg;
			writeFile($filename, $co );
			$result .= "File '$f' written<br/>";
			$result .= "Site update result: <br/>" . `cd $directory/.. && make`;
			$c = $cgi->{form}{'content'};
		}
		else
		{
			$c = `cat $filename`;
		}

	  $c =~ s/\r//mg;
		$c =~ s/"/" + '"' + "/mg;
		$c =~ s/^/+ "/mg;
		$c =~ s/$/\\n"/mg;
		$c =~ s/\\n"$//;
		$c = '""' . $c;
		if ( ! ($c =~ /"$/ ))
		{
			$c .='"';
		}

		&printPage();
	}
	else
	{
		print "No hacking!";
	}
}




sub printPage()
{


print <<"EOF";

<html>
  <head>
		<base href="$base"></base>
		<script type="text/javascript" src="../ckeditor/ckeditor.js"></script>
		<script type="text/javascript" src="../ckeditor/_samples/sample.js"></script>
	</head>
	<body>
		<pre>$result</pre>

		<div id="sourceView" style="border: 1px solid red"></div>

		<script type="text/javascript">
			var content = $c;
		</script>

		<form>
			<textarea cols="80" id="editor1" name="editor1" rows="40"></textarea>
		</form>

			<script type="text/javascript">
			//<![CDATA[

				var textlist = [];

				/**
				 * This will 'save' the editing context back in the list,
				 * and put the clicked one in the editor.
				 */
				function toggle(q)
				{
					var editor = CKEDITOR.instances.editor1;

					if ( editingId >= 0 )
					{
						textlist[editingId] = editor.getData();
					}

					editingId = q;
					editor.setData( textlist[q] );
				}

				function parse( xml )
				{
					if ( window.DOMParser )
					{
						return new DOMParser().parseFromString( xml, "text/xml" );
					}
					else if ( window.ActiveXObject )
					{
						var xmlDoc = new ActiveXObject( // "MSXML2.DOMDocument.3.0" );//
							"Microsoft.XMLDOM" );
						xmlDoc.async=false;
						xmlDoc.loadXML( xml );
						return xmlDoc.documentElement;
					}
					else
					{
						alert("No go..");
					}
				}

				function textify( list )
				{
					textlist = [];//new Array(list.length);
					for (i=0; i<list.length; i++)
					{
						var co="";
						for ( j=0; j < list[i].childNodes.length; j++)
							co += ser==null? list[i].childNodes[j].xml : ser.serializeToString( list[i].childNodes[j] );

						textlist[i] = co;
					
						var a = document.createElement( "a" );
						a.style.display="block";
						a.setAttribute( "href", "javascript:toggle(" + i + ");" );
						a.appendChild( document.createTextNode( "+ language: " + list[i].getAttribute("xml:lang") ));
						sv.appendChild( a );
					}
				}
/*
				function dump(q, lev)
				{
					if ( lev > 5 ) return "";

					var indent = ""; for ( var i =0; i < lev; i++) indent+="  ";
					var s = indent + q
						+ ("("+q.nodeName+": "+q.nodeValue+")")
						+ ".childNodes[" + q.childNodes.length + "]:\\n";
					for ( var  i = 0; i < q.childNodes.length; i++)
						s+= indent + "  " + dump(q.childNodes[i], lev+1) + "\\n";

					return s;
				}

				function dump2(q, lev)
				{
					if ( lev > 5 ) return "";

					var indent = ""; for ( var i =0; i < lev; i++) indent+="  ";

					var s =  indent;
					if ( q.nodeType == 1 )
						s+= "<"+q.nodeName+"> cn=" + q.childNodes.length;
					else if ( q.nodeType == 3 )
						s+= q.nodeValue;
					for ( var  i = 0; i < q.childNodes.length; i++)
						s+= indent+"{\\n"+indent + dump2(q.childNodes[i], lev+1) + "}\\n";

					return s;
				}
*/

				function untextify( list )
				{
					for ( var i=0; i < list.length; i ++)
					{
						var li = list[i];


						while ( li.hasChildNodes() )
							li.removeChild( li.lastChild );

						var str = textlist[i];
						str = str == null ? "" : str;

						str=str.replace( /&nbsp;/g, "&#160;" );
						str=str.replace( /&amp;/g, "&#38;" );
						str=str.replace( /\xa0/g, "" );

						str = "<?xml version='1.0'?>\\n<doc>" + str + "</doc>";

						var p = parse( str ).documentElement;

						for ( var k = 0; k < p.childNodes.length; k++)
						{
							li.appendChild( p.childNodes[k].cloneNode( true ) );
						}
					}
				}

				var ser = null;

				var daContent = parse( content );

				var sv = document.getElementById('sourceView');
				var list = daContent.getElementsByTagName('slide');

				if ( daContent.xml == null )
					ser = new XMLSerializer();

				function serialize( xml )
				{
					return xml.xml == null ? new XMLSerializer().serializeToString( xml ) : xml.xml;
				}

				function showResult()
				{
					var v = document.getElementById( "view" );
					v.value = serialize( daContent );

					/*
					while ( v.hasChildNodes() )
						v.removeChild( v.lastChild );

					var str = serialize( daContent );
					v.appendChild( document.createTextNode( str ) );
					*/
				}
				textify( list );
				untextify ( list );
			</script>


			<script type="text/javascript">

				var MainDocument = document;

				var editingId=-1;

				CKEDITOR.config.extraPlugins = 'xmllayout,save2';

				CKEDITOR.plugins.add( 'save2', 
					{
						init : function( editor )
						{
							var cmd = editor.addCommand( 'save2',
								{
									modes : { wysiwyg:1, source:1 },

									exec : function( editor )
									{
										if ( editingId >= 0 )
											textlist[editingId] = editor.getData();

										untextify( list );
										showResult();
										document.getElementById('uploadForm').submit();
									}
								}
							);

							cmd.modes = { wysiwyg : 1 };//!!( editor.element.$.form ) };

							editor.ui.addButton( 'Save2',
								{
									label : editor.lang.save,
									command : 'save2',
									className : 'cke_button_save'
								}
							);
						}
						
					}
				);

				CKEDITOR.replace( 'editor1',
					{
						fullPage : false,
						enterMode : CKEDITOR.ENTER_BR,

						on :
						{
							instanceReady: function(ev)
							{
								this.dataProcessor.writer.setRules( 'p',
									{
										indent: false,
										breakBeforeOpen: true,
										breakAfterOpen: false,
										breakBeforeClose: false,
										breakAfterClose: false
									}
								);
								this.dataProcessor.writer.setRules( 'li',
									{
										indent: false,
										breakBeforeOpen: true,
										breakAfterOpen: false,
										breakBeforeClose: false,
										breakAfterClose: false
									}
								);

							}
						},
						toolbar : [
							['Source', 'Save2', 'Preview'],
							['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
							['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
							['Styles', 'Format'],
							['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript'],
							['NumberedList', 'BulletedList'],
							['Link', 'Unlink'],
							['Image', 'Table', 'SpecialChar'],
							['TextColor', 'BGColor']
						]
					}
				);
			//]]>
			</script>

		<form id="uploadForm" action="$cgiURL" method="post">
			<input type="hidden" name="base" value="$base"/>
			<input type="hidden" name="f" value="$f"/>
			<input type="hidden" id="view" cols="40" name="content">
		</form>

	</body>
</html>

EOF
}

sub writeFile
{
	my ($file, $content) = @_;

	open OUT, ">$file" or die "Error creating file: $!\n";

	print OUT $content or die "Error writing to file: $!\n";

	close OUT or die "Error closing file: $!\n";
}

1;
