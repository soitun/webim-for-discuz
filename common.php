<?php
require_once('../../include/common.inc.php');
require_once('lib/webim.class.php');
require_once('lib/json.php');
require_once('config.php');

if(!$discuz_uid)exit('Login at first.');
$_SGLOBAL['supe_uid']=  $discuz_uid;
$_SGLOBAL['db']= $db;
$_SC['charset'] = UC_CHARSET;

$ucdb = new dbstuff;
$ucdb->charset = UC_DBCHARSET;
$ucdb->connect(UC_DBHOST, UC_DBUSER, UC_DBPW, UC_DBNAME);

if( !function_exists('getspace') ) {
    function getspace($uid) {
        global $db;
        $db->query("SET NAMES ". UC_DBCHARSET);
        $space = $db->fetch_first("SELECT username,gender,nickname FROM "
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
    global $_SGLOBAL,$_IMC,$space, $ucdb;
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
$user->id = to_utf8($space['username']);
$user->nick = to_utf8($space['username']);
$user->pic_url = user_pic($user->uid);
$user->show = gp('show') ? gp('show') : "unavailable";
$user->url = "space.php?uid=".$user->uid;

function to_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv($_SC['charset'],'utf-8',$s);
    }
}
////
////function from_utf8($s) {
////    global $_SC;
////    if($_SC['charset'] == 'utf-8') {
////        return $s;
////    } else {
////        return  _iconv('utf-8',$_SC['charset'],$s);
////    }
////}
////
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
function im_tname($name) {
    return UC_DBTABLEPRE."webim_".$name;
}

function build_buddies($buddies) {
    $_buddies = array();
    foreach($buddies as $b)
        $_buddies[]=array('id'=>$b->id,'show'=>$b->show,'need_reload'=>true,'presence'=>$b->presence);
    return $_buddies;
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
    global $_SGLOBAL,$_IMC, $groups,$space;
    $ids = ids_array($ids);
    $ids = ids_except($space['username'], $ids);
    if(empty($ids))return array();
    $ids = join("','", $ids);
    $buddies = array();
    $_SGLOBAL['db']->query("SET NAMES " . UC_DBCHARSET);
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
        $buddies[]=array('id'=>$id,
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
    global $_SGLOBAL,$_IMC,$space, $ucdb;
    $uname = $space['username'];
    $messages = array();
    $ucdb->query("SET NAMES " . UC_DBCHARSET);
    $query = $ucdb->query("SELECT * FROM "
            .im_tname('histories')
            ." WHERE `to`='$uname' and send = 0 ORDER BY timestamp DESC LIMIT 100");
    while ($value = $ucdb->fetch_array($query)) {
        array_unshift($messages,array('to'=>$value['to'],
                'nick'=>$value['nick'],
                'from'=>$value['from'],
                'style'=>$value['style'],
                'body'=>to_utf8($value['body']),
                'timestamp'=>$value['timestamp'],
                'type' =>$value['type']));
    }
    return $messages;
}

function new_message_to_histroy() {
    global $_SGLOBAL,$_IMC,$space, $ucdb;
    $uname = $space['username'];
//    var_dump("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$uname' AND send = 0");
    $ucdb->query("UPDATE "
            .im_tname('histories')
            ." SET send = 1 WHERE `to`='$uname' AND send = 0");
}

function find_history($ids,$type="unicast") {
    global $_SGLOBAL,$_IMC,$space, $ucdb;
    $ucdb->query("SET NAMES " . UC_DBCHARSET);
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
                        array('to'=>to_utf8($value['to']),
                        'from'=>to_utf8($value['from']),
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'timestamp'=>$value['timestamp'],
                        'type' =>$value['type'],
                        'nick'=>to_utf8($value['nick'])));
            }
        }else{
            $q=  "SELECT main.* FROM "
                    . im_tname('histories')
                    . " main WHERE (`send`=1) AND ((`to`='$id' AND `from`='$uname' AND `fromdel` != 1) or (`from`='$id' AND `to`='$uname' AND `todel` != 1))  ORDER BY timestamp DESC LIMIT 30";
            $query = $ucdb->query($q);
            while ($value = $ucdb->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>to_utf8($value['to']),
                        'nick'=>to_utf8($value['nick']),
                        'from'=>to_utf8($value['from']),
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
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
    return to_unicode(to_utf8(($_nick)));
}

function tname($name) {
    global $tablepre;
    return $tablepre.$name;
}
