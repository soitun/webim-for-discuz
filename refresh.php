<?php

include_once('common.php');

if(empty($ticket)){
	header("HTTP/1.0 400 Bad Request");
	echo 'Empty post $ticket';
}else{
	require 'config.php';
	$im = new WebIM($user, $ticket, $_IMC['domain'], $_IMC['apikey'], $_IMC['host'], $_IMC['port']);
	echo $im->offline();
}
