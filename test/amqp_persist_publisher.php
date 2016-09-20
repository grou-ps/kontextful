<?php
include_once(__DIR__.'/../configs/globals.php');
include_once(__DIR__.'/../vendor/autoload.php');
include('../libs/AmqpSingleton.php');

$amqp = AmqpSingleton::getInstance();

//$msg_body = implode(' ', array_slice($argv, 2));

$msg_body = array("uid"=>500317633, "service"=>"facebook", "session_id"=>"2014050700350936b23cdf405970cd5e920ef6a9567870");

$msg = $amqp->createMessage(json_encode($msg_body));

$amqp->publish($msg,"normalize");


$amqp->close();
