<?php
/**
 * Created by JetBrains PhpStorm.
 * User: esokullu
 * Date: 3/25/14
 * Time: 5:35 AM
 * To change this template use File | Settings | File Templates.
 */

class ScoreFacebookGroup extends ScoreAdapter implements ScoreInterface {
    private $feed;
    public function __construct($group_id) {
        $this->feed = $group_id;
    }
    public function fetchCorpus() {
        $cmd_final = sprintf($this->GOOSE_CMD, $this->feed);
        //error_log($cmd_final);
        exec($cmd_final, $output, $res);
        if($res===0) {
            /*error_log(print_r($output,true));
            $corpus = strtolower(implode("\n",$output));
            $this->title = preg_match_all("/~~~[a-z]+: [^\n]+/i",$corpus,$matches);
            print_r($matches);*/
            $corpus['title'] = str_replace("~~~Title: ","",$output[0]);
            $corpus['tags'] = str_replace("~~~Keywords: ","",$output[1]);
            $corpus['description'] = str_replace("~~~Description: ","",$output[2]);
            $corpus['content'] = str_replace("~~~Article: ", "", $output[3].implode("\n",array_slice($output, 3)));
            return $corpus;
        }
        else throw new ScoreException("ScoreWWWPage can't parseCorpus");
    }
}