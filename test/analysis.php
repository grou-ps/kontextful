<?php

include('../vendor/autoload.php');

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use \NlpTools\Analysis\FreqDist;


$stopwords = file_get_contents('../assets/db/stopwords/english.txt');
$stopwords = explode("\n",$stopwords);
//print_r($stopwords);

$s = "Please allow me to introduce myself.
I'm a man of wealth and taste. How about you my lady? Should I mention that I LOVE you lady?";

$s = mb_strtolower($s);

$punct = new WhitespaceAndPunctuationTokenizer();
$punct_res = $punct->tokenize($s);

//print_r($punct_res);

$cleaned = array_diff($punct_res, $stopwords);
//print_r($cleaned);

$cleaned_just_words = array_filter($cleaned,function($var){if(is_numeric($var)||preg_match("/^[a-z0-9]+$/",$var)) return true;});

//print_r($cleaned_just_words);
$anal = new FreqDist($cleaned_just_words);
print_r($anal->getKeyValues());
