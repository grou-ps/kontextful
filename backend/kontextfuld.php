<?php

namespace Kontextful\Backend;

include(__DIR__.'/../configs/globals.php');
include(__DIR__.'/../vendor/autoload.php');

include(__DIR__.'/../libs/AmqpSingleton.php');
include(__DIR__.'/../libs/KLogger.php');
include(__DIR__.'/../libs/WordnetClient.php');
include(__DIR__.'/../libs/Redis.php');

include('WorkerFactory.php');
include('Combiner/Main.php');



function process($msg) {
	try {
		$worker = WorkerFactory::build($msg->delivery_info['routing_key']);
	} catch(\Exception $e) {
		$msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
		\KLogger::log(
			get_class($e)." ".$e->getMessage()
			, "error");
	}
	if(isset($worker) /*&& is_subclass_of($worker,"Worker")*/) {
		$message = json_decode($msg->body, true);
		$worker->bind($message);
		try {
			$worker->process();
		}
		catch(\Exception $e) {
			\KLogger::log(
			"Process Error: ". get_class($e)." ".$e->getMessage()
			, "error");
		}
		$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
	}
}

function activate_autoload() {
	spl_autoload_register(function ($class) {
		$class = explode("\\",$class);
		if($class[0]=="Kontextful"&&$class[1]=="Backend") {
			unset($class[0]);
			unset($class[1]);
			$file = __DIR__ . '/' . implode('/', $class) .'.php';
			\KLogger::log("autoloading ".$file, "debug");
			if(file_exists($file)) {
				include $file;
				KLogger:log("autoloaded ".$file, "debug");
			}
		}
	
	});
}

function main($workers) {
	
	array_shift($workers);
	
	if(!isset($workers)||!is_array($workers)||count($workers)==0)
		$workers = array("fetch");
	
	activate_autoload();
	
	
	if(WORDNET_ENABLED) {
		$wordnet = \WordnetClient::getInstance();
		$wordnet->config(WORDNETD_HOST, WORDNETD_PORT);
	}
	
	if(REDIS_ENABLED) {
		$redis = \Redis::getInstance();
	}
	
	$amqp = \AmqpSingleton::getInstance();
	//$amqp = \AmqpSingleton::getNewInstance();
	
	$amqp->registerShutdown();
	
	if($workers[0]!="combine") {
		$amqp->consume('Kontextful\Backend\process', $workers);
	}
	else {
		\KSimpleLogger::log("Will run Combiner");
		$combiner = new \Kontextful\Backend\Combiner\Main();
		$combiner->run();
	}
}


main($argv);