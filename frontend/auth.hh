<?php

include(__DIR__.'/Auth.class.php');
// include('../libs/AmqpSingleton.hh');

session_start();

$signup_info = isset($_POST['access_token'])?print_r($_POST,true):"";
if(!empty($signup_info)) {
	file_put_contents("/tmp/signups.txt",$signup_info,FILE_APPEND | LOCK_EX);
	mail("emre@groups-inc.com","Kontextful Sign up", $signup_info);
}

$auth = new Auth('facebook',(int)$_POST['facebook_id'],$_POST['access_token']);

if(!$auth->is_logged_in())
	die("oops. you haven't logged in. please go back and try again.");


error_log("invitation code: ".$_POST['invitation_code']);

$invitation_tried = false;
$invitation_succeeded = false;
if(isset($_POST['invitation_code'])&&!empty($_POST['invitation_code'])) {
	$invitation_tried = true;
	if($auth->check_invitation_code($_POST['invitation_code'])) {
		$invitation_succeeded = true;	
	}
}



$msg = "";
if($invitation_succeeded) {
	$msg = "Wait while we analyze your social networks. We'll email you when we're finished.";
}
else if ($invitation_tried) {
	$msg = "Your code isn't valid. But good thing you're one of the first in our wait list. We'll call you soon.";
}
else {
	$msg = "You're one of the first in our wait list. We'll call you soon.";
}





?>






<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Kontextful</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        
        <link rel="stylesheet" href="css/main.css">

        <script src="js/vendor/modernizr-2.6.2.min.js"></script>

        <link rel="stylesheet" href="css/ladda-themeless.min.css">


    </head>
    <body>
    
    <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

    <div class="navbar-inverse" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/">Kontextful!</a>
        </div>

        <div class="navbar-collapse collapse">
          
        </div><!--/.navbar-collapse -->
      </div>
    </div>
    
        <div class="jumbotron">
      <div class="container">
        
        <h3><a href="https://chrome.google.com/webstore/detail/kontextful/cbbogheealndegjieajmafgjjldfmpcf" target="_blank">Download Chrome extension</a></h3>
        <div><?=$msg ?></div>
        <!--
		<h3>Install our Chrome plugin in 3 easy steps:</h3>
		<ol>
			<li>Click this</li>
			<li>Allow</li>
			<li><?=$msg ?></li>
		</ol>
		-->

      </div>
    </div>
    
    <div class="container">
     <footer>
        <p>&copy; Kontextful. Patents pending. Palo Alto, CA 2014</p>
      </footer>
    </div> <!-- /container -->


    	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.0.min.js"><\/script>')</script>

        <script src="js/vendor/bootstrap.min.js"></script>

        <script src="js/plugins.js"></script>
        

        <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
        <script>
            (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
            function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
            e=o.createElement(i);r=o.getElementsByTagName(i)[0];
            e.src='//www.google-analytics.com/analytics.js';
            r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
            ga('create','UA-XXXXX-X');ga('send','pageview');
        </script>

    </body>
</html>
