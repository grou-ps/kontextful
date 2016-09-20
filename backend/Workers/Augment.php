<?php
/**
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 */


namespace Kontextful\Backend\Workers;

class Augment extends Worker {
	
	private $current_token;
	private $result_token = array();
	private $token_counter = 0;
	private $wordnet;
	
	private $tokens;
	
	public function __construct() {
		parent::__construct();
		$this->wordnet = \WordnetClient::getInstance();
		\KSimpleLogger::log("Augment constructed");
		$this->set_next_step("stem");
		$this->set_this_step("augment");
	}
	
	public function bind($params) {
		parent::bind($params);
		$this->tokens = $this->params['tokenized_unit'];
		unset($this->params['tokenized_unit']);
	}
	
	/** 
	 * will be called multiple times, that's why it appends.
	 * @return \Kontextful\Backend\Workers\Augment
	 */
	protected function _save_file() {
		if(LOG_DATA) {
			$augmented_file = str_replace('.json',".augmented.json", $this->params['raw_file']);;
			file_put_contents($augmented_file, json_encode($this->result_token) . PHP_EOL,  FILE_APPEND | LOCK_EX);
		}
		return $this;
	}
	
	public function process() {
		// \KSimpleLogger::log("Augment processing: ".print_r($this->params['tokenized_unit'],true));
		foreach($this->tokens as $token) {
			$this->set_token($token)->augment()->do_next_step(array(
					"augmented_subunit"=>$this->result_token[$this->token_counter],
					"subunit_id"=>$this->token_counter++
				));
		}
		$this->_save_file()->notify_final_step($this->token_counter);
		
		/*notify_final_step(array(
					"unit_id"=>$this->params['unit_id'],
					"total_subunits"=>$this->token_counter,
		));*/
	}
	
	private function set_token($token) {
		$this->current_token = $token;
		//$this->token_counter++;
		$this->save_val('token', $token);
		return $this;
	}
	
	private function augment() {
		$top_polysemy = $this->compute_polysemy();
		$this->get_synonyms($top_polysemy);
		$this->get_hypernyms($top_polysemy);
		//// $this->get_hyponyms(); // more broader, no need to look
		// $this->get_coordinates(); 
		//// $this->get_meronyms(); // coordinates have meronyms
		//// $this->get_holonyms(); // coordinates have holonyms
		return $this;
	}
	
	private function compute_polysemy() {
		$r = $this->wordnet->query("top_polysemy", $this->current_token);
		if(is_array($r)&&isset($r[0])&&!empty($r[0]))
			return $r[0];
		else return "";
	}
	
	private function get_synonyms($polysemy) {
		$this->save_val('synonyms',
							$this->wordnet->query("synonym", $this->current_token, $polysemy));
	}
	
	private function get_hypernyms($polysemy) {
		$this->save_val('hypernyms',
				$this->wordnet->query("hypernym", $this->current_token, $polysemy));
	}
	
	private function compute_polysemy_in_cmdline() {
		
		$process_polysemy = function($return) {
			if($return['result']!=0 || !is_array($return['output']) || count($return['output']) == 0) {
				return 0;
			}
			else {
				$output = implode('\n', $return['output']);
				if(preg_match("/polysemy count \= ([0-9])/", $output, $matches)) {
					return (int) $matches[1];
				}
				else {
					return 0;
				}
			}
		};
		
		$this->save_val('polysemy_as_verb', $process_polysemy($this->_exec("famlv", true))); // ./wn fun -famlv
		$this->save_val('polysemy_as_noun', $process_polysemy($this->_exec("famln", true))); // ./wn fun -famln
		$this->save_val('polysemy_as_adjective', $process_polysemy($this->_exec("famla", true))); // ./wn fun -famla
		$this->save_val('polysemy_as_rest', $process_polysemy($this->_exec("famlr", true))); // ./wn fun -famlr
		
	}
	
	private function save_val($key, $val) {
		$this->result_token[$this->token_counter][$key] = $val;
	}
	
	private function get_synonyms_in_cmdline() {
		$this->save_val('synonyms_as_verb', $this->_exec('synsv'));
		$this->save_val('synonyms_as_noun', $this->_exec('synsn'));
		$this->save_val('synonyms_as_adjective', $this->_exec('synsa'));
		$this->save_val('synonyms_as_rest', $this->_exec('synsr'));
	}
	
	private function get_hypernyms_in_cmdline() {
		$process_hypernym = function($return) {
			if($return['result']!=0 || !is_array($return['output']) || count($return['output']) == 0) {
				return null;
			}
			else {
				$final_return  = array();
				$output_allowed = true;
				foreach($return['output'] as $output) {
					if(preg_match("/^Sense [0-9]$/", trim($output))) {
						$allowed_output = true;
					}
					else if($output_allowed && strpos("=>",$output)!==false) {
						$_return = explode(',', trim(str_replace("=>","",$output)));
						$final_return  = array_merge($final_return, $_return);
						$output_allowed = false;
					}
				}
				array_walk( $final_return, function(&$value) { $value = trim($value); } );
				array_filter( $final_return, function($value) { return !(strpos(" ",$value)); } );
				return $final_return;
			}
		};
		
		$this->save_val('hypernyms_as_verb', $process_hypernym($this->_exec('hypev', true)));
		$this->save_val('hypernyms_as_noun', $process_hypernym($this->_exec('hypen', true)));
	}
	
	private function get_coordinates_in_cmdline() {
		$process_coordinates = function($return) {
			if($return['result']!=0 || !is_array($return['output']) || count($return['output']) == 0) {
				return null;
			}
			else {
				$final_return  = array();
				$output_allowed = true;
				foreach($return['output'] as $output) {
					if(strpos("=>",$output)!==false || strpos("->",$output)!==false) {
						$_return = explode(',', trim(str_replace(array("=>","->"),"",$output)));
						$final_return  = array_merge($final_return, $_return);
					}
				}
				array_walk( $final_return, function(&$value) { $value = trim($value); } );
				array_filter( $final_return, function($value) { return !(strpos(" ",$value)); } );
				return $final_return;
			}
		};
		$this->save_val('coordinates_as_verb', $process_coordinates($this->_exec('coorv', true)));
		$this->save_val('coordinates_as_noun', $process_coordinates($this->_exec('coorn', true)));
	}
	
	private function _exec($cmd, $custom_return=false) {
		$return = array();
		$output_lines = null;
		$return_var = -1;
		exec(
			escapeshellcmd(WORDNET_CMD).' '.escapeshellarg($this->current_token).' -'.escapeshellcmd($cmd), 
			$output_lines,
			$return_var
		);
		
		if($custom_return)
			return array("result"=>$return_var, "output"=>$output_lines);
		else {
			if( $return_var==0 && !is_null($output_lines) && is_array($output_lines)) { // means success
				foreach($output_lines as $output) {
					if(strpos("=>",$output)!==false) {
						$_return = explode(',', trim(str_replace("=>","",$output)));
						$return  = array_merge($return, $_return);
					}
				}
				array_walk( $return, function(&$value) { $value = trim($value); } );
				array_filter( $return, function($value) { return !(strpos(" ",$value)); } );
				return $return;
			}
			else return null;
		}
	}
}