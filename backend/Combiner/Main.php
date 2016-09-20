<?php
namespace Kontextful\Backend\Combiner;

class Main {
	private $amqp;
	private $redis;
	const wait_time = 10;
	private $wait = array();
	
	public function __construct() {
		$this->amqp = \AmqpSingleton::getInstance();
		$this->redis = \Redis::getInstance();
	}
	
	public function run() {
		while(true) {
			//$this->process_jobs($this->check_db());
			\KSimpleLogger::log("Running in the loop");
			$this->process($this->check_db());
			sleep(self::wait_time);
		}
	}
	
	private function check_db() {
		return $this->redis->smembers("jobs");
	}
	
	private function retrieve($job_id, $step) {
		return $this->redis->lrange($job_id."-completed-".$step, 0, -1);
	}
	
	private function calculate_next_step($array) {
		return array_sum($array);
	}
	
	private function process($jobs) {
		
		\KSimpleLogger::log("Jobs are ".print_r($jobs, true));
		
		foreach($jobs as $job_id) {
			
			\KSimpleLogger::log("Processing job_id ".$job_id);
			
			$fetch = $this->retrieve($job_id, "fetch");
			if(($fetch)==null)
				continue;
			$next_val = array_sum($fetch) * 3; // *3 special to this step
			
			\KSimpleLogger::log("Fetch done ".$job_id);
			\KSimpleLogger::log("Structure should be ". $next_val." ".$job_id);
			
			$structure = $this->retrieve($job_id, "structure");
			\KSimpleLogger::log("Actual Structure: ".count($structure)." ".$job_id);
			
			if(($structure)==null)
				continue;
			if(count($structure)<$next_val)
				continue;
			$next_val = array_sum($structure); 
			
			\KSimpleLogger::log("Structure done ".$job_id);
			\KSimpleLogger::log("Tokenize should be ". $next_val." ".$job_id);
			
			$tokenize = $this->retrieve($job_id, "tokenize");
			\KSimpleLogger::log("Actual Tokenize: ".count($tokenize)." ".$job_id);
			
			if(($tokenize)==null)
				continue;
			if(count($tokenize)<$next_val)
				continue;
			$next_val = array_sum($tokenize);
			
			\KSimpleLogger::log("Structure done ".$job_id);
			\KSimpleLogger::log("Augment should be ". $next_val." ".$job_id);
			
			$augment = $this->retrieve($job_id, "augment");
			\KSimpleLogger::log("Actual Augment: ".count($augment)." ".$job_id);
			
			if(($augment)==null)
				continue;
			if(count($augment)<round($next_val*COMPLETION_ROUNDING_ERROR))
				continue;
			$next_val = array_sum($augment);
			
			\KSimpleLogger::log("Augment done ".$job_id);
			\KSimpleLogger::log("Score should be ". $next_val." ".$job_id);
			
			$score = $this->retrieve($job_id, "score");
			\KSimpleLogger::log("Actual Score: ".count($score)." ".$job_id);
			
			if(($score)==null)
				continue;
			if(count($score)<round($next_val*COMPLETION_ROUNDING_ERROR))
				continue;
			
			\KSimpleLogger::log("Score done ".$job_id);
			
			if(!isset($this->wait[$job_id])) {
				$this->wait[$job_id] = time() + WAIT_TIME;
				\KSimpleLogger::log("Wait a bit... for like ".($this->wait[$job_id]-time())." secs.");
				continue;
			}
			else if(time() < $this->wait[$job_id] && count($score)<$next_val /* if it's 100% don't wait */) {
				\KSimpleLogger::log("Wait a bit... for like ".($this->wait[$job_id]-time())." secs.");
				continue;
			}
			
			\KSimpleLogger::log("No more waiting...");
			
			unset($this->wait[$job_id]);
			
			$x = explode("-",$job_id);
			$msg = $this->amqp->createMessage(
					json_encode(array(
						"uid"=>$x[0],
						"service"=>$x[1],
						"session_id"=>$x[2],
					))
			);
			$this->amqp->publish($msg, "normalize");
			
			$this->redis->srem("jobs", $job_id);
			
		}
	}
	
	
	private function process_jobs($jobs) {
		foreach($jobs as $job_id) {
			// job_id be global key, so user_id-service-access_token
			$job = new Job($job_id);
			$this->eval_report($job->report());
		}
	}
	
	private function eval_report($report) {
		// ....
	}
	
}