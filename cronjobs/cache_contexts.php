<?php
include(__DIR__."/../configs/globals.php");
try {
	$mem = new Memcache();
	$mem->connect('localhost', 11211);
	$db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB, MYSQL_USER, MYSQL_PASS);
	$contexts = $titles = array();
	$stmt = $db->prepare("SELECT user_id, service_id, title, (context_id) as cid FROM contexts");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$n = 0;
	while($row = $stmt->fetch()) {
		if(!isset($contexts[$row['user_id']])) {
			$contexts[$row['user_id']] = array();
		}
		$contexts[$row['user_id']][$n++] = $row['cid'];
		$mem->set("title.".$row['cid'], $row['title']);
		$mem->set("service_id.".$row['cid'], $row['service_id']);
	}
	
	foreach($contexts as $user_id=>$context) {
		$key = "contexts.".$user_id;
		$mem->set($key, $context);
	}
}
catch(\PDOException $e) {
	die( $e->getMessage());
}