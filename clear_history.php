<?php
include_once('common.php');
$username = $space['username'];
$ids = ids_array(gp("id"));
if(!empty($ids)) {
    for($i=0;$i<count($ids);$i++) {
        $id = $ids[$i];
        $_SGLOBAL['db']->query("UPDATE ".im_tname('histories')." SET fromdel=1 WHERE `from`='$username'");
        $_SGLOBAL['db']->query("UPDATE ".im_tname('histories')." SET todel=1 WHERE `to`='$username'");
        $q="UPDATE ".im_tname('histories')." SET fromdel=1 WHERE `from`='$username'";
    }
}
echo '{success:true}';
?>

