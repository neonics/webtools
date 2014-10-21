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

# hardcoded:
# logicDir = "psp";
# styleDir = "style";
# contentDir = "content";

////////////////////////////////////////////////////////////////////////////

# set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/inc' );
#$psp_custom_handlers = array( 'auth' => 'AuthRequestHandler', 'template' => 'TemplateRequestHandler' );
#$staticContent = array( 'fonts/', 'resources/' ); # css/js/img will be added in serve.php

$requestBaseDir = dirname( __FILE__ );



require "$pspBaseDir/serve.php";

?>
