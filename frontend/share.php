<?php
session_start();
header('Content-Type: application/json');

include(__DIR__.'/Auth.class.php');
include_once(__DIR__.'/../vendor/autoload.php');

function die_error($msg, $code=1) {
	die(json_encode(array(
			"error"=>$code,
			"msg"=>ucfirst($msg)
	)
	));
}

$db=null;
function connect_db() {
	global $db;
	if(is_object($db)) {
		return $db;
	}
	else {
		try {
			# MySQL with PDO_MYSQL
			$db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB, MYSQL_USER, MYSQL_PASS);
		}
		catch(\PDOException $e) {
			die( $e->getMessage());
		}
		return $db;
	}
}

if(!isset($_SESSION['uid'])) {
	die_error("not logged in", 2);
}


$facebook = new Facebook(array(
		'appId'  => FACEBOOK_APPID,
		'secret' => FACEBOOK_SECRET,
		'fileUpload' => false, // optional
		'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
	)
);


$facebook->setAccessToken($_SESSION['access_token']);

$user = $facebook->getUser();

if (!$user) {
	die_error("your access token may have expired. please log in at http://kontextful.com/login.hh and reauthorize this app.", 2);
}


if(isset($_POST['context_type']) && $_POST['context_type'] == "facebookgroups") {
	if($_POST['context_id']!=0) {
		$response = $facebook->api(
				"/{$_POST['context_id']}/feed",
				"POST",
				array (
						'message' => $_POST['comments'],
						'link' => $_POST['url'],
						'name'=> $_POST['title']
				)
		);
	}
	else {
		$response = $facebook->api(
				"/me/feed",
				"POST",
				array (
						'message' => $_POST['comments'],
						'link' => $_POST['url'],
						'name'=> $_POST['title'],
						'value'=>'ALL_FRIENDS'
				)
		);
	}
	
	return json_encode(array("result"=>$response));
}

die_error("please define an action");