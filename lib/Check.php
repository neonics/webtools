<?php
// depends on function 'fatal'; default implementation in Util.php
class Check
{
	public static function identifier( $name ) {
		if ( preg_match( "/^[\w_]+$/", $name ) )
		{
			return $name;
		}
		else
			fatal( "expect identifier, not '" .htmlspecialchars( $name )."'" );
        return null;
	}

	public static function email( $value, $fatal = true ) {
		if ( preg_match ( "/^[^@]+@([\w\d-]+\.)+[\w\d-]+$/", $value ) )
			return $fatal ? $value : true;
		else
			$fatal ? fatal( "not an e-mail address: '" . htmlspecialchars( $value ) . "'" ) : false;
	}

	public static function length( $value, $minLen = 1, $maxLen = null, $fatal = true ) {
		if ( $minLen !== null && strlen( $value ) < $minLen )
			$fatal ? fatal( "invalid length: ".strlen($value). "<$minLen" ) : false;
		else
			return $fatal ? $value : true;
	}
}

