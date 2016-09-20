<?php
namespace Kontextful\Backend\Workers\Fetch;

class ImpossibleFacebookQueryException extends \Exception {}

class Facebook extends FetchWorker {
	
	private $facebook;
	private $parent_dir = "";
	
	private $raw_data;
	
	public function __construct() {
		parent::__construct();
		try {
			$this->facebook = new \Facebook(array(
					'appId'  => FACEBOOK_APPID,
					'secret' => FACEBOOK_SECRET,
					'fileUpload' => false, // optional
					'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
				)
			);
		}
		catch(\FacebookException $e) {
			throw $e;
		}
	}
	
	public function bind($params) {
		parent::bind($params);
		\KSimpleLogger::log("inside the Fetch/Facebook class with access token: ".$this->params['access_token']);
		try {
			$this->facebook->setAccessToken($this->params['access_token']);
		}
		catch(\FacebookException $e) {
			throw $e;
		}
	}
	
	public function process() {
		\KSimpleLogger::log("processing Fetch/Facebook");
		$this->set_session_id();
		$this->generate_dirs();
		$this->process_person();
		$this->process_groups();
		$this->notify_final_step();
	}
	
	protected function notify_final_step() {
		$contexts_list = array();
		foreach($this->db['groups_list'] as $context) {
			$contexts_list[] = $context['id'];
		}	
		$this->complete($contexts_list);
	}
	
	
	static protected function _name_context_for_notification($context) {
		return "facebook_groups:".$context;
	}
	
	protected function complete($contexts_list) {
		foreach($contexts_list as $i=>$context) {
			$contexts_list[$i] = self::_name_context_for_notification($context);
		}
		//parent::notify_final_step(array("contexts_list"=>$contexts_list));
		parent::notify_final_step(count($contexts_list));
	}
	
	private function generate_dirs() {
		$this->parent_dir = DATA_DIR. 'facebook' . DIRECTORY_SEPARATOR . $this->params['uid']. DIRECTORY_SEPARATOR .$this->params['session_id']; 
		\KSimpleLogger::log("parent dir is: {$this->parent_dir}");
		mkdir($this->parent_dir, 0777, true); // recursive
		\KSimpleLogger::log("parent dir created");
	}
	
	
	private function process_person() {
		\KSimpleLogger::log("about to process a person");
		$this->db['groups_list'] = $this->_query_big_data("me","groups");
		\KSimpleLogger::log("person's groups are: ".print_r($this->db['groups_list'],true));
		$file = $this->parent_dir. DIRECTORY_SEPARATOR . 'groups.json';
		$data = json_encode($this->db['groups_list']);
		$this->do_next_step($data, $file)->_save_as($data, $file);
	}
	
	protected function do_next_step($data, $filename, $group_id=-1 /* doesn't apply */) {
		if(PIPE_TYPE=='stdin') {
			parent::do_next_step(
									array(	
											"session_id"=>$this->params['session_id'],
											"raw_data"=>$data, 
											"raw_file"=>$filename,
											"root_dir"=>$this->parent_dir,
											"context"=>self::_name_context_for_notification($group_id),
									)
			);
		}
		else {
			parent::do_next_step(
					array(
							"session_id"=>$this->params['session_id'],
							"raw_file"=>$filename,
							"root_dir"=>$this->parent_dir,
							"context"=>self::_name_context_for_notification($group_id),
					)
			);
		}
		return $this;
	}
	
	
	protected function _save_as($data, $filename) {
		if(LOG_DATA) {
			file_put_contents($filename, json_encode($data) , LOCK_EX);
		}
	}
	
	
/*
	private function _processPerson() {
		$result_data = array();
		$raw_fetch = array();
		$offset = 0;
		$limit = 5000;
		do {
			echo $offset.PHP_EOL;
			$raw_fetch = $this->facebook->api("/me/groups/?limit={$limit}&offset={$offset}");
			print_r($raw_fetch);
			echo PHP_EOL;
			$result_data = array_merge($result_data, $raw_fetch['data']);	
			$offset += $limit;
		}
		while(isset($raw_fetch['paging']['next']));
		$this->db['groups_list'] = $result_data;
		//print_r($result_data);
		//echo PHP_EOL;
		file_put_contents($this->parent_dir.'/groups.json', json_encode($result_data), LOCK_EX );
		mkdir($this->parent_dir.'/groups');
	}
*/
	
	private function _get_group_dir($group_id) {
		return $this->parent_dir . DIRECTORY_SEPARATOR .'groups' . DIRECTORY_SEPARATOR . $group_id;
	}
	
	private function process_groups() {
		\KSimpleLogger::log("processing groups");
		mkdir($this->parent_dir. DIRECTORY_SEPARATOR .'groups');
		foreach($this->db['groups_list'] as $group) {
			\KSimpleLogger::log("processing group id: {$group['id']}");
			mkdir($this->_get_group_dir($group['id']));
			$this->process_group($group['id']);
		}
	}
	
	private function finish_processing($group_id, $type, $data) {
		$file = $this->_get_group_dir($group_id). DIRECTORY_SEPARATOR . $type. '.json';
		$this->do_next_step($data, $file, $group_id)->_save_as($data, $file);
	}
	
	private function process_group($group_id) {
		
		// @todo
		// maybe a cache check here
		// and then symlink
		
		$group_info = $this->facebook->api($group_id);
		$this->finish_processing($group_id, 'info', $group_info);
		
		$this->process_group_feed($group_id);
		$this->process_group_docs($group_id);
		$this->process_group_members($group_id);
	}
	
	private function process_group_feed($group_id) {
		$group_feed = $this->_query_big_data($group_id, "feed");
		$this->finish_processing($group_id, 'feed', $group_feed);
	}
	
	private function process_group_docs($group_id) {
		$group_docs = $this->_query_big_data($group_id, "docs");
		$this->finish_processing($group_id, 'docs', $group_docs);
	}

	private function process_group_members($group_id) {
		$group_members = $this->_query_big_data($group_id, "members");
		$this->finish_processing($group_id, 'members', $group_info);
	}
	
	/**
	 * For Facebook queries that are paginated
	 * @param string $node
	 * @param string $connection
	 * @return multitype:
	 */
	private function _query_big_data($node="me", $connection="") {
		
		if(empty($node)) {
			throw ImpossibleFacebookQueryException("Impossible Facebook Query");
		}
		
		$url = "/{$node}";
		
		if(!empty($connection))
			$url .= "/{$connection}";
		
		$result_data = array();
		$raw_fetch = array();
		
		$query_string = "";
		
		do {
			\KSimpleLogger::log("about to make a big data query on facebook: ".$url.$query_string);
			try {
				$raw_fetch = $this->facebook->api($url.$query_string);
				\KSimpleLogger::log("facebook big data query results: ".print_r($raw_fetch,true));
				$result_data = array_merge($result_data, $raw_fetch['data']);
				if(isset($raw_fetch['paging']['next']))
					$query_string = '?' . parse_url($raw_fetch['paging']['next'], PHP_URL_QUERY);
				else $query_string = "";
			}
			catch(\Exception $e) {
				\KSimpleLogger::log("Facebook API Exception: ".$e->getMessage());
				$query_string = "";
			}
		}
		while(!empty($query_string));
		return $result_data;
		
	}
	

	
	
	// $user_profile = $facebook->api('/me');
	// $user_groups = $facebook->api('/me/groups');
	// error_log($user_profile['email']);
}