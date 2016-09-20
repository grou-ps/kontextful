<?php 

namespace Kontextful\Backend\Workers;

class NormalizeException extends \Exception {}

class Normalize extends Worker {
	
	private $gkey;
	// private $redis;
	private $global_count_key = "";
	private $global_weight_key = "";
	private $count_key = "";
	private $weight_key = "";
	private $score_key = "";
	private $contexts_list_key = "";
	private $normalized_count_key = "";
	private $normalized_weight_key = "";
	
	const full_completion_rounding_error = 0.8;
	
	public function __construct() {
		parent::__construct();
		$this->set_this_step("normalize");
		$this->set_next_step("persist");
		// $this->redis = \Redis::getInstance();
	}
	
	public function bind($params) {
		parent::bind($params);
		$this->gkey = $this->params['uid'].'-'.$this->params['service'].'-'.$this->params['session_id'].'-';
		
		$this->contexts_list_key = $this->gkey.'contexts_list';
		// \KSimpleLogger::log("Contexts list key will be: ".$this->contexts_list_key);
		// $this->create_score_keys();
	}
	
	protected function do_next_step() {
		$uid = $this->params['uid'];
		$service = $this->params['service'];
		$session_id =$this->params['session_id'];
		//$raw_file = $this->params['raw_file'];
		$this->params = array(); // works better than unset, suppresses warning messages in the Worker class.
		parent::do_next_step(array(
								'completed'=>'normalize',
								'global_key'=>$this->gkey,
								'uid'=>$uid,
								'service'=>$service,
								'session_id'=>$session_id,
								// 'raw_file'=>$raw_file
		));
	}
	
	
	private function _get_lock_key() {
		return $this->gkey.'LOCKED';
	}
	
	private function is_locked() {
		return $this->redis->exists($this->_get_lock_key());
	}
	
	private function lock() {
		$this->redis->set($this->_get_lock_key(), 'Y');
	}
	
	
	public function process() {
		$this->normalize()->do_next_step();
	}
	
	/**
	 * the combine (reduction) process is now split.
	 * @throws NormalizeException
	 */
	public function _deprecated_process() {
		if(isset($this->params['completed'])) {
			// \KSimpleLogger::log("Completion message from: ".$this->params['completed']);
			call_user_func(array($this, 'complete_'.$this->params['completed']));
			//eval('$this->complete_'.$this->params['completed'].'();');
			if(!$this->is_locked() && $this->check_for_full_completion()) {
				$this->wait()->normalize()->do_next_step();
			}
		}
		else {
			throw new NormalizeException("There has to be a param called completed");
		}
	}
	
	private function create_score_keys($context) {
		$this->count_key = $this->gkey.$context.'-count';
		$this->weight_key = $this->gkey.$context.'-weight';
		$this->global_count_key = $this->gkey.$context.'-global_count';
		$this->global_weight_key = $this->gkey.$context.'-global_weight';
		$this->normalized_count_key = $this->gkey.$context.'-normalized_score';
		$this->normalized_weight_key = $this->gkey.$context.'-normalized_weight';
	}
	
	private function get_complete_key() {
		
		$key = "";
		$num_args = func_num_args();
		$arg_list = func_get_args();
		
		if($num_args<1) {
			throw NormalizeException("error in the get_complete_key function: 0 arg not allowed.");
		}
		else if($num_args==1)
			$key = $this->get_key('completed-'.$arg_list[0]); 
		else {
			array_unshift($arg_list, "completed");
			$args = implode('-', $arg_list);
			$key = $this->get_key($args);
		}
		
		return $key;
	}
	
	private function get_key() {
		$key = "";
		$num_args = func_num_args();
		$arg_list = func_get_args();
		if($num_args<1) {
			throw NormalizeException("error in the get_key function: 0 arg not allowed.");
		}
		else if($num_args==1)
			$key = $this->gkey.$arg_list[0];
		else {
			$args = implode('-', $arg_list);
			$key = $this->gkey.$args;
		}	
		return $key;
	}
	
