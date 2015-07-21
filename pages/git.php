<?php
return [];

function template_content() { ?>

<style type='text/css' scoped='scoped'>a { text-decoration: none; } </style>
<pre style='background-color: black;color:white'>
<?php


$opts = "--pretty=format:\"%C(yellow)%h%Creset%C(blue bold)%d%Creset %C(white bold)%s%Creset %C(white dim)(by %an %ar)%Creset' \"";

$cmd = "/bin/bash -c '/usr/bin/git log --color=always --oneline .'";

if ( isset( $_REQUEST['sha'] ) && preg_match( "/^[a-f0-9]+$/", $_REQUEST['sha'] ) )
{
	$cmd = "/bin/bash -c '/usr/bin/git log --color=always -n1 --name-status " .	$_REQUEST['sha']
	."; echo ; /usr/bin/git diff --color=always " .	$_REQUEST['sha'] .'^..'. $_REQUEST['sha']
	.  "'";
}
#echo "Executing: $cmd\n";
ob_start();
system( $cmd );
//"c:/cygwin/bin/"
	#"git log --graph --pretty=format:'%C(yellow)%h%Creset%C(blue bold)%d%Creset %C(white bold)%s%Creset %C(white dim)(by %an %ar)%Creset' --all --color=always"
#"git log --oneline | wc"

$out= ob_get_contents();
ob_end_clean();

$out = htmlentities( $out );

$out = preg_replace( "/\x1b\[1m/", "<span style='font-weight:bold'>", $out);
$out = preg_replace( "/\x1b\[0?m/", "</span>", $out);
$out = preg_replace( "/\x1b\[33m/", "<span style='color:yellow'>", $out );
$out = preg_replace( "/\x1b\[1;34m/", "<span style='color:blue;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;37m/", "<span style='color:white;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[2;37m/", "<span style='color:#888'>", $out );

$out = preg_replace( "/\x1b\[1;31m/", "<span style='color:blue;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;32m/", "<span style='color:green;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;33m/", "<span style='color:cyan;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;33m/", "<span style='color:cyan;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;34m/", "<span style='color:red;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;35m/", "<span style='color:orange;font-weight:bold'>", $out );
$out = preg_replace( "/\x1b\[1;36m/", "<span style='color:purple;font-weight:bold'>", $out );

$out = preg_replace( "/\x1b\[31m/", "<span style='color:red'>", $out );
$out = preg_replace( "/\x1b\[32m/", "<span style='color:#0f0'>", $out );
$out = preg_replace( "/\x1b\[33m/", "<span style='color:cyan'>", $out );
$out = preg_replace( "/\x1b\[34m/", "<span style='color:red'>", $out );
$out = preg_replace( "/\x1b\[35m/", "<span style='color:orange'>", $out );
$out = preg_replace( "/\x1b\[36m/", "<span style='color:#ff00ff'>", $out );
$out = preg_replace( "/\x1b\[41m/", "<span style='color:blue'>", $out );



$out = preg_replace( "@(<span style='color:yellow'>)([a-f0-9]+)(</span>)@", "<a href='?sha=$2'>$0</a>", $out );

echo $out;
?>
</pre>


<?php }
