<?php
include_once(__DIR__.'/../configs/globals.php');
include_once(__DIR__.'/../vendor/autoload.php');
include('../libs/AmqpSingleton.php');

$amqp = AmqpSingleton::getInstance();

echo "trying with uid: ".$argv[1].PHP_EOL;
echo " .. and access token: ".$argv[2].PHP_EOL;

$msg_body = array("uid"=>$argv[1], "service"=>"facebook", "access_token"=>$argv[2]);

$msg = $amqp->createMessage(json_encode($msg_body));

$amqp->publish($msg,"fetch");


$amqp->close();
