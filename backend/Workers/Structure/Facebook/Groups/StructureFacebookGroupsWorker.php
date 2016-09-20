<?php
/**
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 */


namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

// see backend/Workers/Fetch/Facebook, this is a copy of an exception with the same name
class ImpossibleFacebookQueryException2 extends \Exception {}

abstract class StructureFacebookGroupsWorker extends \Kontextful\Backend\Workers\Worker {
	
	const unit_structures = 3; // Docs, Feed, Info
	protected $structure_id;
	
	protected $raw_file;
	protected $raw_data;
	protected $structured_file;
	protected $structured_data = array();
	
	
	public function __construct() {
		parent::__construct();
		$this->set_this_step("structure");
		$this->set_next_step("tokenize");
	}
	
	public function bind($params) {
		parent::bind($params);
		$this->raw_data = $this->params['raw_data'];
		$this->raw_file = $this->params['raw_file'];
		$this->structured_file = str_replace('.json',".structured.json",$this->raw_file);
		\KSimpleLogger::log("raw file is: ".$this->raw_file);
		\KSimpleLogger::log("structured file is: ".$this->structured_file);
	}
	
	// abstract function process();
	// it already exists in Worker class.
	// we don't concrete here here, although most childs 
	// do the same thing with this function
	// BECAUSE: don't forget about Groups.php
	// it just doesn't process!
	
	// UPDATE Groups.php and Members.php will just overwrite this.
	
	public function process() {
		// \KSimpleLogger::log("Processing the following file for Structure/Facebook/Groups/Info: ".$this->raw_file);
		$this->set_structure_id();
		if(PIPE_TYPE=="stdin") {
			$this->structure()->do_next_step()->_save_file();
		}
		else {
			$this->structure()->_save_file()->do_next_step();
		}
		//$this->notify_final_step(array("structure_id"=>$this->structure_id, "total_structures"=>self::unit_structures)); // gave up this idea
		$this->notify_final_step(1); //self::unit_structures); // gave up this idea
	}
	
	abstract protected function set_structure_id();
	abstract protected function structure();
	
	protected function jsonize() {
		$this->structured_data = json_encode($this->structured_data);
		return $this;
	}
	
	protected function do_next_step() {
		// unset($this->params['raw_data']); // no overload
		if(PIPE_TYPE=="stdin") {
			parent::do_next_step(
					array(
							"structured_data"=>$this->structured_data,
							"structure_id"=>$this->structure_id
					)
				);
		}
		else {
			parent::do_next_step(
					array(
							"structured_file"=>$this->structured_file,
							"structure_id"=>$this->structure_id
					)
			);
		}
		return $this;
	}
	
	protected function _save_file() {
		if(LOG_DATA) {
			file_put_contents($this->structured_file, json_encode($this->structured_data), LOCK_EX);
		}
		return $this;
	}
	
	/**
	 * helper function: that's why it starts with _
	 * @return mixed
	 */
	protected function _decode_raw_file() {
		return $this->_decode_raw_data(
					file_get_contents($this->raw_file)
				);
	}
	
	protected function _decode_raw_data($data) {
		return json_decode($data, true);
	}
	

	
	
	
}