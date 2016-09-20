<?php
class Redis {
	static private $instance;
	public $client;
	private function __construct() {
		$this->client = new \Predis\Client(array(
				'scheme' => 'tcp',
				'host'   => REDIS_HOST,
				'port'   => REDIS_PORT,
				));
	}
	static public function getInstance() {
		if(!is_object(self::$instance)) {
			self::$instance = new Redis();
		}
		return self::$instance;
	}
	
	public function __call($name, $arguments) {
    	return call_user_func_array(array($this->client, $name), $arguments);
    }

}

