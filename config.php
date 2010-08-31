<?php

$_IMC = array();
$_IMC["version"] = "@VERSION";//版本
$_IMC["domain"] = "www.kehao.com";//网站注册域名
$_IMC["apikey"] = "69bf0f86-b424-11df-a17e-001a92e5f0be";//网站注册apikey
$_IMC["host"] = "webim20.cn";//im服务器
$_IMC["port"] = 8000;//服务端接口端口
$_IMC["theme"] = "base";//界面主题，根据webim/static/themes/目录内容选择
$_IMC["local"] = "zh-CN";//本地语言，扩展请修改webim/static/i18n/内容
$_IMC["emot"] = "default";//表情主题
$_IMC["show_realname"] = false;//是否显示好友真实姓名
$_IMC["opacity"] = 80;//toolbar背景透明度设置

@include_once DISCUZ_ROOT . './forumdata/cache/plugin_webim.php';
$c =  $_DPLUGIN['webim']['vars'];
foreach($c as $k => $v){
	if(!empty($v)){
		$_IMC[$k] = $v;
	}
}	
