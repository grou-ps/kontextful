<?php

class ScoreWWWPage extends ScoreAdapter implements ScoreInterface {
    private $GOOSE_DIR = '/Users/esokullu/Downloads/goose-master';
    private $GOOSE_CMD;
    private $feed;
    public function __construct($feed) {
        $this->GOOSE_CMD =  'cd '.$this->GOOSE_DIR.' && MAVEN_OPTS="-Xms256m -Xmx2000m"; mvn exec:java -Dexec.mainClass=com.gravity.goose.TalkToMeGoose -Dexec.args="%s" -e -q';
        $this->feed = $feed;
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