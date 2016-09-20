<?php
class WordnetClient {
	private static $instance = null;
	private $client;
	
	private function __construct() {
		register_shutdown_function('wordnet_client_shutdown', $this);
	}
	
	public function config($host, $port) {
		$this->client = stream_socket_client("tcp://$host:$port", $errno, $errorMessage);
		// stream_set_timeout($this->client, 0, 100000); // works
		// stream_set_timeout($this->client, 0, 50000); // works
		// stream_set_timeout($this->client, 0, 20000); // works
		// stream_set_timeout($this->client, 0, 10000); // works
		// stream_set_timeout($this->client, 0, 5000); // works
		stream_set_timeout($this->client, 0, 4000); // works
		// stream_set_timeout($this->client, 0, 2500); // aggressive 
		// stream_set_timeout($this->client, 0, 1); // practically 0, let's try. otherwise the augment worker is a real bottleneck for the whole topology.
	}
	
	static public function getInstance() {
		if(!is_object(self::$instance)) {
			self::$instance = new WordnetClient();
		}
		return self::$instance;
	} 
	
	public function query($function, $word, $type="") {
		if($type=="noun"||$type=="verb")
			fwrite($this->client, $function.' -'.$type.'- '.$word."\n");
		else
			fwrite($this->client, $function.' '.$word."\n");
		$res=trim(stream_get_contents($this->client));
		return explode("\n",$res);
	}
	
	public function close() {
		fclose($this->client);
	}
}

function wordnet_client_shutdown($wordnet_client) {
	$wordnet_client->close();
}