<?php

$pspBaseDir = ".";

$requestURIRoots = Array(
	'/',
	'/webtools',
);

# loglevel:
$debug = 0;
$debugDumpFiles = 0;
$logging = 1;  // 1 = file, 2 = http response, 3 = both

# logicDir = "psp";
# styleDir = "style";
# contentDir = "content";

////////////////////////////////////////////////////////////////////////////

$requestBaseDir = dirname( __FILE__ );

require "$pspBaseDir/serve.php";

?>
