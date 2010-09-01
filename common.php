<?php
require_once('../../include/common.inc.php');
require_once('lib/webim.class.php');
require_once('lib/json.php');
require_once('config.php');

if(!$discuz_uid)exit('Login at first.');
$_SGLOBAL['supe_uid']=  $discuz_uid;
$_SGLOBAL['db']= $db;
$_SC['charset'] = UC_CHARSET;

$_SGLOBAL['db']->query("SET NAMES utf8");

$ucdb = new dbstuff;
$ucdb->charset = UC_DBCHARSET;
$ucdb->connect(UC_DBHOST, UC_DBUSER, UC_DBPW, UC_DBNAME);
$ucdb->query("SET NAMES utf8");

if( !function_exists('getspace') ) {
    function getspace($uid) {
        global $_SGLOBAL;
        $space = $_SGLOBAL['db']->fetch_first("SELECT username,gender,nickname FROM "
                .tname('members')." m left join "
                .tname('memberfields')
                ." mf  on m.uid=mf.uid WHERE m.uid=$uid");
        $space['uid']=$uid;
        $space['nickname']=$space['nickname']?$space['nickname']:$space['username'];
        return $space;
    }
}
if( !function_exists('tname') ) {
    function tname($name) {
        global $tablepre;
        return $tablepre.$name;
    }
}
function setting() {
    global $_SGLOBAL,$space, $ucdb;
    if(!empty($_SGLOBAL['supe_uid'])) {
        $setting  = $ucdb->fetch_array($ucdb->query("SELECT * FROM ".im_tname('settings')." WHERE uid='$_SGLOBAL[supe_uid]'"));
        if(empty($setting)) {
            $setting = array('uid'=>$space['uid'],'web'=>"");
            $ucdb->query("INSERT INTO ".im_tname('settings')." (uid,web) VALUES ($_SGLOBAL[supe_uid],'')");
        }
        $setting = $setting["web"];
    }
    return json_decode(empty($setting) ? "{}" : $setting);
}
$space = getspace($discuz_uid);

if( !function_exists('user_pic') ) {
    function user_pic($uid, $size='small') {
        return UC_API.'/avatar.php?uid='.$uid.'&size='.$size;
    }
}
if(empty($space))exit();

$user->uid =$space['uid'];
$user->id = $space['username'];
$user->nick = $space['username'];
$user->pic_url = user_pic($user->uid);
$user->show = gp('show') ? gp('show') : "unavailable";
$user->url = "space.php?uid=".$user->uid;


//Common $ticket

$ticket = gp('ticket');
if($ticket){
$ticket = stripslashes($ticket);
}


function to_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv($_SC['charset'],'utf-8',$s);
    }
}

function from_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv('utf-8',$_SC['charset'],$s);
    }
}

////function to_unicode($s) {
////    return preg_replace("/^\"(.*)\"$/","$1",json_encode($s));
////}
function ids_array($ids) {
    return ($ids===NULL || $ids==="") ? array() : (is_array($ids) ? array_unique($ids) : array_unique(split(",", $ids)));
}
function ids_except($id, $ids) {
    if(in_array($id, $ids)) {
        array_splice($ids, array_search($id, $ids), 1);
    }
    return $ids;
}


function complete_status($members){
	if(!empty($members)){
		$num = count($members);
		$ids = array();
		$ob = array();
		for($i = 0; $i < $num; $i++){
			$m = $members[$i];
			$id = $m->uid;
			$ids[] = $id;
			$ob[$id] = $m;
			$m->status = "";
		}
	}
	return $members;

}

function buddy($ids) {
    global $_SGLOBAL,$space;
    $ids = ids_array($ids);
    $ids = ids_except($space['username'], $ids);
    if(empty($ids))return array();
    $ids = join("','", $ids);
    $buddies = array();
    $q="SELECT main.uid, main.username, f.friendid FROM "
            .UC_DBTABLEPRE
            ."members main LEFT OUTER JOIN "
            .UC_DBTABLEPRE
            ."friends f ON f.uid = '$space[uid]' AND main.uid = f.friendid WHERE main.username IN ('$ids')";
    $query = $_SGLOBAL['db']-> query($q);
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
//                realname_set($value['uid'], to_utf8($value['username']));
        $id = $value['username'];
        $nick = nick($value);
        if(empty($value['friendid'])) {
            $group = "stranger";
        }else {
            $group = "friend" ;
        }
        $buddies[]=(object)array('id'=>$id,
                'nick'=> $nick,
                'pic_url' =>user_pic($value['uid']),
                'status'=>'' ,
                'status_time'=>'',
                'url'=>'space.php?uid='.$value['uid'],
                'group'=> $group,
                'default_pic_url' => UC_API.'/images/noavatar_small.gif');
    }
    return $buddies;
}

