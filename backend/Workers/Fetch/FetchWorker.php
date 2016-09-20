<?php
namespace Kontextful\Backend\Workers\Fetch;

abstract class FetchWorker extends \Kontextful\Backend\Workers\Worker {
	protected $db;
	public function __construct() {
		parent::__construct();
		$this->set_this_step("fetch");
		$this->set_next_step("structure");
	}
	
	public function bind($params) {
		parent::bind($params);
	}
	
	protected function set_session_id() {
		$this->generate_session_id()->session_cleanup()->add_job();
	}
	
	private function generate_session_id() {
		$now = new \DateTime("now", new \DateTimeZone("America/Los_Angeles"));
		$timestring = $now->format('YmdHis');
		$this->params['session_id'] = $timestring.md5($this->params['access_token']);
		return $this;
	}
	
	private function session_cleanup() {
		unset($this->params['access_token']); // cleanup
		return $this;
	}
	
	private function add_job() {
		$key = $this->params['uid'].'-'.$this->params['service'].'-'.$this->params['session_id'];
		$this->redis->sadd("jobs", $key);
	}
	
	abstract static protected function _name_context_for_notification($context);
}