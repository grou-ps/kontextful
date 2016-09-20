<?php
namespace Kontextful\Backend\Combiner\Elements;
abstract class Element {
	
	protected $id;
	protected $job_id;
	protected $type;
	protected $db;
	
	public function __construct($id, $job_id=null) {
		$this->id = $id;
		$this->job_id = is_null($job_id) ? $this->id : $job_id;
		$this->redis = \Redis::getInstance();
	}
	
	abstract public function report();
	
	abstract public function eval_report();
	
	abstract protected function mark_query();
	
	abstract protected function check_for_red_alert();
	
	protected function get_id() { 
		return $this->id;
	}
	
	protected function create_report($id=null) {
		if(is_null($id))
			return new Report($this->get_id(), get_class($this));
		else
			return new Report($id, get_class($this));
	}
	
}