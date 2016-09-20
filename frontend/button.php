<?php
session_start();
header('Content-Type: application/json');

include(__DIR__.'/Auth.class.php');
include_once(__DIR__.'/../vendor/autoload.php');

use \NlpTools\Stemmers\PorterStemmer;
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;


function die_error($msg, $code=1) {
	die(json_encode(array(
			"error"=>$code,
			"msg"=>ucfirst($msg)
		)
	));
}


$db=null;
function connect_db() {
	global $db;
	if(is_object($db)) {
		return $db;
	}
	else {
		try {
			# MySQL with PDO_MYSQL
			$db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB, MYSQL_USER, MYSQL_PASS);
		}
		catch(\PDOException $e) {
			die( $e->getMessage());
		}
		return $db;
	}
}


if(!isset($_SESSION['uid'])) {
	die_error("not logged in", 2);
}


if(isset($_GET['analyze'])) {
	
	$mem = new Memcache();
	$mem->connect('localhost', 11211);
	
	
	// echo "session uid is: ". $_SESSION['uid'] . PHP_EOL;
	
	
	$contexts = $mem->get("contexts.{$_SESSION['uid']}");
	if(!$contexts) {
		//error_log("you don't have any contexts analyzed yet.");
		die_error("you don't have any contexts analyzed yet.", 3);
	}
	
	
	header('Content-type: application/javascript; charset=utf-8');
	
	// make sure it's http and not https as we don't support ssl yet.
	$url = str_replace("https://", "http://", $_GET['analyze']);
	
	$cmd = "java -jar /usr/share/boilerpipe_extractor/boilerpipe_extractor.jar ".escapeshellarg($url);
	// error_log($cmd);
	exec($cmd, $output, $return_var);
	
	if($return_var==0) {
		$text = implode("\n", $output);	
	}
	else {
		die_error("Couldn't parse the URL");
	}
		
		
		$score = array();
		$max_weight= 0;
		$max_count= 0;
		
		$punct = new WhitespaceAndPunctuationTokenizer();
		$stemmer = new PorterStemmer();
		
		$text = $punct->tokenize($text);
		
		$lexicality = 10;
		
		
		//error_log("title is: ".$_GET['title']);
		
		if(isset($_GET['title'])) {
			$title = $text = $punct->tokenize($_GET['title']);
			foreach($title as $item) {
				$item = $stemmer->stem($item);
				if(!isset($score[$item])) {
					$score[$item] = array();
					$score[$item]['count'] = 1;
				}
				else {
					$score[$item]['count']++;
				}
				$score[$item]['weight'] = 1000*$lexicality;
				if($score[$item]['count'] > $max_count)
					$max_count = $score[$item]['count'];
				if($score[$item]['weight'] > $max_weight)
					$max_weight = $score[$item]['weight'];
			}
		}
		
		foreach($text as $item) {
			$item = $stemmer->stem($item);
			if(!isset($score[$item])) {
				$score[$item] = array();
				$score[$item]['count'] = 1;
			}
			else {
				$score[$item]['count']++;
			}
			$score[$item]['weight'] = 100*$lexicality;
			if($score[$item]['count'] > $max_count)
				$max_count = $score[$item]['count'];
			if($score[$item]['weight'] > $max_weight)
				$max_weight = $score[$item]['weight'];
		}
		

		
		
		
		// normalize
		connect_db();
		$url_md5 = md5($url);
		
		
		// first save the URL
		$stmt = $db->prepare(
					"INSERT INTO analyzed_urls (`url`,`url_md5`, `user_id`, `added_at`) VALUES (:url, :url_md5, :user_id, NOW())"
		);
		
		$stmt->bindParam(":url", $url);
		$stmt->bindParam(":url_md5", $url_md5);
		$stmt->bindParam(":user_id", $_SESSION['uid']);
		
		$stmt->execute();
		
		
		// then save the keywords
		foreach($score as $key=>$item) {
			$score[$key]['weight'] =$item['weight'] / $max_weight;
			$score[$key]['count'] = $item['count'] / $max_count;
			
			// insert into database
			$stmt = $db->prepare(
					"INSERT INTO content_scores (`content_id`, `word`, `count`, `weight`) VALUES (:url_md5, :word, :count, :weight)	"
			);
			$stmt->bindParam(":url_md5", $url_md5);
			$stmt->bindParam(":word", $key);
			$stmt->bindParam(":count", $score[$key]['count']);
			$stmt->bindParam(":weight", $score[$key]['weight']);
			$stmt->execute();
		}

		
		
		
		//echo "and the contexts are: ".PHP_EOL;
		// error_log(print_r($contexts, true));
		//echo "here we are".PHP_EOL;
		
		/*
 
v1
====================== 
SELECT context_scores.context_id, content_scores.word, (
context_scores.count + content_scores.count
) /2 AS count, (
context_scores.weight + content_scores.weight
) /2 AS  `weight` 
FROM content_scores
LEFT JOIN context_scores ON content_scores.word = context_scores.word

WHERE content_id =  "d9f8af066afed57920de2c959824fe11"
AND context_id =  "0x6e15bfee14642a"


v2
======================
SELECT context_scores.context_id, COUNT( DISTINCT content_scores.word ) , SUM( context_scores.count + content_scores.count )  AS count, SUM( context_scores.weight + content_scores.weight )  AS  `weight` 
FROM content_scores
LEFT JOIN context_scores ON content_scores.word = context_scores.word
WHERE content_id =  "d9f8af066afed57920de2c959824fe11"
AND context_id =  "0x6e15bfee14642a"
GROUP BY context_id



		 */
		
		$results = array();
		$total = $total2 = $_s = $max_overlap = 0;
		$most_likely = $second_likely = "";
		
		
		//$contexts_for_sql = array_map(function($x) use ($db) { return $db->quote($x); }, $contexts);
		// $contexts_for_sql = implode(",", $contexts_for_sql);
		$contexts_for_sql = implode(",", array_map(function($x) { return "'".$x."'"; }, $contexts));
		
		
		$sql = "SELECT context_id, COUNT( DISTINCT content_scores.word ) as overlap, SUM( context_scores.count + content_scores.count )  AS count, SUM( context_scores.weight + content_scores.weight )  AS  `weight`  FROM content_scores LEFT JOIN context_scores ON content_scores.word = context_scores.word WHERE content_id = :url_md5 AND context_id IN ( ".$contexts_for_sql." ) GROUP BY context_id";
		error_log($sql);
				
		
		$stmt = $db->prepare(
				//		"select sum(contents.`count` + contexts.`count`)/2 as count_mean, sum(contents.`weight` + contexts.`weight`)/2 as weight_mean from contents left join contexts on contents.word=contexts.word where contents.content_id=MD5(:url) and contexts.context_id=:context_id"
				//		"select sum(content_scores.`count` + context_scores.`count`)/2 as `count`, sum(content_scores.`weight` + context_scores.`weight`)/2 as `weight`  from content_scores left join context_scores on content_scores.word=context_scores.word where content_scores.content_id=:url_md5 and context_scores.context_id=:context_id"
				$sql
		);
		
		$stmt->bindParam(":url_md5", $url_md5);
		$stmt->execute();
		
		$results = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$results[] = $row;
			if($row['overlap']>$max_overlap)
				$max_overlap = $row['overlap'];
		} 
		
		foreach($results as $result) {
			$_s = 0;
			if($result['overlap']>1) {
				$_s += 
				10 * ($result['weight'] / $result['overlap']) 
				+  
				5 * ($result['count'] / $result['overlap']) 
				+ 
				2 * ( $result['overlap'] / $max_overlap );
			}
			// echo "Score is: ".$_s;
			
			if($_s>$total) {
				$total2 = $total;
				$total = $_s;
				$second_likely = $most_likely;
				$most_likely = $result['context_id'];
			}
			else if($_s>$total2) {
				$total2 = $_s;
				$second_likely = $result['context_id'];
			}
		}
		
		//error_log(print_r($results,true));
		// echo json_encode($results);
		//echo PHP_EOL;
		// echo "most likely is: ".$most_likely." - with score: ".$total;
		
		echo json_encode(
				array(
						"success" => array( 
								array("id"=>$mem->get("service_id.".$most_likely), "title"=>$mem->get("title.".$most_likely)), 
								array("id"=>$mem->get("service_id.".$second_likely), "title"=>$mem->get("title.".$second_likely))
						)
				)
		);
		
	}
	else {
		 // echo json_encode(array("error"=>"couldn't parse the wep page", "return_var"=>$return_var, "output"=>$output));
		 die_error("wep page not set");
	}
	
	
	exit;



/*
if(!isset($_SESSION['uid'])) {
	show_facebook_login();
}

*/