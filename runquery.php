<?php

function runquery2($sql, $pre = null) {
	global $dbcharset, $tablepre, $db;
	$pre = is_null($pre) ? $tablepre : $pre;

	$sql = str_replace("\r", "\n", str_replace(array(' cdb_', ' {tablepre}', ' `cdb_'), array(' '.$pre, ' '.$pre, ' `'.$pre), $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {

			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				$db->query(createtable($query, $dbcharset));

			} else {
				$db->query($query);
			}

		}
	}
}

