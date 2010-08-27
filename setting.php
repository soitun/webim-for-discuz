<?php

include_once('common.php');
$data = p('data');
if(empty($data)){
	header("HTTP/1.0 400 Bad Request");
	echo 'Empty post $data';
}else{
        $_SGLOBAL['db']->query("UPDATE ".im_tname('setting')." SET web='$data' WHERE uid=$space[uid]");
	echo 'ok';
}
