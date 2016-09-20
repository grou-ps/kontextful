<?hh //partial


include(__DIR__.'/../vendor/autoload.php');
include(__DIR__.'/../configs/globals.php');
include(__DIR__.'/../libs/AmqpSingleton.php');

class Auth {

    private string $table = "users";
    private Facebook $facebook;
    private int $uid = -1;
    private ?PDO $db;
    private string $email;
    private string $long_term_access_token = "";

    public function __construct(
        private string $service,
        private int $service_id,
        private string $access_token): Auth {

        $uid = null;

		$this->_connect_db();        

        $this->queue = AmqpSingleton::getInstance();

        $this->facebook = new \Facebook(array(
                'appId'  => FACEBOOK_APPID,
                'secret' => FACEBOOK_SECRET,
                'fileUpload' => false, // optional
                'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
            )
        );

        if(!$this->verify_access_token()) {
            // throw an exception
            die('user not verified');
        }

        if(($uid=$this->login())===-1) {
            $uid = $this->signup();
            // $this->ping_queue($uid); // after the coupon
        }
        
        $this->update_long_term_access_token();

        $this->set_session($uid);
        
		return $this;
    }
    
    private function _connect_db(): PDO {
		if(is_object($this->db)) {
			return $this->db;
		}
		else {
			try {
				# MySQL with PDO_MYSQL
				$this->db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB, MYSQL_USER, MYSQL_PASS);
			}
			catch(\PDOException $e) {
				die( $e->getMessage());
			}
			return $this->db;
		}
	}
	
	private function update_long_term_access_token(): bool {
		$ltat = $this->get_long_term_access_token();
		$this->long_term_access_token = $ltat;
		$sth = $this->_connect_db()->prepare(
				"UPDATE users SET long_term_access_token = :ltat WHERE user_id = :user_id LIMIT 1"
		);
		
		$sth->bindParam(":user_id", $this->service_id);
		$sth->bindParam(":ltat", $ltat);
		
		return $sth->execute();
	}

	
	private function get_long_term_access_token(): string {
		$urlt = "https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&grant_type=fb_exchange_token&fb_exchange_token=%s";
		$url = sprintf($urlt, FACEBOOK_APPID, FACEBOOK_SECRET, $this->access_token);
		$res = trim(file_get_contents($url));
		if(preg_match("/^access\_token\=(.+)\&expires=([0-9]+)$/", $res, $matches)) {
			return $matches[1];
		}
		else
			return "";
	}

    private function verify_access_token(): bool {
        // ignore $service for now.
        // it's only facebook

        $this->facebook->setAccessToken($this->access_token);
        $user = $this->facebook->getUser();

        if($user!=$this->service_id)
            return false;

        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $this->facebook->api('/me');
            //$user_groups = $this->facebook->api('/me/groups');
        } catch (FacebookApiException $e) {
            error_log($e->getMessage());
            error_log(print_r($user_profile,true));
            return false;
        }

        if(isset($user_profile['email'])) {
        	$this->email = $user_profile['email'];
            return true;
        }

        error_log(print_r($user_profile,true));

        return false;

    }
    
    public function is_logged_in(): bool {
    	return isset($_SESSION['uid']); 
    }

    private function set_session(int $uid): void {
        // session_start();
        $_SESSION['uid'] = $uid;
        $_SESSION['service'] = $this->service;
        $_SESSION['service_id'] = $this->service_id;
        $_SESSION['access_token'] = empty($this->long_term_access_token) ? $this->access_token : $this->long_term_access_token;
    }

    public function signup(): int {
        
		$sth = $this->_connect_db()->prepare(
				"INSERT INTO users (user_id, email, added_at) VALUES (:user_id, :email, NOW())"
		);
		
		//  because it is facebook, and we know that
		// @todo: revisit this when new services may be added.
		$sth->bindParam(":user_id", $this->service_id); 
		
		$sth->bindParam(":email", $this->email);
		
		if(!$sth->execute())
			return -1;
			
		$sth = $this->_connect_db()->prepare(
				"INSERT INTO services (user_id, service, service_id, added_at) VALUES (:user_id, :service, :service_id, NOW())"
		);
		
		$sth->bindParam(":user_id", $this->service_id);
		$sth->bindParam(":service_id", $this->service_id);
		$sth->bindParam(":service", $this->service);
		
		return $sth->execute() ? (int) $this->service_id : -1;

    }

    /**
     * Returns true if there is a user with the specified service
     * and service id.
     * @param string $service
     * @param string $id
     * @returns user_id
     */
    public function login(): int {
    
    	$sth = $this->_connect_db()->prepare(
				"SELECT user_id FROM services WHERE service_id=:service_id AND service=:service LIMIT 1"
		);
		
		$sth->bindParam(":service_id", $this->service_id);
		$sth->bindParam(":service", $this->service);
		
		if(!$sth->execute())
			return -1;
		
		$user_id = $sth->fetchColumn();
		
		if(is_numeric($user_id))
			$user_id = (int) $user_id;
		else
			$user_id = -1;
			
		return $user_id;	
    }

    public function ping_queue(int $uid): void {
        
        $msg_body = array(
                            'uid' => $uid,
                            'service' => $this->service,
                            'access_token' => $this->access_token
		);
		
		$msg = $this->queue->createMessage(json_encode($msg_body));
        $this->queue->publish($msg, "fetch");
        $this->queue->close();
    }
    
    public function check_invitation_code(string $code): bool {
    	$legit_codes = json_decode(LEGIT_CODES, true);
    	error_log("legit codes are: ".print_r($legit_codes,true));
    	error_log("code is: ".$code);
    	if(in_array($code, $legit_codes)) {
    		return $this->apply_invitation_code($code);
    		// return true;
    	}
    	else {
    		return false;
    	}
    }
    
    private function _get_coupon_use(string $code): int {
    	$coupon_use = glob(sys_get_temp_dir(), "COUPON_".$code."_*");
    	return count($coupon_use);
    }
    
    private function _register_coupon_use(string $code): void {
    	tempnam(sys_get_temp_dir(), "COUPON_".$code."_");
    }
    
    private function apply_invitation_code(string $code): void {
    	
    	if( $this->_get_coupon_use($code) > 250 )
    		return false;
    	
    	$sth = $this->_connect_db()->prepare(
				"UPDATE users SET invite_code = :code WHERE user_id = :user_id"
		);
		
		$sth->bindParam(":code",$code);
		$sth->bindParam(":user_id",$_SESSION['uid']);
		
		$sth->execute();
		
		$this->_register_coupon_use($code);
		
		$this->ping_queue((int)$_SESSION['uid']);
		
		return true;
    }
}