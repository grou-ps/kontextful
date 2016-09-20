<?php
namespace Kontextful\Backend\Elements;
class Unit extends Element {
	
	private function is_augmented() {
		
	} 
	
	
	public function process() {
		if($this->is_augmented()) {
			$subunits = $this->get_subunits();
			foreach($subunits as $subunit_id) {
				// context_id will be like facebookgroup:context_id
				$subunit = new Subunit($subunit_id);
				$subunit->process();
			}
		}
		else {
			$this->mark_lookup();
		}
	}
	
}

class Subunit {
	public function check_percentile_completeness() {
		
	}
}