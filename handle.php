<?php

$pspBaseDir = ".";

$requestURIRoots = Array(
	'/webtools',
);

# loglevel:
$debug = 2;
$debugDumpFiles = 1;
$logging = 2;  // 1 = file, 2 = http response, 3 = both

# logicDir = "psp";
# styleDir = "style";
# contentDir = "content";

////////////////////////////////////////////////////////////////////////////

$requestBaseDir = dirname( __FILE__ );

require "$pspBaseDir/serve.php";

?>
