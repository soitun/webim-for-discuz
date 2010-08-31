<?php

/**
 * Author: Hidden
 * Date: Mon Aug 23 21:27:07 CST 2010
 *
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
require('runquery.php');

$sql = <<<EOF

DROP TABLE IF EXISTS cdb_webim_settings;
DROP TABLE IF EXISTS cdb_webim_histories;

EOF;

#runquery2($sql, UC_DBTABLEPRE);

$finish = TRUE;
