<?php
/**
 * Created by JetBrains PhpStorm.
 * User: esokullu
 * Date: 3/24/14
 * Time: 2:27 PM
 * To change this template use File | Settings | File Templates.
 */

define('GOOSE_DIR','/Users/esokullu/Downloads/goose-master');
define('GOOSE_CMD', 'cd '.GOOSE_DIR.' && MAVEN_OPTS="-Xms256m -Xmx2000m"; mvn exec:java -Dexec.mainClass=com.gravity.goose.TalkToMeGoose -Dexec.args="%s" -e -q');

exec(sprintf(GOOSE_CMD, "https://github.com/jiminoc/goose/blob/master/src/main/scala/com/gravity/goose/TalkToMeGoose.scala"), $output, $res);
echo $res."\n";
print_r($output);
