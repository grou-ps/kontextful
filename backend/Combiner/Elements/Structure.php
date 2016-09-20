<?php
namespace Kontextful\Backend\Elements;
class Structure extends Element {
	
	private function is_tokenized() {
		
	} 
	
	
	public function process() {
		if($this->is_tokenized()) {
			$units = $this->get_units();
			foreach($units as $unit_id) {
				// context_id will be like facebookgroup:context_id
				$unit = new Unit($structure_id);
				$unit->process();
			}
		}
		else {
			$this->mark_lookup();
		}
	}
	
}