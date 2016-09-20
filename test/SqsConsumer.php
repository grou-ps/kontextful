<?hh // strict
// emre

include('../vendor/autoload.php');
include('../configs/globals.php');
use Aws\Sqs\SqsClient;

abstract class SqsConsumer {

    private SqsClient $sqs;

    public function __construct(): void {
        $this->sqs = SqsClient::factory(array(
            'key'    => AWS_ACCESS_KEY,
            'secret' => AWS_SECRET_KEY,
            'region' => AWS_REGION
        ));
    }

    protected function check_for_updates($type): ?array {
        while(true) {
            $result = $this->sqs->receiveMessage(array(
                'QueueUrl' => SQS_QUEUE,
                'WaitTimeSeconds' => 20,
                'MaxNumberOfMessages' => 10,
            ));


            foreach ($result['Messages'] as $message) {
                $msg = json_decode($message['Body'],true);
                if($msg['msg']==$type) {
                    return $msg;
                }
                //$this->consume($msg);
                //$this->delete($message['ReceiptHandle']);
            }
        }
        return null;
    }

    abstract private function consume(array $msg): void;
/*
    private function consume(array $msg): void {
        // $msg: msg => consume, params => [ uid, service, access_token ]
        // skip service for now, it's always facebook
        if($msg['msg']!='consume') {
            return;
        }


        $fetcher = new Fetcher($msg['params']['service']);
        $fetcher->fetch($msg['params']['access_token']);

        switch($msg['params']['service']) {
            case 'facebook':
            default:
                $this->download_facebook_groups();
                $this->download_friend_lists();
                break;
        }
    }
*/
    protected function delete(string $handle): void {
        $this->sqs->deleteMessage(array(
            'QueueUrl' => SQS_QUEUE,
            'ReceiptHandle'=> $handle
        ));
    }

}


