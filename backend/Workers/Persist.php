<?php
namespace Kontextful\Backend\Workers;

class PersistException extends \Exception {}

class Persist extends Worker {
	private $count_set;
	private $weight_set;
	private $db;
	private $vector;
	private $persistent_data_file;
	private $graph_file;
	private $context_id;
	private $contexts_list_key = "";
	private $normalized_count_key = "";
	private $normalized_weight_key = "";
	private $root_dir;
	private $groups_json_data = "";
	
	const expire_time = 3600; // seconds, 1 day, 60*60
	
	
	public function __construct() {
		parent::__construct();
		$this->set_this_step("persist");
	}
	
	public function bind($params) {
		parent::bind($params);
		$this->contexts_list_key = $this->params['global_key'].'contexts_list';
		\KSimpleLogger::log("Contexts list key will be: ".$this->contexts_list_key);
	}
	
	private function set_file_locations() {
		/*
		if(!preg_match("/(^.+\/[0-9]{14}\/)[a-z]+\/.+$/",$this->params['raw_file'],$matches)) {
			throw PersistException("Erroneous raw_file parameter");
		}
		*/
		$this->root_dir = DATA_DIR. $this->params['service'] . DIRECTORY_SEPARATOR . $this->params['uid']. DIRECTORY_SEPARATOR .$this->params['session_id'] . DIRECTORY_SEPARATOR; // .
		
		\KSimpleLogger::log("Root dir is: ".$this->root_dir);
		//exit;
		
		
		$this->persistent_data_file = $this->root_dir.$this->context_id."_score_vector.json";
		$this->graph_file = $this->root_dir.$this->context_id."_graph.gif";
		
		\KSimpleLogger::log("Persistent data file: ".$this->persistent_data_file);
		\KSimpleLogger::log("Graph file: ".$this->graph_file);
		
		return $this;
	}
	
	private function create_keys($context) {
		$this->count_key = $this->params['global_key'].$context.'-count';
		$this->weight_key = $this->params['global_key'].$context.'-weight';
		$this->global_count_key = $this->params['global_key'].$context.'-global_count';
		$this->global_weight_key = $this->params['global_key'].$context.'-global_weight';
		$this->normalized_count_key = $this->params['global_key'].$context.'-normalized_score';
		$this->normalized_weight_key = $this->params['global_key'].$context.'-normalized_weight';
	}
	
	private function get_contexts_list() {
		$r = array();
		$cl = $this->redis->smembers($this->contexts_list_key);
		/*if(!is_null($cl))
			$cl = json_decode($cl, true);*/
		return $cl;
	}
	
	public function process() {
		\KSimpleLogger::log("Time for some persisting");
		\KSimpleLogger::log("Will look up contexts list key: ". $this->contexts_list_key);
		$contexts = $this->get_contexts_list();
		
		\KSimpleLogger::log("Will look up contexts list key: ". print_r($contexts, true));
		
		foreach($contexts as $context) {
			\KSimpleLogger::log("Persisting context: ".$context);
			$this->create_keys($context);
			$this->expire_temp_data();
			$this->generate_context_id();
			
			\KSimpleLogger::log("Context ID is set to be: ".$this->context_id);
			
			$this->set_file_locations()->load_scores()->dump_into_file()->dump_into_db($context)->visualize();
			$this->expire_normalized_vector();
		}
		
		$this->notify_user();
		
	}
	
	
	private function _get_email() {
		$sth = $this->_connect_db()->prepare(
				"SELECT email FROM users WHERE user_id = :user_id"
		);
		
		$sth->bindParam(":user_id", $this->params['uid']);
		
		$sth->execute();
		
		$email = $sth->fetch(\PDO::FETCH_ASSOC);
		$email = $email['email'];
		
		return $email;
	}
	
	private function notify_user() {
		if(!NOTIFY_USER) {
			return false;
		}
		
		$mail = new \PHPMailer;
		$mail->IsSMTP();
		$mail->Host = SMTP_HOST;
		$mail->Port = SMTP_PORT;
		$mail->SMTPAuth = true;
		$mail->Username = SMTP_USER;
		$mail->Password = SMTP_PASS;
		$mail->SMTPSecure = 'tls';
		
		$mail->From = "emre@kontextful.com";
		$mail->FromName = "Emre Sokullu";
		
		// tricky
		$mail->AddAddress($this->_get_email());
		$admins = NOTIFY_ADMINS;
		if(!empty($admins)) {
			$admins = json_decode($admins, true);
			foreach($admins as $admin) {
				$mail->AddBCC($admin);
			}
		}
		
		
		$mail->IsHTML(false);
		$mail->Subject = "Kontextful Ready For Action";
		$msg = <<<EOS
We're done analyzing your social network accounts. 
If you haven't installed the Kontextful browser extensions yet, please do so from http://kontextful.com/extensions.hh and start using Kontextful. 
Have questions or comments? Just hit reply. 
Thanks for being an early adopter. Share the love. Cheers,
--
Emre
EOS;
		$mail->Body = $msg;
		if(!$mail->Send()) {
			\KSimpleLogger::log('Message could not be sent.');
			\KSimpleLogger::log('Mailer Error: ' . $mail->ErrorInfo);
		}	
	}
	
	private function load_scores() {
		
		\KSimpleLogger::log("Fetching: ".$this->normalized_count_key);
		\KSimpleLogger::log("Fetching: ".$this->normalized_weight_key);
		
		$this->count_set = $this->redis->hgetall($this->normalized_count_key);
		$this->weight_set = $this->redis->hgetall($this->normalized_weight_key);
		
		\KSimpleLogger::log("Count Set: ".print_r($this->count_set, true));
		\KSimpleLogger::log("Weight Set: ".print_r($this->weight_set, true));
		
		return $this;
	}
	
