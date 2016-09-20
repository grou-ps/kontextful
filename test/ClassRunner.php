<?php
/**
 * Created by JetBrains PhpStorm.
 * User: esokullu
 * Date: 3/24/14
 * Time: 6:58 PM
 * To change this template use File | Settings | File Templates.
 */
include("../Score.php");
$score = new Score("www","http://edition.cnn.com/2014/03/24/world/europe/ukraine-crisis/index.html?hpt=hp_t2");
$score->fetch();
print_r($score->getCorpus());
print_r($score->compute());