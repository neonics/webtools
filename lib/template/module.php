<?php 
/**
 * Template Module / Module template : streaming template_content replacement with menu.
 *
 * This file depends on inc/BootstrapTemplate.php which defines the actual template
 * function template_do(), which (via BootstrapTemplate) calls template_content().
 * This offers no menu capability, which this method provides.
 */

// $data = function module_init() //
// function module_content( $data ) //

function template_init( $request ) {
		ob_start();
		$request->module_data = function_exists( 'module_init' ) ? (object) module_init( $request ) : new stdClass;
		$request->module_data->menu = ob_get_clean();
}

/**
 * At current, permissions are assumed to become more restrictive with increasing
 * specificity. In other words, a basic permission or role to access the module
 * is required, and pages within the module may have more restrictive permissions.
 */
function _module_auth( $request ) {
	#echo "<pre><b>module_auth</b>:\n".print_r($request,1)."</pre>";
	if ( array_key_exists( 'role', $request->template_data )
		&& null === $request->template_data['role'] // public
	)
		return;

	if ( !empty( $request->template_data['permission'] ) ) {
		if ( ! auth_permission( $request->template_data['permission'] ) )
			throw new SecurityException( "No permission to access $page" );
	}
	else if ( !empty( $request->template_data['role'] ) ) {
		if ( ! auth_role( $request->template_data['role'] ) )
			throw new SecurityException( "No permission to access $page" );
	}
	else
	{
		$page = preg_replace( "@\.(php|html)$@", "", $request->requestRelURI );

		$perm = gad( gad( gad( $request->template_data, 'pages' ), $page ), 'permission' );
		$role = gad( gad( gad( $request->template_data, 'pages' ), $page ), 'role' );

		if ( empty( $perm ) && empty( $role ) )
		{
			warn( "missing permission setting for page <b>$page</b> in module <b>".$request->template_data['template_module']."</b>" );
		}
		else
		{
			if ( ! empty( $perm ) )
			{
				if ( !auth_permission( $perm ) )
					throw new SecurityException( "No permission '$perm' to access $page" );
			}
			else #if ( ! empty( $role ) 
			{
				if ( !auth_role( $role ) )
					throw new SecurityException( "No permission to access $page" );
			}
		}

	}
}

function template_content( $request ) // only one module per page
{

	_module_auth( $request );

	//ob_flush(); flush();
		//$tmp = function_exists( 'module_menu' ) ? module_menu( $request->template_data['menu']() ) : null;

#		die( "<pre>".print_r( $request->template->noticebar,1 ) ."</pre>");

	?>
		<div>
	<?php

	$override = "style='margin-top: 1em'"; // for when no <header>

	if ( function_exists( 'module_menu' ) )
	{
		// TODO: update js/menu.js to match the active link
		ob_start();
		$submenu_classes = "";
		$submenu_label =
		module_menu( $request, $submenu_classes );
		$submenu = ob_get_clean();

		$submenu = \template\AuthFilter::filter( $submenu );

		if ( strlen($submenu) )
		{
			?>
				<header class='navbar'>

					<nav class='menu navbar'>

						<ul class='nav navbar-nav'>
							<li>
								<h2><?php echo $submenu_label;?></h2>
								<button data-target="#module-menu" data-toggle="collapse" class="navbar-toggle" type="button"
									style='color:red;background-color:blue'
								>
									<span class="sr-only">Toggle Navigation</span>
									<span class="icon-bar"></span>
								</button>
							</li>
						</ul>
						<ul class="nav navbar-nav navbar-collapse collapse submenu <?php echo $submenu_classes;?>" id="module-menu">

							<?php echo $submenu; ?>

						</ul>
						<script type='text/javascript'>
							menuActive( document.getElementById( 'module-menu' ) );
						</script>
					</nav>

				</header>
			<?php
			$override = "";
		}
	}
	?>
			<div class='container-fluid' <?php echo $override;?>>
	<?php
		//ob_flush();
		flush();
		module_content( $request );
	?>
			</div>
		</div>
	<?php
}

return [ 'template_module' => 'index', 'role' => null ];
