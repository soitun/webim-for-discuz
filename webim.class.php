<?php

/**
 * Author: Hidden
 * Date: Mon Aug 23 22:25:15 CST 2010
 *
 */
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_webim {

	function global_footer() {
		global $GLOBALS;
		if(!$GLOBALS['discuz_uid']) {
			return;
		}
		@include_once DISCUZ_ROOT . './forumdata/cache/plugin_webim.php';
		$config =  $_DPLUGIN['webim']['vars'];
		$theme = empty($config['theme']) ? 'base' : $config['theme'];
		$local = empty($config['local']) ? 'zh-CN' : $config['local'];
		$min = ".min";                
		if ( isset( $_GET['webim_debug'] ) ) {                        
			$min = "";                
		}
		return <<<EOF
		<link href="plugins/webim/static/webim.discuz$min.css?@VERSION" media="all" type="text/css" rel="stylesheet"/>
		<link href="plugins/webim/static/themes/{$theme}/jquery.ui.theme.css?@VERSION" media="all" type="text/css" rel="stylesheet"/>
		<script src="plugins/webim/static/webim.discuz$min.js?@VERSION" type="text/javascript"></script>
		<script src="plugins/webim/static/i18n/webim-{$local}.js?@VERSION" type="text/javascript"></script>
		<script src="plugins/webim/custom.js.php?@VERSION" type="text/javascript"></script>
EOF;
	}
}