	private function complete_fetch() {
		\KSimpleLogger::log("About to complete fetch...");
		\KSimpleLogger::log("fetch key is: ".$this->get_complete_key('fetch'));
		$this->redis->set($this->get_complete_key('fetch'), "Y");
		
		\KSimpleLogger::log("Contexts list is: ".print_r( $this->params['contexts_list'], true));
		
		\KSimpleLogger::log('$this->contexts_list_key => '.$this->contexts_list_key);
		
		foreach($this->params['contexts_list'] as $context) {
			$this->redis->sadd($this->contexts_list_key, $context);
		}
		//$this->redis->set($this->contexts_list_key, json_encode($this->params['contexts_list']));
		
		\KSimpleLogger::log("contexts list is as follows: ".print_r($this->params['contexts_list'], true));
	}
	
	
	private function complete_structure() {
		\KSimpleLogger::log("Complete STRUCTURE with key: ".$this->get_complete_key('structure', $this->params['context']));
		$this->redis->incr($this->get_complete_key('structure', $this->params['context']));
		\KSimpleLogger::log($this->redis->get($this->get_complete_key('structure', $this->params['context'])));
		$this->redis->set($this->get_key('total_structures', $this->params['context']), $this->params['total_structures']);
		\KSimpleLogger::log($this->get_key('total_structures', $this->params['context']));
		\KSimpleLogger::log($this->params['total_structures']);
	}
	
	private function complete_tokenize() {
		\KSimpleLogger::log("Complete tokenize called with the key: ".$this->get_complete_key('tokenize', $this->params['context'], $this->params['structure_id']));
		$this->redis->set($this->get_complete_key('tokenize', $this->params['context'], $this->params['structure_id']), 'Y');
		\KSimpleLogger::log("TOTAL UNITS KEY IS: ".$this->get_key('total_units', $this->params['context'])." ---- VALUE IS: ".$this->params['total_units']);
		$this->redis->set($this->get_key('total_units', $this->params['context'], $this->params['structure_id']), $this->params['total_units']);
	}
	
	private function complete_augment() {
		$this->redis->sadd($this->get_complete_key('augment', $this->params['context'], $this->params['structure_id']), $this->params['unit_id']);
		$this->redis->set($this->get_key('total_subunits', $this->params['context'], $this->params['structure_id'], $this->params['unit_id']), $this->params['total_subunits']);
	}
	
	private function complete_stem() {
		$this->redis->sadd($this->get_complete_key('stem', $this->params['context'], $this->params['structure_id'], $this->params['unit_id']), $this->params['subunit_id']);
	}
	
	private function complete_score() {
		$this->redis->sadd($this->get_complete_key('score', $this->params['context'], $this->params['structure_id'], $this->params['unit_id']), $this->params['subunit_id']);
	}
	
	
	private function _check_fetch() {
		\KSimpleLogger::log("Checking for full completion....");
		
		if($this->redis->get($this->get_complete_key('fetch')) === null) {
			\KSimpleLogger::log("Fetch not complete yet!");
			return false;
		}
		
		return true;
	}
	
	private function _check_structures($context) {
			\KSimpleLogger::log("Checking structures first");
			
			// check for structures first
			
			\KSimpleLogger::log("KEYS 1".$this->get_complete_key('structure', $context));
			\KSimpleLogger::log("KEYS 2".$this->get_key('total_structures', $context));
			
			$cur_structures = $this->redis->get($this->get_complete_key('structure', $context));
			$total_structures = $this->redis->get($this->get_key('total_structures', $context));
			if($total_structures < 1 || $cur_structures!=$total_structures) {
				\KSimpleLogger::log("Total structures: $total_structures , Current structures: $cur_structures");
				return -1; // let's return int, not boolean
			}
			
			\KSimpleLogger::log("structures are done.");
			\KSimpleLogger::log("Total structures: $total_structures , Current structures: $cur_structures");
			return $total_structures;
	}
	
