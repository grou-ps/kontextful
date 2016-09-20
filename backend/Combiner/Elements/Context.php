<?php
namespace Kontextful\Backend\Elements;
class Context extends Element {
	
	protected function get_key() {
		return $this->job_id."-completed-structure";
	}
	
	private function is_structured() {
		$x = $this->redis->smembers($this->get_key());
		foreach($x as $y) {
			if(strpos($y, "\"context\":\"".$this->id."\"")!==false)
				return true;
		}
		return false;
	} 
	
	
	public function report() {
		$report = $this->create_report()->make_subelements("structure");
		if($this->is_structured()) {
			$total_structures = 3; // constant
			for($i=0;$i<$total_structures;$i++) {
				// context_id will be like facebookgroup:context_id
				$structure = new Structure($i);
				$report->add_subelement($structure->report());
			}
		}
		else {
			$this->mark_lookup();
		}
	}
	
}