<?php

/**
 * WebIM PHP Lib
 *
 * Author: Hidden <zzdhidden@gmail.com>
 * Date: Mon Aug 23 15:15:41 CST 2010
 *
 *
 * @TODO: join, leave
 */

require_once('util.php');
require_once('http_client.php');

class WebIM
{

	var $user;
	var $domain;
	var $apikey;
	var $host;
	var $port;
	var $client;
	var $ticket;

	/**
	 * New
	 *
	 * @param object $user
	 * 	-id:
	 * 	-nick:
	 * 	-show:
	 * 	-status:
	 *
	 * @param string $ticket
	 * @param string $domain
	 * @param string $apikey
	 * @param string $host
	 * @param string $port
	 *
	 */

	function WebIM($user, $ticket, $domain, $apikey, $host, $port = 8000){
		$this->user = $user;
		$this->domain = $domain;
		$this->apikey = $apikey;
		$this->ticket = $ticket;
		$this->host = $host;
		$this->port = $port;
		$this->client = new HttpClient($this->host, $this->port);
	}

	/**
	 * Join room.
	 *
	 * @param string $room_id
	 *
	 * @return object room_info
	 * 	-id
	 * 	-count
	 */

	function join($room_id){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'nick' => $this->user->nick,
			'room' => $room_id,
		);
		$this->client->post('/room/join', $data);
		$cont = $this->client->getContent();
		if($this->client->status == "200"){
			$da = json_decode($cont);
			return (object)array(
				"id" => $room_id,
				"count" => $da ->{$room_id},
			);
		}else{
			return null;
		}
	}

	/**
	 * Leave room.
	 *
	 * @param string $room_id
	 *
	 * @return ok
	 *
	 */

	function leave($room_id){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'nick' => $this->user->nick,
			'room' => $room_id,
		);
		$this->client->post('/room/leave', $data);
		return $this->client->getContent();
	}

	/**
	 * Get room members.
	 *
	 * @param string $room_id
	 *
	 * @return array $members
	 * 	array($member_info)
	 *
	 */

	function members($room_id){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'room' => $room_id,
		);
		$this->client->get('/room/members', $data);
		$cont = $this->client->getContent();
		if($this->client->status == "200"){
			$da = json_decode($cont);
			return $da ->{$room_id};
		}else{
			return null;
		}
	}

	/**
	 * Send user chat status to other.
	 *
	 * @param string $to status receiver
	 * @param string $show status
	 *
	 * @return ok
	 *
	 */

	function status($to, $show){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'nick' => $this->user->nick,
			'to' => $to,
			'show' => $show,
		);
		$this->client->post('/statuses', $data);
		return $this->client->getContent();
	}

	/**
	 * Send message to other.
	 *
	 * @param string $type unicast or multcast or boardcast
	 * @param string $to message receiver
	 * @param string $body message
	 * @param string $style css
	 *
	 * @return ok
	 *
	 */

	function message($type, $to, $body, $style=""){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'nick' => $this->user->nick,
			'type' => $type,
			'to' => $to,
			'body' => $body,
			'style' => $style,
			'timestamp' => (string)microtime(true)*1000,
		);
		$this->client->post('/messages', $data);
		return $this->client->getContent();
	}


	/**
	 * Send user presence
	 *
	 * @param string $show
	 * @param string $status
	 *
	 * @return ok
	 *
	 */

	function presence($show, $status = ""){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain,
			'nick' => $this->user->nick,
			'show' => $show,
			'status' => $status,
		);
		$this->client->post('/presences/show', $data);
		return $this->client->getContent();
	}


	/**
	 * User offline
	 *
	 * @return ok
	 *
	 */

	function offline(){
		$data = array(
			'ticket' => $this->ticket,
			'apikey' => $this->apikey,
			'domain' => $this->domain
		);
		$this->client->post('/presences/offline', $data);
		return $this->client->getContent();
	}

	/**
	 * User online
	 *
	 * @param string $buddy_ids
	 * @param string $room_ids
	 *
	 * @return object
	 * 	-success: true
	 * 	-connection:
	 * 	-user:
	 * 	-buddies: [&buddyInfo]
	 * 	-rooms: [&roomInfo]
	 * 	-error_msg:
	 *
	 */
	function online($buddy_ids, $room_ids){
		$data = array(
			'rooms'=> $room_ids, 
			'buddies'=> $buddy_ids, 
			'domain' => $this->domain, 
			'apikey' => $this->apikey, 
			'name'=> $this->user->id, 
			'nick'=> $this->user->nick, 
			'show' => $this->user->show
		);
		$this->client->post('/presences/online', unicode_val($data));
		$cont = $this->client->getContent();
		$da = json_decode($cont);
		if($this->client->status != "200" || empty($da->ticket)){
			return (object)array("success" => false, "error_msg" => $cont);
		}else{
			$ticket = $da->ticket;
			$this->ticket = $ticket;
			$buddies = array();
			foreach($da->buddies as $id => $show){
				$buddies[] = (object)array("id" => $id, "nick" => $id, "show" => $show, "presence" => "online");
			}
			$rooms = array();
			foreach($da->roominfo as $id => $count){
				$rooms[] = (object)array("id" => $id, "nick" => $id, "count" => $count);
			}
			$connection = (object)array(
				"ticket" => $ticket,
				"domain" => $this->domain,
				"server" => "http://".$this->host.":".(string)$this->port."/packets",
			);
			return (object)array("success" => true, "connection" => $connection, "buddies" => $buddies, "rooms" => $rooms, "server_time" => microtime(true)*1000, "user" => $this->user);
		}
	}

	/**
	 * Check the server is connectable or not.
	 *
	 * @return object
	 * 	-success: true
	 * 	-error_msg:
	 *
	 */

	function check_connect(){
		$data = array(
			'rooms'=> "", 
			'buddies'=> "", 
			'domain' => $this->domain, 
			'apikey' => $this->apikey, 
			'name'=> $this->user->id, 
			'nick'=> $this->user->nick, 
			'show' => $this->user->show
		);
		$this->client->post('/presences/online', unicode_val($data));
		$cont = $this->client->getContent();
		$da = json_decode($cont);
		if($this->client->status != "200" || empty($da->ticket)){
			return (object)array("success" => false, "error_msg" => $cont);
		}else{
			$this->ticket = $da->ticket;
			return (object)array("success" => true, "ticket" => $da->ticket);
		}
	}
}

?>
