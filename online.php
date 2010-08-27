<?php

include_once('common.php');

session_start();
if(!isset($_SESSION['timestamp']) || (gp('timestamp') - $_SESSION['timestamp'] > $_IMC['timestamp']*60)) {//第一次登陆，获得好友列表，保存第一次登陆的时间戳
    require_once(DISCUZ_ROOT.'/uc_client/client.php');
    $buddynum = uc_friend_totalnum($space['uid']);
    $buddies = uc_friend_ls($space['uid'], 1, $buddynum, $buddynum);
    foreach($buddies as $value) {
        $friend_ids[] = $value['friendid'];
    }
    $_SESSION['timestamp'] = gp('timestamp');
    if(!isset($_SESSION['friend_ids'])) {
        $_SESSION['friend_ids'] = $friend_ids;
    }
}else {//不是第一次登陆，比较与上次登录的时间差，大于10分钟重新获取好友列表
    $friend_ids = $_SESSION['friend_ids'];
}
$buddy_ids = ids_array(gp("buddy_ids"));//正在聊天的联系人

$new_messages = find_new_message();//查找离线消息
for($i=0;$i<count($new_messages);$i++) {
    $msg_uid = $new_messages[$i]["from"];
    array_push($buddy_ids, $msg_uid);
}

if(!empty($friend_ids)) {
    $ids=join(",",$friend_ids);
    $query = $_SGLOBAL['db']-> query("SELECT username FROM ".tname('members')." WHERE uid IN ($ids)");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $buddie_ids[] = $value['username'];
    }
}
$buddie_ids=array_unique($buddie_ids);
$im = new WebIM($user, null, $_IMC['domain'], $_IMC['apikey'], $_IMC['host'], $_IMC['port']);
$data = $im->online(implode(",",$buddie_ids),"");
if($data->success) {

    $data->rooms = array();
    $online_buddies=build_buddies($data->buddies);
    $data->buddies=array_merge(buddy($buddy_ids),$online_buddies);
    $data->histories=find_history($buddy_ids);
    $data->new_messages=$new_messages;
    echo json_encode($data);
    new_message_to_histroy();
}else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode($data->error_msg);
}
