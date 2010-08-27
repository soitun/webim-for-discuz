<?php

/**
 * Add json_encode and json_decode in php4
 *
 */
if( !function_exists('json_encode') ) {
	require_once 'JSON.php';
	function json_encode($data) {
		$json = new Services_JSON();
		return( $json->encode($data) );
	}
}

// Future-friendly json_decode
if( !function_exists('json_decode') ) {
	require_once 'JSON.php';
	function json_decode($data) {
		$json = new Services_JSON();
		return( $json->decode($data) );
	}
}

/**
 * Provide a simple method for the $_GET and $_POST
 *
 */
function g($key = '') {
	return $key === '' ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : null);
}

function p($key = '') {
	return $key === '' ? $_POST : (isset($_POST[$key]) ? $_POST[$key] : null);
}

function gp($key = '',$def = null) {
	$v = g($key);
	if(is_null($v)){
		$v = p($key);
	}
	if(is_null($v)){
		$v = $def;
	}
	return $v;
}

/**
 * 
 *
 */
function _iconv($s,$t,$data){
	if( function_exists('iconv') ) {
		return iconv($s,$t,$data);
	}else{
		require_once 'chinese.class.php';
		$chs = new Chinese($s,$t);
		return $chs->convert($data);
	}
}

function to_unicode($s){ 
	return preg_replace("/^\"(.*)\"$/","$1",json_encode($s));
}

function unicode_val($ob){
	foreach($ob as $k => $v){
		$ob[$k] = to_unicode($v);
	}
	return $ob;
}

?>
