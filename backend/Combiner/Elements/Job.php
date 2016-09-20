<?php
namespace Kontextful\Backend\Combiner\Elements;
class Job extends Element {
	

	protected function get_key() {
		return $this->job_id."-completed-fetch";
	}
	
	public function is_fetched() {
		$x = $this->redis->smembers($this->get_key());
		return !is_null($x) && count($x) >=1 ;
	}
	
	protected function mark_query() {
		$first_lookup_key = $this->id."-first_lookup-completed-fetch";
		$first_lookup_time = $this->redis->get($first_lookup_key);
		if(is_null($first_lookup_time)) {
			$this->redis->set($first_lookup_key, time());
		}
		else if(time()-$first_lookup_time > COMBINER_RED_ALERT_THRESHOLD) {
				$this->redis->hset("red_alerts", "fetch", $this->id);
		}
	}
	
	private function get_contexts() {
		return $this->redis->smembers($this->get_key());
	}
	
	
	public function report() {
		$report = $this->create_report()->make_subelements("context");
		if($this->is_fetched()) {
			$contexts = $this->get_contexts();
			foreach($contexts as $context_id) {
				// context_id will be like facebookgroup:context_id
				$context = new Context($context_id, $job_id=null);
				$report->add_subelement($context->report());
			}
		}
		else {
			$this->mark_query();
		}
		return $report;
	}
}