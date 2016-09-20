<?php
/**
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 */


namespace Kontextful\Backend\Workers;

use \NlpTools\Stemmers\PorterStemmer;

class Stem extends Worker {
	
	private $stem_data = array();
	private $stemmer;
	private static $stemmable_strings = array('token');
	private static $stemmable_arrays = array('synonyms', 'hypernyms');
	
	public function __construct() {
		parent::__construct();
		$this->stemmer = new PorterStemmer();
		$this->set_this_step("stem");
		$this->set_next_step("score");
	}
	
	protected function _save_file() {
		if(LOG_DATA) {
			$stem_file = str_replace('.json',".stemmed.json", $this->params['raw_file']);
			file_put_contents($stem_file, json_encode($this->stem_data),  FILE_APPEND | LOCK_EX);
		}
		return $this;
	}
	
	public function process() {
		foreach($this->params['augmented_subunit'] as $key=>$token) {
			if(in_array($key, self::$stemmable_strings))
				$this->stem_data[$this->params['subunit_id']][$key] = $this->stemmer->stem($token);
			else if(in_array($key, self::$stemmable_arrays)) {
				$this->stem_data[$this->params['subunit_id']][$key] = array();
				foreach($token as $val) {
					$this->stem_data[$this->params['subunit_id']][$key][] = $this->stemmer->stem($val);
				}
			}
		}
		$this->do_next_step()/*->notify_final_step(array(
				"unit_id"=>$this->params['unit_id'],
				"subunit_id"=>$this->params['subunit_id']
		))*/;
	}
	
	protected function do_next_step() {
		unset($this->params['augmented_subunit']);
		parent::do_next_step(array("stemmed_subunit"=>$this->stem_data));
		return $this;
	}

	
}