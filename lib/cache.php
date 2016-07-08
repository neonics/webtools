<?php
function cache( $cache_key, callable $f ) {
	static $cache = [];
	return array_key_exists( $cache_key, $cache )
	? $cache[ $cache_key ]
	: $cache[ $cache_key ] = $f();
}
