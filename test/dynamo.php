<?php

# https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/LowLevelPHPItemCRUD.html

include('../vendor/autoload.php');
include('../configs/globals.php');

use Aws\DynamoDb\DynamoDb;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\Enum\ReturnValue;
use Aws\DynamoDb\Enum\ComparisonOperator;


$aws = Aws\Common\Aws::factory(array(
    'key'    => AWS_ACCESS_KEY,
    'secret' => AWS_SECRET_KEY,
    'region' => AWS_REGION,
));

$db = $aws->get("dynamodb");

$table = "users";


if(false) {

    echo "# Adding data to table {$table}..." . PHP_EOL;

    $uid = uniqid(md5(rand()),true);

    echo "# UID is {$uid}". PHP_EOL;


    $response = $db->putItem(array(
        "TableName" => $table,
        "Item" => $db->formatAttributes(array(
                "users" => $uid,
                "service_id" => json_encode(array(
                    "service_type" => "deneme",
                    "service_specific_id" => "834fweihfue"
                )),
                "added_at" => time()
            )
        ),
        "ReturnConsumedCapacity" => 'TOTAL'
    ));

    echo "Consumed capacity: " . print_r($response, true) . PHP_EOL;


}


if(true) {

    $uid = "53d33bb9db69adfc35bcee33aa8a9a095344522fb98ff5.96604077";
    echo "# uid is {$uid}".PHP_EOL;

    /*
     * getItem only for primary key
     */
    if(false) {
        $response = $db->getItem(array(
            "TableName" => $table,
            "ConsistentRead" => true,
            "Key" =>
                /*
                "service_id" => array(Type::STRING => json_encode(array(
                    "service_type" => "deneme",
                    "service_specific_id" => "834fweihfue"
                    ))
                )
                */
                /*
               "users" => array(Type::STRING => $uid)
                */
                $db->formatAttributes(array(
                    "service_id" => json_encode(array(
                            "service_type" => "deneme",
                            "service_specific_id" => "834fweihfue"
                        )
                    )
                ))

           ,"AttributesToGet" => array("users", "service_id", "added_at")
        ));
        print_r($response["Item"]);
    }


    if(true){
        /*
         * make sure IndexName is in place.
         */
        $response = $db->query(array(
            "TableName" => $table,
            "IndexName" => "service_id-index",
            "KeyConditions" => array(
                /*
                "service_id" => array(Type::STRING => json_encode(array(
                    "service_type" => "deneme",
                    "service_specific_id" => "834fweihfue"
                    ))
                )
                */
                /*
               "users" => array(Type::STRING => $uid)
                */

                "service_id" => array(
                    "ComparisonOperator" => ComparisonOperator::EQ,
                    "AttributeValueList" => array(
                        array(Type::STRING => json_encode(array(
                            "service_type" => "deneme",
                            "service_specific_id" => "834fweihfue"
                        ))
                        )
                    )
                )
            )
        ));
        print_r($response);
        echo PHP_EOL;
        echo "and the user id is: ".$response['Items'][0]['users']['S'];

    }

}


/*
echo json_encode(array(
    "service_type" => "deneme",
    "service_specific_id" => "834fweihfue"
));
*/
/*
print_r($db->formatAttributes(array(
    "service_id" => json_encode(array(
            "service_type" => "deneme",
            "service_specific_id" => "834fweihfue"
        )
    )
)));
*/