	private function _check_tokenize_key($context, $structure_id) {
		if($this->redis->get($this->get_complete_key('tokenize', $context, $structure_id)) === null) {
			\KSimpleLogger::log("Tokenize not complete yet! Failed at {$context} in {$structure_id} structure id");
			\KSimpleLogger::log("Key was: ".$this->get_complete_key('tokenize', $context, $structure_id));
			return false;
		}
		\KSimpleLogger::log("Tokenize completed for: {$context} in {$structure_id} structure id");
		\KSimpleLogger::log("Key was: ".$this->get_complete_key('tokenize', $context, $structure_id));
		return true;
	} 
	
	
	private function check_for_full_completion() {
		
		$check = $this->_check_fetch();
		if(!$check) return false;
		
		\KSimpleLogger::log("Checking for all contexts");
		$contexts = $this->get_all_contexts();
			
		if($contexts === null) {
			\KSimpleLogger::log("Fetch complete, but contexts not set yet?? Weird!!");
			return false;
		}
		
		\KSimpleLogger::log("Checking for full completion contexts are: ".print_r($contexts, true));
		
		$total_units = array();
		$total_subunits = array();
		
		\KSimpleLogger::log("List of all contexts: ".print_r($contexts,true));
		
		foreach($contexts as $context) {
			
			\KSimpleLogger::log("Analyzing context: {$context}");
			
			if(($total_structures=$this->_check_structures($context))==-1) return false;
			
			$total_units[$context] = array();
			$total_subunits[$context] = array();
			
			for($structure_id=0;$structure_id<$total_structures;$structure_id++) {
			
				if(!$this->_check_tokenize_key($context, $structure_id)) return false;
					
				\KSimpleLogger::log("1TOTAL UNITS FOR {$context} in structure {$structure_id} with key  :".$this->get_key('total_units', $context, $structure_id)." ----- ");
				
				$total_units[$context][$structure_id] = $this->redis->get($this->get_key('total_units', $context, $structure_id));
				
				\KSimpleLogger::log("2TOTAL UNITS FOR {$context} in structure {$structure_id} with key ".$this->get_key('total_units', $context, $structure_id)."  :".$this->get_key('total_units', $context, $structure_id)." ----- ".$total_units[$context][$structure_id]);
				
				if(($total_units[$context][$structure_id]) === null) {
					\KSimpleLogger::log("Total units for $context - $structure_id not determined yet");
					return false;
				}
				
				\KSimpleLogger::log("Total units for $context - $structure_id : ".$total_units[$context][$structure_id]);
				
				if($total_units[$context][$structure_id]==0) {
					\KSimpleLogger::log("Total units for {$context} {$structure_id} is 0, so skipping a lot of things...");
				}
				else {
	
					$completed_augment_units = $this->redis->smembers($this->get_complete_key('augment', $context, $structure_id));
					\KSimpleLogger::log("Completed augment units for $context - $structure_id have reached:". count($completed_augment_units));
					if( count($completed_augment_units) <  round($total_units[$context][$structure_id]*self::full_completion_rounding_error) ) {
						\KSimpleLogger::log("Completed augment units have not reached the threshold yet.");
						return false;
					}
					\KSimpleLogger::log("Completed augment units for $context - $structure_id did reach the threshold");
					
					
					//$total_subunits[$context][$structure_id] = array();
					$total_subunits_x = 0;
					$completed_stem_subunits = 0;
					$completed_score_subunits = 0;
					
					for($unit_id=0;$unit_id<$total_units[$context][$structure_id];$unit_id++) {
						// $total_subunits[$context][$structure_id][$unit_id] += $this->redis->get($this->get_key('total_subunits',$context, $structure_id, $unit_id));
						$total_subunits_x += $this->redis->get($this->get_key('total_subunits',$context, $structure_id, $unit_id));
						/*if(is_null($total_subunits[$context][$structure_id][$unit_id])) {
							\KSimpleLogger::log("Total subunits for $context - $structure_id - $unit_id not determined yet");
							return false;
						}*/
						\KSimpleLogger::log("Total subunits for $context - $structure_id - $unit_id: ".$total_subunits[$context][$structure_id][$unit_id]);
						
						
						$completed_stem_subunits += count($this->redis->smembers($this->get_complete_key('stem',$context,$structure_id,$unit_id)));
						$completed_score_subunits += count($this->redis->smembers($this->get_complete_key('score',$context,$structure_id,$unit_id)));
					
						
					}
					
					\KSimpleLogger::log("Completed stem subunits for $context - $structure_id - $unit_id have reached: ". ($completed_stem_subunits) / ++$total_subunits[$context][$structure_id][$unit_id]*100 ."%");
					\KSimpleLogger::log("Completed stem score for  $context - $structure_id - $unit_id  have reached: ". ($completed_score_subunits) / ++$total_subunits[$context][$structure_id][$unit_id]*100 ."%");
					
					if(
					//$total_subunits[$context][$structure_id][$unit_id]*2
					$total_subunits_x*2
					<
					round(
							($completed_stem_subunits + $completed_score_subunits)
							*
							self::full_completion_rounding_error
					)
					) {
						\KSimpleLogger::log("Completed stem or score subunits have not reached the threshold yet!");
						return false;
					}
				}
			
			}
			
		}
		
		\KSimpleLogger::log("We are good to go baby!");
		
		$this->lock();
		
		return true;
		
	}
	
