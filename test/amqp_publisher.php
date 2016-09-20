<?php
include_once(__DIR__.'/../configs/globals.php');
include_once(__DIR__.'/../vendor/autoload.php');
include('../libs/AmqpSingleton.php');

$amqp = AmqpSingleton::getInstance();

$msg_body = implode(' ', array_slice($argv, 2));
$msg = $amqp->createMessage($msg_body);

$amqp->publish($msg,$argv[1]);


$amqp->close();
