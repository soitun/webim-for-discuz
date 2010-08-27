<?php

/**
 * @author Hidden <zzdhidden@gmail.com>
 *
 */

function install_db($db, $file){
	$logs = array();
	$sql = file_get_contents($file);
	/* Replace @charset to database charset at first. */
	$sql = preg_replace('/\@charset/', $db['charset'], $sql);
	/* Add db prefix */
	$sql = preg_replace('/\webim_/', $db['db_prefix'].'webim_', $sql);

	$link = mysql_connect($db['host'], $db['username'], $db['password']);
	mysql_select_db($db['db_name'], $link);
	$charset = $db['charset'] || 'utf8';
	$sqls = explode(";", $sql);
	$succ = true;
	$error_msg = "";
	foreach($sqls as $k => $v){
		$v = trim($v);
		if(!empty($v)){
			$result = mysql_query($v.";");
			if(!$result){
				$succ = false;
				$error_msg .= mysql_error()."\n";
			}
		}
	}
	mysql_close();
	$logs[] = array($succ, "安装数据", $file);
	return $logs;
}

function clean_cache($dir){
	//delete cache files
	$handle = opendir($dir);
	while (($file=readdir($handle)) != false) {
		$path = $dir.DIRECTORY_SEPARATOR.$file;
		if (!is_dir($path)) {
			unlink($path);
		}
	}
	return array(array(true, "清除缓存", $dir));
}

function input_config($config){
	$q = stdin("输入im服务器地址 (".$config['host']."): ");
	if(!empty($q)){
		$config['host'] = $q;
	}
	$q = stdin("输入注册域名 (".$config['domain']."): ");
	if(!empty($q)){
		$config['domain'] = $q;
	}
	$q = stdin("输入注册apikey (".$config['apikey']."): ");
	if(!empty($q)){
		$config['apikey'] = $q;
	}
	return $config;
}

function is_db_connectable($db){
	$link = mysql_connect($db['host'], $db['username'], $db['password']);
	$ok = $link && mysql_select_db($db['db_name'], $link);
	mysql_close();
	return $ok;
}

function stdin($notice, $required = false){
	echo $notice;
	$stdin=fopen('php://stdin','r');
	$input=fgets($stdin, 1024);
	$q = trim($input);
	$q = $required && empty($q) ? stdin($notice) : $q;
	return $q;
}

function merge_config($new, $old){
	if($old){
		foreach($old as $k => $v){
			if(isset($new[$k]) && $k != 'version' && $k != 'enable'){
				$new[$k] = $v;
			}
		}
	}
	return $new;
}

function select_unwritable_path($paths){
	$p = array();
	foreach($paths as $k => $v){
		if(!is_writable($v)){
			$p[] = $v;
		}
	}
	return $p;
}
function log_install($logs, $truncate_size, $html = false){
	$faild_num = 0;
	foreach($logs as $k => $v){
		if(!$v[0]){
			$faild_num += 1;
		}
	}
	$head = $faild_num > 0 ? "安装WebIM失败" : "安装WebIM成功";
	$desc = $faild_num > 0 ? "WebIM安装失败，请联系开发人员。" : "WebIM安装成功。";
	$markup = "";
	if($html){
		$markup .= '<div class="box"><h3>'.$head.'</h3><div class="box-c"><p class="box-desc">'.$desc.'</p><ul>';
		foreach($logs as $k => $v){
			$markup .= "<li>".$v[1].($v[0] ? " 成功" : " 失败")." (".substr($v[2], $truncate_size).")"."</li>";
		}
		$markup .= '</ul></div></div>';

	}else{
		$markup .= "\n$head\n---------------------------------\n";
		foreach($logs as $k => $v){
			$markup .= $v[1].($v[0] ? " 成功" : " 失败")." (".substr($v[2], $truncate_size).")	\n";
		}
		$markup .= "---------------------------------\n";
		$markup .= "\n$desc\n\n";
	}
	return $markup;
}
function unwritable_log($paths, $truncate_size = 0, $html = false){
	$head = "无可写权限";
	$desc = "下面这些文件或目录需要可写权限才能继续安装，请修改这些文件以及文件夹内所有文件的权限为777";
	$markup = "";
	if($html){
		$markup .= '<div class="box"><h3>'.$head.'</h3><div class="box-c"><p class="box-desc">'.$desc.'</p><ul>';
		foreach($paths as $k => $v){
			$markup .= "<li>".substr($v, $truncate_size)."</li>";
		}
		$markup .= '</ul></div></div>';
	}else{
		$markup .= "\n".$desc."\n";
		$markup .= "---------------------------------\n";
		foreach($paths as $k => $v){
			$markup .= substr($v, $truncate_size)."\n";
		}
		$markup .= "---------------------------------\n\n";
	}
	return $markup;
}
/**
 * 
 * @param
 * @return
 *
 */
function report_install(){
}

?>
