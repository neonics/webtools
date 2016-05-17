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
			return $fatal ? fatal( "not an e-mail address: '" . htmlspecialchars( $value ) . "'" ) : false;
	}

	public static function length( $value, $minLen = 1, $maxLen = null, $fatal = true ) {
		if ( $minLen !== null && strlen( $value ) < $minLen )
			return $fatal ? fatal( "invalid length: ".strlen($value). "<$minLen" ) : false;
		else
			return $fatal ? $value : true;
	}

	public static function int( $value, $fatal = true ) {
		if ( ! is_int( $value ) && intval($value) != $value )
			return $fatal ? fatal( "invalid int: $value" ) : false;
		else
			return $fatal ? $value : true;
	}

	public static function date( $value, $fatal = true ) {
		if ( preg_match( '@^\d\d\d\d([/-])\d\d?\1\d\d?$@', $value ) )
			return $fatal ? $value : true;
		else
			return $fatal ? fatal( "invalid date: $date" ) : false;
	}
}

