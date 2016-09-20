<?hh // partial

include('../vendor/autoload.php');
include('../configs/globals.php');
include('../libs/AmqpSingleton.php');

use Aws\DynamoDb\DynamoDb;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\Enum\ReturnValue;
use Aws\DynamoDb\Enum\ComparisonOperator;
use Aws\Sqs\SqsClient;


class Auth {

    private string $table = "users";
    private Facebook $facebook;
    private ?string $uid = null;

    public function __construct(
        private string $service,
        private string $service_id,
        private string $access_token): bool {

        $uid = null;

        $aws = Aws\Common\Aws::factory(array(
            'key'    => AWS_ACCESS_KEY,
            'secret' => AWS_SECRET_KEY,
            'region' => AWS_REGION,
        ));

        $this->db = $aws->get("dynamodb");
        $this->queue = AmqpSingleton::getInstance();

        $this->facebook = new Facebook(array(
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

        if(($uid=$this->login())===null) {
            $uid = $this->signup();
            $this->ping_queue($uid);
        }

        $this->set_session($uid);

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

        if(isset($user_profile['email'])) // a bit unnecessary but.
            return true;

        error_log(print_r($user_profile,true));

        return false;

    }

    private function set_session(string $uid): void {
        session_start();
        $_SESSION['uid'] = $uid;
        $_SESSION['service'] = $this->service;
        $_SESSION['service_id'] = $this->service_id;
    }

    public function signup(): string {
        $uid = uniqid(md5(rand()),true);
        try {
            $response = $this->db->putItem(array(
                "TableName" => $this->table,
                "Item" => $this->db->formatAttributes(array(
                            "users" => $uid,
                            "service_id" => json_encode(array(
                            "service_type" => $this->service,
                            "service_specific_id" => $this->service_id
                        )),
                        "added_at" => time()
                    )
                )
            ));
            return $uid;
        } catch (Exception $e) {
            error_log($e->getMessage());
            // throw an error
            die('signup failed');
        }
    }

    /**
     * Returns true if there is a user with the specified service
     * and service id.
     * @param string $service
     * @param string $id
     * @returns user_id
     */
    public function login(): ?string {
        try {
            $response = $this->db->query(array(
                "TableName" => $this->table,
                "IndexName" => "service_id-index",
                "KeyConditions" => array("service_id" => array(
                        "ComparisonOperator" => ComparisonOperator::EQ,
                        "AttributeValueList" => array(
                            array(Type::STRING => json_encode(array(
                                "service_type" => $this->service,
                                "service_specific_id" => $this->service_id
                                ))
                            )
                        )
                    )
                )
            ));

            if ($response['Count'] >= 1 && isset($response['Items'][0]['users']['S']))
                return $response['Items'][0]['users']['S'];
        } catch (Exception $e) {
            error_log($e->getMessage());

        }

        return null;
    }

    public function ping_queue(string $uid): void {
        
        /* $this->queue->sendMessage(
            array(
                'QueueUrl'    => SQS_QUEUE,
                'MessageBody' => json_encode(
                    array(
                        'msg'=>'process',
                        'params'=> array(
                            'uid' => $uid,
                            'service' => $this->service,
                            'access_token' => $this->access_token
                        )
                    )
                ),
            )
        ); */
        
        $msg_body = array(
                            'uid' => $uid,
                            'service' => $this->service,
                            'access_token' => $this->access_token
		);
		
		$msg = $this->queue->createMessage(json_encode($msg_body));
        $this->publish($msg, "fetch");
        $this->queue->close();
    }
}



new Auth('facebook',$_POST['facebook_id'],$_POST['access_token']);
echo "Hello ".$_POST['first_name'].";<br><img src='http://graph.facebook.com/{$_POST['username']}/picture' />";

