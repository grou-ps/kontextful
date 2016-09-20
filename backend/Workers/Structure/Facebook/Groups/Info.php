<?php
// do nothing

namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

class Info extends StructureFacebookGroupsWorker {
	
	/*
	public function process() {
		\KSimpleLogger::log("Processing the following file for Structure/Facebook/Groups/Info: ".$this->raw_file);
		$this->structure()->do_next_step()->_save_file();
	}
	*/
	
	protected function set_structure_id() {
		$this->structure_id = 2;
	}
	
	public function structure() {
		if(PIPE_TYPE=="stdin")
			$data = $this->raw_data;
		else
			$data = $this->_decode_raw_file();
		$this->structured_data['context_title'] = $data['name'];
		$this->structured_data['context_description'] = $data['description'];
		// $this->structured_data['tags'] = ""; // facebook doesn't give them out.
		return $this;
	}
	
	/*
	public function structure_with_file_as_input() {
		$data = $this->_decode_raw_file();
		$this->structured_data['context_title'] = $data['name'];
		$this->structured_data['context_description'] = $data['description'];
		// $this->structured_data['tags'] = ""; // facebook doesn't give them out.
		return $this; 
	} 
	*/
}
