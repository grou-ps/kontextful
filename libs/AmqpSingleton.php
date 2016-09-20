<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpSingleton {

    private static $instance = null;
    private $conn; // connection
    private $ch; // channel
    private $queue; // AMQP_QUEUE

    private function __construct() {
    	$durable=true;
        $this->conn = new AMQPConnection(AMQP_HOST, AMQP_PORT, AMQP_USER, AMQP_PASS, AMQP_VHOST);
		$this->ch = $this->conn->channel();
		
		if($durable) {
			$this->ch->exchange_declare(AMQP_EXCHANGE, 'direct', false, true, true);
			list($this->queue,,) = $this->ch->queue_declare('', false, true, false, true);
		}
		else {
			// not durable
			$this->ch->exchange_declare(AMQP_EXCHANGE, 'direct', false, false, false);
			list($this->queue,,) = $this->ch->queue_declare('', false, false, false, false);
		}
		
		
    }

    private function __clone() {}

    public static function getInstance() {
        if(!is_object(self::$instance))
            self::$instance = new AmqpSingleton();
        return self::$instance;
    }
    
    public static function getNewInstance() {
    	return new AmqpSingleton();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getChannel() {
        return $this->ch;
    }
    
    public function close() {
    	$this->ch->close();
    	$this->conn->close();
    }
    
    public function createMessage($msg_body) {
    	$msg = new AMQPMessage($msg_body,
    			array(
    					'content_type' => 'text/plain', 
    					'delivery_mode' => 2
    			)
    		);
    	return $msg;
    }
    
    public function publish($msg, $worker) {
    	$this->ch->basic_publish($msg, AMQP_EXCHANGE, $worker);
    }
    
    public function consume($callback_function, $workers) {
    	$consumer_tag = 'consumer'. getmypid (); // who am i, in case of multiple consumers.

    	// $workers = unserialize(AMQP_WORKERS);
    	foreach($workers as $worker) {
    		//echo $worker.PHP_EOL;
    		$this->ch->queue_bind($this->queue, AMQP_EXCHANGE, $worker);
    	}
    	$this->ch->basic_qos(null, 1, null); // for fair dispatching
    	$this->ch->basic_consume($this->queue, $consumer_tag, false, false, false, false, $callback_function);
    	
    	// Loop as long as the channel has callbacks registered
    	while (count($this->ch->callbacks)) {
    		$this->ch->wait();
    	}
    }
    
    public function registerShutdown() {
    	register_shutdown_function('amqp_shutdown', $this);
    }
    
}

function amqp_shutdown($amqp) {
	$amqp->close();
}
