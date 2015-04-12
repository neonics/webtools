<?php
/**
 * Authentication Component
 *
 * USAGE:
 *
 *   annotate an element with
 *
 *      data-component="auth".
 *   
 *   and annotate nested elements with:
 *
 *      data-auth-role="a_role_name".
 *
 * Elements with auth roles specified will only be shown if the
 * authenticated user has the role.
 */
require_once( "Util.php");
require_once( "ModuleManager.php");

global $debug; $debug = 2;

ModuleManager::loadModule( 'auth' );

$roles = explode(',', auth_roles() );

$tmp_id = "auth".rand();


?>

<script type='text/javascript' id='<?php echo $tmp_id; ?>'>
{
	var tmp_id = '<?php echo $tmp_id; ?>';
	var here = $( '#' + tmp_id );

	console.log( here, here.parent() );
	here.parent().find( '[data-auth-role]').each( function() {
		console.log( "[auth] FOUND:", arguments );
		if ( [ <?php echo implode(',', array_map(function($v){return "'$v'";}, $roles ) ); ?> ].indexOf( $(this).data('auth-role') ) < 0 )
		{
			console.log( "removing ", this );
			this.remove();
		}

	} );
}
</script>
