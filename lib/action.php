<?php

function isAction( $action ) { return $action == getArg( 'action' ) || getArg( "action:$action" ) !== null; }
function getArg( $name ) { return isset( $_REQUEST[$name] ) ? $_REQUEST[$name] : null; }


/**
 * checks all request variables with the format "action:ACTIONTYPE[:args]";
 * the value is ignored (as the value for buttons is usually a label).
 *
 * @return an array of arrays. Each (inner) array consists of the action name
 * 				followed by the arguments.
 *
 * Example:
 *
 * XXX separator char = won't work here
 *
 *  ...?action:foo:bar:baz=A:x=y=z:list=X,Y,Z
 *
 *  will result in:
 *
 *  array(
 *		array( 'foo',
 *			'bar',
 *			array( 'baz=A', 'baz'=>'A' ),
 *			array( 'x=y=z', array( 'x', 'y', 'z' ) ),
 *			array( 'list=X,Y,Z', 'list' => array( 'X', 'Y', Z' ) )
 *	)
 *
 */
function getActions() {
	static $actions = null;
	if ( isset( $actions ) ) {debug("reusing previous actions");return $actions;}// don't parse again

	$actions = array();
	foreach ( $_REQUEST as $k => $v )
	{
		$m = null;
		if ( preg_match( "/^action:([^:]+)((:[^:]+)*)$/", $k, $m ) )
		{
			#debug("found action: $k -> $v:  action=$m[1] args=$m[2]" );
			$args = explode( ":", substr( $m[2], 1 ) );
			$actions[] = array_merge( array( $m[1]), array_merge( array_map(
				function($a) use($args)
				{
#					return explode("=", $a );
#					debug(" $a -> {$args[$a]} ");

					if ( strpos( $a, "/" ) === false )
					{
							return $a;
						
					}
					else
					{
						$l = explode( "/", $a );

						if ( count( $l ) == 2 )
						{
							$k = $l[0];
							$v = $l[1];

							if ( strpos( $v, ',' ) !== false )
								$v = explode( ",", $v );

							return array( $a, $k => $v );
						}
						else
						{
							return array( $a, $l );
						}
					}
				},
				$args
			) ) ) ;
		}
	}

	#warn( print_r( $actions, true ) );

	return $actions;
}


function getActionArg( $action, $argname ) {
	foreach ( $action as $i=>$v )
		if ( is_array( $v ) )
			if ( isset( $v[ $argname ] ) )
				return $v[ $argname ];
	throw new Exception("Missing required argument '$argname' for action ".implode(':',$action));
}


function getAction( $name ) {
	foreach ( getActions() as $i => $a )
		if ( $a[0] == $name )
			return $a;
}
