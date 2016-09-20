<?php
/**
 * Created by JetBrains PhpStorm.
 * User: esokullu
 * Date: 3/24/14
 * Time: 5:55 PM
 * To change this template use File | Settings | File Templates.
 */

include('vendor/autoload.php');
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use \NlpTools\Analysis\FreqDist;

class ScoreException extends Exception {}

class Score {

    const kTITLE = 10;
    const kTAGS = 8;
    const kDESCRIPTION = 5;
    const kCONTENT = 2;

    protected $_adapter = null;
    private $stopwords;
    private $corpus;

    public function __construct($adapter, $feed) {

        $stopwords = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'db/stopwords/english.txt');
        $this->stopwords = explode("\n",$stopwords);

        switch($adapter) {
            case 'FacebookPage':
                include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "Score/Facebook/FriendList.php");
                $this->_adapter = new ScoreFacebookPage($feed);
                break;
            case 'FacebookGroup':
                include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "Score/Facebook/Group.php");
                $this->_adapter = new ScoreFacebookGroup($feed);
                break;
            case 'www':
            default:
                include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "Score/WWW/Page.php");
                $this->_adapter = new ScoreWWWPage($feed);
                break;
        }
    }

    public function adapter() {
        return $this->_adapter;
    }

    /*
     * either a Facebook Page, or Group or Web Page.
     */
    public function fetch() {
        $ret = array();
        $corpus = $this->adapter()->fetchCorpus();
        foreach($corpus as $context=>$corp) {
            $this->corpus[$context] = $this->analyze($corp);
        }
    }

    public function compute() {
        $db = array();
        foreach($this->corpus['title'] as $word=>$rep) {
            if(isset($db[$word]))
                $db[$word] += $rep * self::kTITLE;
            else
                $db[$word] = $rep * self::kTITLE;
        }
        foreach($this->corpus['tags'] as $word=>$rep) {
            if(isset($db[$word]))
                $db[$word] += $rep * self::kTAGS;
            else
                $db[$word] = $rep * self::kTAGS;
        }
        foreach($this->corpus['description'] as $word=>$rep) {
            if(isset($db[$word]))
                $db[$word] += $rep * self::kDESCRIPTION;
            else
                $db[$word] = $rep * self::kDESCRIPTION;
        }
        foreach($this->corpus['content'] as $word=>$rep) {
            if(isset($db[$word]))
                $db[$word] += $rep * self::kCONTENT;
            else
                $db[$word] = $rep * self::kCONTENT;
        }
        $sum = array_sum($db);
        arsort($db, SORT_NUMERIC);
        $divider = reset($db); // returns the first key of an associative array
        foreach($db as $word=>$val) {
            $db[$word] = $val/$divider;
        }
        return $db;
    }

    public function getCorpus() {
        return $this->corpus;
    }

    private function analyze($corp) {
        $punct = new WhitespaceAndPunctuationTokenizer();
        $punct_res = $punct->tokenize($corp);
        $cleaned = array_diff($punct_res, $this->stopwords);
        $cleaned_just_words = array_filter(
            $cleaned,
            function($var){
                if(is_numeric($var)||preg_match("/^[a-z0-9]+$/",$var))
                    return true;
            }
        );
        $freqdist = new FreqDist($cleaned_just_words);
        return $freqdist->getKeyValues();
    }
}