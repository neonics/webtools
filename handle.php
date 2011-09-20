<?php

$pspBaseDir = ".";

$requestURIRoots = Array(
	'/web',
);

# $debug = 2;
# $debugDumpFiles = 1;

# logicDir = "psp";
# styleDir = "style";
# contentDir = "content";

////////////////////////////////////////////////////////////////////////////

$requestBaseDir = dirname( __FILE__ );

require "$pspBaseDir/serve.php";

?>
