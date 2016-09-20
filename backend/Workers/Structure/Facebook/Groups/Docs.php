<?php

namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

class Docs extends StructureFacebookGroupsWorker {
	
	protected function set_structure_id() {
		$this->structure_id = 0;
	}
	
	/*
	public function process() {
		\KSimpleLogger::log("Processing the following file for Structure/Facebook/Groups/Docs: ".$this->raw_file);
		$this->structure()->do_next_step()->_save_file();
	}
	*/
	
	protected function structure() {
		if(PIPE_TYPE=="stdin")
			$data = $this->raw_data;
		else
			$data = $this->_decode_raw_file();
		foreach($data as $i=>$doc) {
			$this->structured_data[$i]['title'] = $doc['subject'];
			$this->structured_data[$i]['content'] = strip_tags($doc['message']);
			\KSimpleLogger::log("Structured data (Facbook Groups Docs) title is: ".$this->structured_data[$i]['title']);
		}
		return $this;
	}
	
	/*
	protected function structure_with_file_as_input() {
		$data = $this->_decode_raw_file();
		foreach($data as $i=>$doc) {
			$this->structured_data[$i]['title'] = $doc['subject'];
			$this->structured_data[$i]['content'] = strip_tags($doc['message']);
			\KSimpleLogger::log("Structured data (Facbook Groups Docs) title is: ".$this->structured_data[$i]['title']);
		}
		return $this;
	}
	*/
}