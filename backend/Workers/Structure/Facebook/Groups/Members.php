<?php
// this class is actually functioning, but we don't do anything.

namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

class Members extends StructureFacebookGroupsWorker {
	
	public function process() {
		\KSimpleLogger::log("Got into Structure/Facebook/Groups/Members, and we're doing nothing -- on purpose");
		return;
		// ignore the rest.
		\KSimpleLogger::log("Processing the following file for Structure/Facebook/Groups/Members: ".$this->raw_file);
		$this->structure()->do_next_step()->_save_file();
	}
	
	protected function structure() {
		foreach($this->raw_file as $i=>$member) {
			if(!isset($member['name']))
				continue;
			$this->structured_data[$i]['content'] = $member['name'];
		}
		return $this;
	}
	
	protected function structure_with_file_as_input() {
		$data = $this->_decode_raw_file();
		foreach($data as $i=>$member) {
			if(!isset($member['name']))
				continue;
			$this->structured_data[$i]['content'] = $member['name'];
		}
		return $this;
	}
	
	protected function set_structure_id() {}
	
}
