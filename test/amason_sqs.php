<?php

include('../vendor/autoload.php');
include('../configs/globals.php');

use Aws\Sqs\SqsClient;

$sqs = SqsClient::factory(array(
    'key'    => AWS_ACCESS_KEY,
    'secret' => AWS_SECRET_KEY,
    'region' => AWS_REGION
));

//$result = $sqs->createQueue(array('QueueName' => 'my-queue'));
//$queueUrl = $result->get('QueueUrl');
//echo $queueUrl;

$qurl = "https://sqs.us-west-1.amazonaws.com/515467233911/my-queue";


$sqs->sendMessage(array(
    'QueueUrl'    => $qurl,
    'MessageBody' => 'An awesome message on '.date("H:i:s"),
));

//exit;

$result = $sqs->receiveMessage(array(
    'QueueUrl' => $qurl,
    'WaitTimeSeconds' => 20,
    'MaxNumberOfMessages' => 10,
));


foreach ($result['Messages'] as $message) {
    // Do something with the message
    echo $message['Body']."\n";
    $sqs->deleteMessage(array(
        'QueueUrl'=>$qurl,
        'ReceiptHandle'=>$message['ReceiptHandle']
    ));
}

