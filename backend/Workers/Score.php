<?php

namespace Kontextful\Backend\Workers;

class Score extends Worker {
	
	// private $redis; // already taken care of in Worker
	private $gkey;
	private $global_count_key = "";
	private $global_weight_key = "";
	private $count_key = "";
	private $weight_key = "";
	private $weight = 0;
	private $contexts_list;
	private $max_weight = 0;
	private $max_count = 0;
	// params[unit_key]: context_title: 1000, context_description: 250, title: 100, content: 25, comment: 5
	private static $weight_table = 
				array("context_title"=>1000,
						"context_description"=>250,
							"title"=>100,
								"content"=>25,
									"comments"=>5);
	
	// params[unit_likes]: like: +2, comment: +5
	private static $reaction_weights = array('likes'=>2, 'comments'=>5);
	
	// key: token *10, hypernym *2, synonym *5
	private static $lexicality_weights = array("token"=>10, "synonyms"=>4, "hypernyms"=>2);
	
	public function __construct() {
		parent::__construct();
		$this->set_next_step("normalize");
		$this->set_this_step("score");
		// $this->redis = \Redis::getInstance(); //already taken care of in Worker
	}
	
	public function bind($params) {
		parent::bind($params);
		$this->gkey = $this->params['uid'].'-'.$this->params['service'].'-'.$this->params['session_id'].'-';
		$this->create_keys()->set_max();
	}
	
	private function set_max() {
		$this->max_weight = $this->redis->get($this->global_weight_key);
		$this->max_count = $this->redis->get($this->global_count_key);
	}
	
	
	private function form_contexts_list() {
		$this->redis->sadd($this->contexts_list, $this->params['context']);
	}
	
	
	public function process() {
		
		$this->form_contexts_list();
		
		foreach($this->params['stemmed_subunit'] as $gem) {
			$this->process_gem($gem);
		}
		
		$this->do_next_step();
	}
	
	protected function do_next_step() {
		unset($this->params['stemmed_subunit']);
		unset($this->params['unit_likes']);
		unset($this->params['unit_comments']);
		/*parent::do_next_step(array(
				"completed"=>"score"
		));*/
		$this->notify_final_step(1);
	}
	
	private function process_gem($gem) {
		foreach($gem as $key=>$x) {
			if($key=="token") {
				$this->score($key, $x);
			}
			else {
				$weight = $this->weigh($key);
				foreach($x as $word) {
					$this->score($key, $word, $weight);
				}
			}
		}
	}
	
	private function score($key, $word, $weight=null) {
		if(empty(trim($word))) 
			return; // don't do anything.
		$this->increment_count($word);
		\KSimpleLogger::log("SCORE WORD". $word);
		if(is_null($weight))
			$this->weigh($key)->save_weight($word);
		else 
			$this->save_weight($word);
	}
	
	
	/*
	 // we eliminate two functions below get_count_key(something) 
	 // and get_score_key(Something) because we switch to
	 // hashkeys in redis.
	private function get_count_key($word) {
		return $this->gkey.$word.'-count';
	}
	
	private function get_score_key($word) {
		return $this->gkey.$word.'-score';
	}
	*/
	
	private function create_keys() {
		$this->contexts_list = $this->gkey.'contexts_list';
		$this->count_key = $this->gkey.$this->params['context'].'-count';
		$this->weight_key = $this->gkey.$this->params['context'].'-weight';
		$this->global_count_key = $this->gkey.$this->params['context'].'-global_count';
		$this->global_weight_key = $this->gkey.$this->params['context'].'-global_weight';
		return $this;
	}
	
	private function increment_count($word) {
		/*$this->redis->incr(
						$this->get_count_key($word)
		);*/
		$val = $this->redis->hincrby($this->count_key, $word, 1);
		if($val > $this->max_count) {
			$this->max_count = $val;
			$this->redis->set($this->global_count_key, $val);
		}
	}
	
	// list
	private function save_weight($word) {
		\KSimpleLogger::log("SAVING SCORE FOR WORD". $word);
		$old_weight = (int) $this->redis->hget($this->weight_key, $word);
		\KSimpleLogger::log("WORD". $word. " OLD SCORE IS: ".$old_weight);
		\KSimpleLogger::log("WORD". $word. " NEW SCORE IS: ".$this->weight);
		if($this->weight > $old_weight) {
			$this->redis->hset($this->weight_key, $word, $this->weight);
			if($this->weight > $this->max_weight) {
				$this->max_weight = $this->weight;
				$this->redis->set($this->global_weight_key, $this->weight);
			}
		}
	}
	
	private function weigh($key) {
		// params[unit_key]: context_title: 1000, context_description: 250, title: 100, content: 25, comment: 5
		// params[unit_likes]: like: +2, comment: +5
		// key: token *10, meronym *2, synonym *4
		
		$this->reset_weight()
						->weigh_by_type()
									->weigh_by_reactions()
													->weigh_by_lexicality($key);
		return $this;
	}
	
	private function reset_weight() { 
		$this->weight = 0;
		return $this; 
	}
	
	private function weigh_by_type() {
		\KSimpleLogger::log("SCORE TYPE". $this->params['unit_key']);
		\KSimpleLogger::log("SCORE TYPE". self::$weight_table[$this->params['unit_key']]);
		$this->weight += self::$weight_table[$this->params['unit_key']];
		return $this;
	}
	
	private function weigh_by_reactions() {
		\KSimpleLogger::log("SCORE REACTIONS". self::$reaction_weights['likes']);
		\KSimpleLogger::log("SCORE REACTIONS". self::$reaction_weights['comments']);
		\KSimpleLogger::log("SCORE REACTIONS". $this->params['unit_likes']);
		\KSimpleLogger::log("SCORE REACTIONS". $this->params['unit_comments']);
		$this->weight += self::$reaction_weights['likes'] * $this->params['unit_likes'];
		$this->weight += self::$reaction_weights['comments'] * $this->params['unit_comments'];
		return $this;
	}
	
	private function weigh_by_lexicality($key) {
		\KSimpleLogger::log("SCORE LEXICALITY". $key);
		\KSimpleLogger::log("SCORE LEXICALITY". self::$lexicality_weights[$key]);
		$this->weight *= self::$lexicality_weights[$key];
	}
	
}