function find_new_message() {
    global $_SGLOBAL,$space, $ucdb;
    $uname = $space['username'];
    $messages = array();
   
    $query = $ucdb->query("SELECT * FROM "
            .im_tname('histories')
            ." WHERE `to`='$uname' and send = 0 ORDER BY timestamp DESC LIMIT 100");
    while ($value = $ucdb->fetch_array($query)) {
        array_unshift($messages,array('to'=>$value['to'],
                'nick'=>$value['nick'],
                'from'=>$value['from'],
                'style'=>$value['style'],
                'body'=>$value['body'],
                'timestamp'=>$value['timestamp'],
                'type' =>$value['type']));
    }
    return $messages;
}

function new_message_to_histroy() {
    global $_SGLOBAL,$space, $ucdb;
    $uname = $space['username'];
//    var_dump("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$uname' AND send = 0");
    $ucdb->query("UPDATE "
            .im_tname('histories')
            ." SET send = 1 WHERE `to`='$uname' AND send = 0");
}

function find_history($ids,$type="unicast") {
    global $_SGLOBAL,$space, $ucdb;
    $uname= $space['username'];
    $histories = array();
    $ids = ids_array($ids);
    if($ids===NULL)return array();
    for($i=0;$i<count($ids);$i++) {
        $id = $ids[$i];
        $list = array();
       if($type=='multicast') {
            $q="SELECT * FROM ".im_tname('histories')
                    . " WHERE (`to`='$id') AND (`type`='multicast') AND send = 1 ORDER BY timestamp DESC LIMIT 30";
            $query = $ucdb->query($q);
            while ($value = $ucdb->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>$value['to'],
                        'from'=>$value['from'],
                        'style'=>$value['style'],
                        'body'=>$value['body'],
                        'timestamp'=>$value['timestamp'],
                        'type' =>$value['type'],
                        'nick'=>$value['nick']));
            }
        }else{
            $q=  "SELECT main.* FROM "
                    . im_tname('histories')
                    . " main WHERE ((`to`='$id' AND `from`='$uname' AND `fromdel` != 1) or (`from`='$id' AND `to`='$uname' AND `todel` != 1 AND `send`=1))  ORDER BY timestamp DESC LIMIT 30";
            $query = $ucdb->query($q);
            while ($value = $ucdb->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>$value['to'],
                        'nick'=>$value['nick'],
                        'from'=>$value['from'],
                        'style'=>$value['style'],
                        'body'=>$value['body'],
                        'type' => $value['type'],
                        'timestamp'=>$value['timestamp']));
            }
        }

    }
    return $list;
}

function nick($sp){
    global $_IMC;
    $_nick=(!$_IMC['show_realname']||empty($sp['name'])) ? $sp['username'] : $sp['name'];
    return to_utf8($_nick);
}

function tname($name) {
    global $tablepre;
    return $tablepre.$name;
}

 function im_tname($name) {
// return "`webim_".$name."`";
    return UC_DBTABLEPRE."webim_".$name;
}


if( !function_exists('avatar') ) {
function avatar($uid, $size='small') {
		return UC_API.'/avatar.php?uid='.$uid.'&size='.$size;
}
}
function online_buddy() {
    global $user, $ucdb,$_SGLOBAL;
    $list = array();
    $buddies=array();
    $q=$ucdb->query("SELECT f.uid,f.friendid, m.username FROM ".UC_DBTABLEPRE."friends f LEFT JOIN ".UC_DBTABLEPRE."members m ON f.friendid=m.uid  WHERE f.uid='$user->uid'");
    while ($value =$ucdb->fetch_array($q)) {
        $id=$value['friendid'];
        $buddies[$id]=$value;
    }
    if(!empty($buddies)){
    $ids=join(",",(array_keys($buddies)));
    $query = $_SGLOBAL['db']->query("SELECT uid,username,groupid FROM ". tname('sessions')." where  uid IN ($ids)");
    while ($v =$_SGLOBAL['db']->fetch_array($query)) {
        $list[] = (object)array(
                        "uid" => $v['uid'],
                        "id" => $v['username'],
                        "nick" => $v['username'],
//                        "group" => $groups[$v['groupid']],
                        "url" => "space.php?uid=".$v['uid'],
                        'default_pic_url' => UC_API.'/images/noavatar_small.gif',
                        "pic_url" => avatar($v['uid'], 'small'),
        );

    }
    }
    return $list;
}
