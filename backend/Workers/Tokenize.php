<?php
/**
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 * @todo better stopwords, better clean up
 */

// or maybe return tokensets.
// group/token_sets/info/title.json
// group/token_sets/info/description.jsont
// group/token_sets/info/_metadata.json
// group/token_sets/feed/145/message.json
// group/token_sets/feed/145/_metadata.json {comments, likes}
// group/token_sets/feed/145/comments/7823/message.json
// group/token_sets/feed/145/comments/7823/_metadata.json {likes}
// group/token_sets/feed/145/message.json
// group/token_sets/feed/145/message.json

namespace Kontextful\Backend\Workers;

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use \NlpTools\Analysis\FreqDist;
// use \NlpTools\Stemmers\PorterStemmer;

class TokenizeException extends \Exception {}

class Tokenize extends Worker {
	
	private $tokenized_data; // to write into tokenized_file
	private $tokenizer;
	private $tokens; // tmp
	private $stemmer;
	private $unit_counter = 0;
	private $structure_id; // don't touch. because do_next_step will kill $this->param[..] before notify_last_step
	
	public function __construct() {
		parent::__construct();
		$this->set_this_step("tokenize");
		$this->set_next_step("augment");
		$this->tokenizer = new WhitespaceAndPunctuationTokenizer();
		// $this->stemmer = new PorterStemmer();
		$stopwords = file_get_contents(STOPWORDS_FILE);
		$this->stopwords = explode("\n",$stopwords);
		$this->unit_counter = $this->params['structure_offset'];
	}
	
	protected function do_next_step($unit, $key, $likes, $comments) {
		if(isset($this->params['raw_data'])) unset($this->params['raw_data']);
		unset($this->params['structured_data']);
		//unset($this->params['thread_offset']);
		//$thread_count=$this->params['unit_threads'];
		//unset($this->params['unit_threads']);
		// unset($this->params['thread_id']);
		parent::do_next_step(
					array(
							"unit_id" => $this->unit_counter++,
							//"unit_id" => $this->unit_counter+=$thread_count, 
							"tokenized_unit"=>$unit,
							"unit_key"=>$key,
							"unit_likes"=>$likes,
							"unit_comments"=>$comments
					)
		);
	}
	
	
	public function bind($params) {
		\KSimpleLogger::log("Tokenize params are: ");
		\KSimpleLogger::log(print_r($params, true));
		parent::bind($params);
		$this->structure_id = $this->params['structure_id']; //  because do_next_step will kill it before notify_last_step
		// $this->tokenized_data = $this->params['tokenized_data'];	
	}
	
	protected function _save_file() {
		if(LOG_DATA) {
			$tokenized_file = str_replace('.json',".tokenized.json", $this->params['raw_file']);
			file_put_contents($tokenized_file, json_encode($this->tokenized_data), LOCK_EX);
		}
		return $this;
	}
	
	public function process() {
		$this->bring_up_structured_data()->map()->_save_file();
		/*$this->notify_final_step(
				array(
						"total_units"=>$this->unit_counter,
						"structure_id"=>$this->structure_id // don't touch. because do_next_step will kill $this->param[..] before notify_last_step
		));*/
		$this->notify_final_step($this->unit_counter);
	}
	
	
	private function bring_up_structured_data() {
		if(PIPE_TYPE=='stdin') {
			return $this->bring_up_structured_data_with_stdin_as_input();
		}
		else {
			return $this->bring_up_structured_data_with_file_as_input();
		}
	}
	
	/**
	 * 
	 * @todo not sure the best performing implementation. could do an array_walk but it's associative.
	 * @return \Kontextful\Backend\Workers\Tokenize
	 */
	private function bring_up_structured_data_with_stdin_as_input() {
		$this->tokenized_data = json_decode(
			mb_strtolower(
					json_encode($this->params['structured_data'])
			)
				, true);
		unset($this->params['structured_data']); // for next steps
		return $this;
	}
	
	private function bring_up_structured_data_with_file_as_input() {
		$this->tokenized_data = json_decode(
			mb_strtolower(
					file_get_contents(
							$this->params['structured_file']
					)
			),
			true
		);
		unset($this->params['structured_data']); // for next steps
		return $this;
	}
	
	private function map(&$data="", $parent_key="") {
		if(empty($data))
			$data = & $this->tokenized_data;
		\KSimpleLogger::log("Map the following: ");
		\KSimpleLogger::log(print_r($data, true));
		foreach($data as $n=>$unit) {
			if(is_array($unit)) {
				foreach($unit as $key=>$val) {
					if(!is_array($val) && $key!="comments") {
						$data[$n][$key] = $this->process_unit($val, (empty($parent_key)?$key:$parent_key), $unit);
					}
					else if(sizeof($val)>0) {
						$this->map($data[$n][$key], $key);
					}
					else if(is_array($val)) { // that means it's already processed
						continue;
					}
				}
			}
			else {
				
				// context_title
				// context_description
				\KSimpleLogger::log("VERY VERY VERY IMPORTANT");
				\KSimpleLogger::log("VERY VERY $n IMPORTANT");
				\KSimpleLogger::log("VERY VERY $unit IMPORTANT");
				\KSimpleLogger::log("VERY VERY ".print_r($data, true)." IMPORTANT");
				$data[0][$n] = $this->process_unit($unit, $n, $data);
				
			}
			
		}
		return $this;
	}
	
	private function process_unit($val, $key, $extra_info) {
		\KSimpleLogger::log("Processing unit (Tokenize): " . print_r($val, true));
		$unit = $this->tokenize($val)->/*stem()->*/clean_up();
		$this->do_next_step($unit, 
								$key, 
									$extra_info['likes'], 
										(isset($extra_info['comments'])?count($extra_info['comments']):0)
		);
		return $unit;
	}
	
	private function tokenize($val) {
		\KSimpleLogger::log("Tokenize unit is: ");
		\KSimpleLogger::log(print_r($val, true));
		$this->tokens = $this->tokenizer->tokenize($val);
		return $this;
	}
	
	/*
	private function stem() {
		$this->tokens = $this->stemmer->stemAll($this->tokens);
		return $this;
	}
	*/
	
	private function clean_up() {
		$this->clean_up_stopwords()->clean_up_nonchars();
		return $this->tokens;
	}
	
	private function clean_up_stopwords() {
		$this->tokens = array_diff($this->tokens, $this->stopwords);
		return $this;
	}
	
	private function clean_up_nonchars() {
		$this->tokens = array_filter(
				$this->tokens,
				function($var){
					if(preg_match('/^[a-zA-Z]+$/',$var)||(is_numeric($var) && strlen($var)>=4)) 
						return true;
				}
		);
	}
	
}