<?php
namespace Kontextful\Backend\Workers;

class WorkerException extends \Exception {}

abstract class Worker {
	protected $amqp;
	protected $params;
	private $next_step;
	private $this_step;
	protected $redis;
	
	public function __construct() {
		$this->amqp = \AmqpSingleton::getInstance();
		$this->redis = \Redis::getInstance();
	}
	public function bind($params) {
		$this->params = $params;
	}
	abstract public function process();
	protected function ping_queue($work, $msg_body) {
		// \KSimpleLogger::log("queue is about to be pinged for: ".$work.", with: ".print_r($msg_body,true));
		$msg = $this->amqp->createMessage(
				json_encode($msg_body)
		);
        $this->amqp->publish($msg, $work);
        // $this->amqp->close(); // this will be called at the end of kontextfuld anyways
	}
	protected function do_next_step($params) {
		\KSimpleLogger::log("do next step is called with the following parameters: ".print_r($params,true));
		$params = array_merge($this->params, $params);
		$this->ping_queue($this->next_step, $params);
	}
	protected function set_next_step($step) {
		// \KSimpleLogger::log("next step is set to be: ".$step);
		$this->next_step = $step;
	}
	protected function set_this_step($step) {
		$this->this_step = $step;
	}
	
	protected function notify_final_step($subelements_count /*$params=array()*/ ) {
		
		$kparams = array();
		
		$kparams['uid'] = $this->params['uid'];
		$kparams['service'] = $this->params['service'];
		$kparams['session_id'] = $this->params['session_id'];
		$kparams['function'] = 'completed';
		$kparams['step'] = $this->this_step;
		$key = implode('-', $kparams);
		
		// $params['context'] = isset($this->params['context']) ? $this->params['context'] : "";
		// $params['structure_id'] = isset($this->params['structure_id']) ? $this->params['structure_id'] : "";
		
		$this->redis->rpush($key, $subelements_count);
	}
	
}