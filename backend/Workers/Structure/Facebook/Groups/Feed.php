<?php
// do nothing

namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

class Feed extends StructureFacebookGroupsWorker {
	

	
	
	private $gi = 0; // global iterator
	private $current_status_id = -1;
	private $fbapi;
	private $big_data_buffer = array();
	
	/*
	public function process() {
		\KSimpleLogger::log("Processing the following file for Structure/Facebook/Groups/Feed: ".$this->raw_file);
		$this->structure()->do_next_step()->_save_file();
	}
	*/
	
	protected function set_structure_id() {
		$this->structure_id = 1;
	}
	
	
	protected function structure() {
		
		if(PIPE_TYPE=="stdin")
			$data = $this->raw_data;
		else
			$data = $this->_decode_raw_file();
		
		foreach($data as $status) {
			if(!isset($status['message']))
				continue;
				
			$this->current_status_id = $status['id'];
			$this->structured_data[$this->gi]['content'] = $status['message'];
			$this->structured_data[$this->gi]['likes'] = $this->get_facebook_likes($status['likes']);
			$this->structured_data[$this->gi]['comments'] = $this->get_facebook_comments($status['comments']);
			$this->gi++;
				
			$this->flush_temporary_files();
				
			// $this->handle_paging($data);
		}
		return $this;
	}
	
	/*
	protected function structure_with_file_as_input() {
		$data = $this->_decode_raw_file();
		foreach($data as $status) {
			if(!isset($status['message']))
				continue;
			
			$this->current_status_id = $status['id'];
			$this->structured_data[$this->gi]['content'] = $status['message'];
			$this->structured_data[$this->gi]['likes'] = $this->get_facebook_likes($status['likes']);
			$this->structured_data[$this->gi]['comments'] = $this->get_facebook_comments($status['comments']);
			$this->gi++;
			
			$this->flush_temporary_files();
			
			// $this->handle_paging($data);
		}
		return $this;
	}
	
	*/
	
	private function fbapi() {
		if(!is_object($this->fbapi))
			$this->build_fbapi();
		return $this->fbapi;
	}
	
	private function build_fbapi() {
		try {
			$this->fbapi = new \Facebook(array(
				'appId'  => FACEBOOK_APPID,
				'secret' => FACEBOOK_SECRET,
				'fileUpload' => false, // optional
				'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
			)
		);
		$this->fbapi->setAccessToken($this->params['access_token']);
		}
		catch(\FacebookException $e) {
			throw $e;
		}
	}
	
	// this is very similar to a method with the name _query_big_data in backend/Workers/Fetch/Facebook
	// we could use a trait as well but for 5.3 compatibility, we don't
	protected function _query_big_data($query, $save_as = "") {
	
		if(empty($query)) {
			throw ImpossibleFacebookQueryException2("Impossible Facebook Query -2");
		}
	
	
		$facebook = $this->fbapi();
	
		$result_data = array();
		$raw_fetch = array();
	
		do {
			$query = preg_replace("/https?\:\/\/graph\.facebook\.com/i","", $query);
			\KSimpleLogger::log("about to make a big data query on facebook: ".$query);
			$raw_fetch = $facebook->api($query);
			\KSimpleLogger::log("facebook big data query results: ".print_r($raw_fetch,true));
			$result_data = array_merge($result_data, $raw_fetch['data']);
			if(isset($raw_data['paging']['next']))
				$query =$raw_data['paging']['next'];
			else $query = "";
		}
		while(!empty($query_string));
	
		if(!empty($save_as))
			$this->_save_big_data($result_data, $save_as);
	
		return $result_data; // $this->_decode_raw_data($result_data);
	
	}
	
	private function _save_big_data($data, $save_as) {
		$this->big_data_buffer[$save_as] = $data;
	}
	
	private function flush_temporary_files() {
		foreach($this->big_data_buffer as $buffer_name => $buffer) {
			$file_name = str_replace(".json", ".".$buffer_name, $this->raw_file) . ".json";
			file_put_contents($file_name, json_encode(
												array($current_status_id => $buffer)
											),
							 FILE_APPEND | LOCK_EX);
		}
		
		$this->big_data_buffer = array(); // reset 
	}
	
	
	// no need to do that, we already handle paging for main items in the Fetch Worker.
	private function handle_paging($data) {
		/*
		if(@isset($data['paging']['next'])) {
			$_data = $this->_query_big_data($data['paging']['next']);
			array_merge($comments, $this->get_facebook_comments($_data));
		}
		*/
	}
	
	private function get_facebook_comments($data) {
		$comments = array();
		if(isset($data) && @is_array($data['data'])) {
			foreach($data['data'] as $i=>$comment) {
				$comments[$i]['content'] = $comment['message'];
				$comments[$i]['likes'] = $comment['like_count'];
			}
			if(@isset($data['paging']['next'])) {
				$_data = $this->_query_big_data($data['paging']['next'], "comments");
				array_merge($comments, $_data);
				// array_merge($comments, $this->get_facebook_comments($_data));
			}
		}
		return $comments;
	}
	
	private function get_facebook_likes($data) {
		if(isset($data) && @is_array($data['data']))
			return $this->compute_facebook_likes($data);
		else 
			return 0;
	}
	
	private function compute_facebook_likes($likes) {
		$like_counter = 0;
		$like_counter += count($likes['data']);
		if(@isset($likes['paging']['next'])) {
			$_data = $this->_query_big_data($likes['paging']['next'], "likes");
			$like_counter += count($_data);
			// $like_counter += $this->compute_facebook_likes($_data);
		}
		return $like_counter;
	}
	
	
}
