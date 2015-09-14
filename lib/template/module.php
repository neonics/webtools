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
 *
 * Have $request->template_data = [
 * 		'permission' => '...',
 *		'role' => '...',
 *		'pages' => [
 *			'my_page'	=> [ 'permission' => 'my_permission' ],
 *			'my_page2'=> [ 'role' => 'my_role' ],
 *		]
 * ];
 */
function _module_auth( $request ) {
	#echo "<pre><b>module_auth</b>:\n".print_r($request,1)."</pre>";
	if ( array_key_exists( 'role', $request->template_data )
		&& null === $request->template_data['role'] // public
	)
		return;

	$page = preg_replace( "@\.(php|html)$@", "", $request->requestRelURI );

	$pperm = gad( gad( gad( $request->template_data, 'pages' ), $page ), 'permission' );
	$prole = gad( gad( gad( $request->template_data, 'pages' ), $page ), 'role' );

	$mperm = gad( $request->template_data, 'permission' );
	$mrole = gad( $request->template_data, 'role' );

	if ( empty( $pperm ) && empty( $prole ) && empty( $mperm ) && empty( $mrole ) )
		warn( "missing permission setting for page <b>$page</b> in module <b>".$request->template_data['template_module']."</b>" );

	// If page specific permissions are specified, these must be satisfied.
	if ( !empty( $pperm ) || !empty( $prole ) )
	{
		if ( !empty( $pperm ) && auth_permission( $pperm ) )
			return;
		if ( !empty( $prole ) && auth_role( $prole ) )
			return;
	}
	else	// fallback to module permissions
	{
		if ( !empty( $mperm ) && auth_permission( $mperm ) )
			return;
		if ( !empty( $mrole ) && auth_role( $mrole ) )
			return;
	}

	if ( $p = gd( $pperm, $mperm ) )
		throw new SecurityException( "No <i>$p</i> permission to access $page" );
	else if ( $r = gd( $prole, $mrole ) )
		throw new SecurityException( "No permission to access $page (requires role <i>$r</i>)" );
	else
		throw new SecurityException( "No permission to access page $page, and no permissions/roles defined! ($pperm / $prole / $mperm / $mrole)" );
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
