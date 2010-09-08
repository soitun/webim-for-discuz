<?php

/**
 * Author: Hidden
 * Date: Mon Aug 23 22:14:34 CST 2010
 *
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

require_once(dirname(__FILE__) . "/lib/util.php");

//$sl = $scriptlang['webim'];
$tl = $templatelang['webim'];
$notice = "";
@include_once DISCUZ_ROOT . './forumdata/cache/plugin_webim.php';
$config =  $_DPLUGIN['webim']['vars'];

if($_GET['theme']){
	$theme = $_GET['theme'];
	$db->query("UPDATE {$tablepre}pluginvars SET value='$theme' WHERE pluginid='$pluginid' AND variable='theme'");
	updatecache('plugins');
	$notice = "<div id='notice'>".$tl['themes_success']."</div>";

}else{
	$theme = empty($config['theme']) ? 'base' : $config['theme'];
}

echo $notice;
showtips($tl['themes_tips']);

$path = dirname(__FILE__).DIRECTORY_SEPARATOR."static".DIRECTORY_SEPARATOR."themes";
$files = scan_subdir($path);
$html = '<ul id="themes">';
foreach ($files as $k => $v){
	$t_path = $path.DIRECTORY_SEPARATOR.$v;
	if(is_dir($t_path) && is_file($t_path.DIRECTORY_SEPARATOR."jquery.ui.theme.css")){
		$cur = $v == $theme ? " class='current'" : "";
		$url = $BASESCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=webim&mod=themes&theme='.$v;
		$html .= "<li$cur><h4><a href='$url'>$v</a></h4><p><a href='$url'><img width=100 height=134 src='plugins/webim/static/themes/images/$v.png' alt='$v' title='$v'/></a></p></li>";
	}
}
$html .= '</ul>';
?>
<style type="text/css">
#notice{
	margin-top: 15px;
	padding: 10px;
	text-align: center;
	background: #FFFAF0;
	border: 1px solid #FFD700;
}
#themes{
	overflow: hidden;
	list-style: none;
	padding: 0;
	margin: 0;
	margin-top: 20px;
}
#themes li{
	float: left;
	padding: 10px;
}
#themes li h4{
	margin: 0 0 5px 0;
}
#themes li.current{
	background: yellow;
}
</style>
<?php echo $html;?>