	private function expire_temp_data() {
		$this->redis->expire($this->count_key, self::expire_time);
		$this->redis->expire($this->weight_key, self::expire_time);
		$this->redis->expire($this->global_count_key, self::expire_time);
		$this->redis->expire($this->global_weight_key, self::expire_time);
	}
	
	private function expire_normalized_vector() {
		$this->redis->expire($this->normalized_count_key, self::expire_time);
		$this->redis->expire($this->normalized_weight_key, self::expire_time);
	}
	
	private function dump_into_file() {
		
		\KSimpleLogger::log("Dumping into: ".$this->persistent_data_file);
		if(LOG_DATA) {
			file_put_contents($this->persistent_data_file, json_encode(array(
										"counts" => $this->count_set,
										"weights" => $this->weight_set
									)), 
								LOCK_EX);
		}
		return $this;
	}
	
	
	// http://labs.ayzenberg.com/2011/11/store-uuid-as-binary-in-mysql/
	// varbinary(16)
	private function generate_context_id() {
		// version 4 UUID
		$this->context_id = '0x'.sprintf(
				'%08x%04x%04x%02x%02x%012x',
				mt_rand(),
				mt_rand(0, 65535),
				bindec(substr_replace(
						sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
				),
				bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
				mt_rand(0, 255),
				mt_rand()
		);
		return $this;
	}
	
	private function _get_context_title($context_id) {
		
		// first filter
		// $context_id = str_replace("facebook_groups:","", $context_id);
		
		if(empty($this->groups_json_data)) {
			$file = file_get_contents($this->root_dir."groups.json");
			$this->groups_json_data = json_decode(json_decode($file, true),true);
		}
		/*
		// give it a try
		if($file_decoded==null && $file[0]=='"') {
			eval('$x = '.$file.';');
			$file_decoded = json_decode($x, true);
		}
		
		if($file_decoded==null) {
			return "";
		}
		*/
		
		$name = "";
		
		foreach($this->groups_json_data as $unit) {
			if($unit['id']==$context_id) {
				$name = $unit['name'];
				break;
			}
		}
		
		\KSimpleLogger::log("Context title for $context_id is $name");
		
		return $name;
	}
	
	private function dump_into_db($pure_context_id) {
		
		\KSimpleLogger::log("Dumping into db");
		\KSimpleLogger::log("First, create the context.");		
		\KSimpleLogger::log("The values will be: ".$this->context_id.', '.$this->params['uid'].', '.$this->params['service']);
		\KSimpleLogger::log("Pure service id is: ".$pure_context_id);
		
		$pure_context_id = str_replace("facebook_groups:","", $pure_context_id);
		
		$sth = $this->_connect_db()->prepare(
				"INSERT INTO contexts (`context_id`, `title`, `user_id`, `service`, `service_id`, `added_at`) VALUES (:context_id, :context_title, :user_id, :service, :service_id, NOW())"
		);
		
		$sth->bindParam(":context_id", $this->context_id);
		$sth->bindParam(":context_title", $this->_get_context_title($pure_context_id));
		$sth->bindParam(":user_id", $this->params['uid']);
		// $sth->bindParam(":service_type", "groups");
		$sth->bindParam(":service_id", $pure_context_id);
		$sth->bindParam(":service", $this->params['service']);
		$sth->execute();
		
		$sth = $this->_connect_db()->prepare(
				"INSERT INTO context_scores (`context_id`, `word`, `count`, `weight`) VALUES (:context_id, :word, :count, :weight)"
		);
		
		$sth->bindParam(":context_id", $this->context_id);
		
		unset($this->vector);
		$this->vector = array();
		
		foreach($this->count_set as $key=>$count_value) {
			
			\KSimpleLogger::log("Writing into db, key: ".$key);
			
			if(!isset($this->weight_set[$key])) {
				\KSimpleLogger::log("No weight, skipping.");
				continue;
			}	
			
			$this->vector[] = array($key, $count_value, $this->weight_set[$key]);
			
			$sth->bindParam(":word", $key);
			$sth->bindParam(":count", $count_value);
			$sth->bindParam(":weight", $this->weight_set[$key]);
			$sth->execute();
		}
		return $this;
	}
	
	private function _connect_db() {
		if(is_object($this->db)) {
			return $this->db;
		}
		else {
			try {
				# MySQL with PDO_MYSQL
				$this->db = new \PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB, MYSQL_USER, MYSQL_PASS);
			}
			catch(\PDOException $e) {
				\KSimpleLogger::log("mysql pdo exception: ".$e->getMessage());
				throw $e;
			}
			return $this->db;
		}
	}
	
	private function visualize() {
		\KSimpleLogger::log("Time to visualize in: ".$this->graph_file. " with ". print_r($this->vector, true) );
		
		$plot = new \PHPlot(1600, 1200);
		$plot->SetImageBorderType('plain');
		$plot->SetPlotType('points');
		$plot->SetDataType('data-data');
		$plot->SetDataValues($this->vector);
		
		$plot->SetTitle('Context Graph For '.$this->context_id);
		$plot->SetPlotAreaWorld(0, 0, 1, 1);
		
		$plot->setOutputFile($this->graph_file);
		$plot->SetIsInline(true);
		$plot->SetFileFormat('gif');
		
		$plot->DrawGraph();
		$plot->PrintImage();
	}
	
}