	private function wait() {
		
		\KSimpleLogger::log("Let's wait a bit!");
		\KSimpleLogger::log("Computing wait time...");
		
		$contexts = $this->get_all_contexts();
		$total_structures = $this->redis->get($this->get_key('total_structures', $this->params['context']));
		
		$completed_stem_subunits = 0;
		$completed_score_subunits = 0;
		$abs_total_subunits = 0;
		$total_subunits = array();
		foreach($contexts as $context) {
			\KSimpleLogger::log("in wait function for the context: ".$context);
			for($structure_id=0;$structure_id<$total_structures;$structure_id++) {
					$total_units = $this->redis->get($this->get_key('total_units', $context, $structure_id));
					for($unit_id=0; $unit_id<$total_units; $unit_id++) {
						$total_subunits = $this->redis->get($this->get_key('total_subunits', $context, $structure_id, $unit_id));
						$abs_total_subunits += $total_subunits;
						//for($subunit_id=0;$subunit_id<$total_subunits;$subunit_id++) {
							$completed_stem_subunits += count($this->redis->smembers($this->get_complete_key('stem',$context,$structure_id,$unit_id)));
							$completed_score_subunits += count($this->redis->smembers($this->get_complete_key('score',$context,$structure_id,$unit_id)));
						//}
					}
			}
		}
		
		
		\KSimpleLogger::log("completed stem subunits: ".$completed_stem_subunits);
		\KSimpleLogger::log("completed score subunits: ".$completed_score_subunits);
		\KSimpleLogger::log("completed total subunits: ".$abs_total_subunits);
		
		
		// $percentage =  round(($completed_stem_subunits + $completed_score_subunits) * self::full_completion_rounding_error ) / (++$abs_total_subunits*2) ;
		$percentage =  round(($completed_stem_subunits + $completed_score_subunits)) / (++$abs_total_subunits*2) ;
		$wait_time = WAIT_TIME*(self::full_completion_rounding_error/$percentage);
		
		\KSimpleLogger::log($percentage."% complete. Will wait for ".$wait_time." seconds");
		
		sleep($wait_time);
		return $this;
		
	}
	
	private function get_all_contexts() {
		\KSimpleLogger::log("Get all contexts function is called. The key will be: ".$this->contexts_list_key);
		return $this->redis->smembers($this->contexts_list_key);
	}
	
	private function normalize() {
		// context'leri cek
		// her biri icin:
		$contexts = $this->get_all_contexts();
		foreach($contexts as $context) {
			$this->create_score_keys($context);
			$this->normalize_counts();
			$this->normalize_weights();
		}
		return $this;
	}
	
	
	private static function calculate_score($score, $total_score) {
		$res = $score / $total_score;
		return ($res > 1) ? 1 : $res;
	}
	
	private function normalize_counts() {
		
		\KSimpleLogger::log("Normalizin counts");
		\KSimpleLogger::log("Count key: ". $this->count_key);
		\KSimpleLogger::log("Total count key: ". $this->global_count_key);
		
		$counts = $this->redis->hgetall($this->count_key);
		$total_count = $this->redis->get($this->global_count_key);
		
		\KSimpleLogger::log("Total count: ". $total_count);
		\KSimpleLogger::log("Counts: ". print_r($counts,true));
		
		foreach($counts as $key=>$count) {
			$this->redis->hset($this->normalized_count_key, 
									$key, self::calculate_score($count, $total_count));
		}
		
		\KSimpleLogger::log("Normalized key will be : ". $this->normalized_count_key);
		
	}
	
	private function normalize_weights() {
		
		\KSimpleLogger::log("Normalizin weights");
		\KSimpleLogger::log("Weight key: ". $this->weight_key);
		\KSimpleLogger::log("Total weight key: ". $this->global_weight_key);
		
		$weights = $this->redis->hgetall($this->weight_key);
		$total_weight = $this->redis->get($this->global_weight_key);
		
		\KSimpleLogger::log("Total weight: ". $total_weight);
		\KSimpleLogger::log("Weights: ". print_r($weights,true));
		
		foreach($weights as $key=>$weight) {
			$this->redis->hset($this->normalized_weight_key, 
									$key, self::calculate_score($weight, $total_weight));
		}
		
		\KSimpleLogger::log("Normalized key will be : ". $this->normalized_weight_key);
	}
	
	public function __call($method, $args) {
		if(substr($method, 0, strlen("complete_"))=="complete_") {
			\KSimpleLogger::log("Impossible Completed Param: ". $method);
			throw new NormalizeException("Impossible Completed Param: ". $method);
		}
	}
	
}