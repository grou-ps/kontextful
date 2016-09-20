<?php
include(__DIR__.'/Auth.class.php');
session_start();
session_destroy();

if(isset($_GET['login']) && $_GET['login'] == "yes" && isset($_GET['facebook_id']) && isset($_GET['access_token'])) {
	error_log("in the login with: ".print_r($_GET,true));
	$auth = new Auth('facebook',(int)$_GET['facebook_id'],$_GET['access_token']);
	
	if(!$auth->is_logged_in())
		die(json_encode(array("status"=>"fail")));
	
	error_log("should be ok, the user id is:".$_SESSION['uid']);	
	die(json_encode(array("status"=>"success")));
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
    
<script>
	
  
  // This is called with the results from from FB.getLoginStatus().
  function statusChangeCallback(response) {
    console.log('statusChangeCallback');
    console.log(response);
    // The response object is returned with a status field that lets the
    // app know the current login status of the person.
    // Full docs on the response object can be found in the documentation
    // for FB.getLoginStatus().
    if (response.status === 'connected') {
      // Logged into your app and Facebook.
      $.get("?login=yes&facebook_id="+response.authResponse.userID+"&access_token="+response.authResponse.accessToken, function( data ) {
      		// console.log(data);
      		if(data.status=="success")
      			document.getElementById('status').innerHTML = 'You are logged in. If you don\'t have an extension installed, please go to <a href="http://kontextful.com/extensions.hh">http://kontextful.com/extensions.hh</a>';
      		else
    			alert( "There was a problem. Please refresh this page and try again. If the problem persists, please contact us at emre@kontextful.com");
  		}, "json");
       
    } else if (response.status === 'not_authorized') {
      // The person is logged into Facebook, but not your app.
      document.getElementById('status').innerHTML = 'Please <a href="#" onclick="history.go(0)">reload</a> this page and log ' +
        'into this app.';
    } else {
      // The person is not logged into Facebook, so we're not sure if
      // they are logged into this app or not.
      document.getElementById('status').innerHTML = 'Please log ' +
        'into Facebook.';
    }
  }

  // This function is called when someone finishes with the Login
  // Button.  See the onlogin handler attached to it in the sample
  // code below.
  function checkLoginState() {
    FB.getLoginStatus(function(response) {
      statusChangeCallback(response);
    });
  }

  window.fbAsyncInit = function() {
  FB.init({
    appId      : '598576316931483',
    cookie     : true,  // enable cookies to allow the server to access 
                        // the session
    xfbml      : true,  // parse social plugins on this page
    version    : 'v2.0' // use version 2.0
  });

  // Now that we've initialized the JavaScript SDK, we call 
  // FB.getLoginStatus().  This function gets the state of the
  // person visiting this page and can return one of three states to
  // the callback you provide.  They can be:
  //
  // 1. Logged into your app ('connected')
  // 2. Logged into Facebook, but not your app ('not_authorized')
  // 3. Not logged into Facebook and can't tell if they are logged into
  //    your app or not.
  //
  // These three cases are handled in the callback function.



FB.logout(function(response) {
  // user is now logged out
   FB.getLoginStatus(function(response) {
    statusChangeCallback(response);
  });
  
  });

 

  };

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));


</script>

    
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
        
        <h3>Please log in via Facebook:</h3>
        <div id="status"><fb:login-button size="xlarge" show_faces="false" max_rows="1" scope="public_profile,email,user_groups,publish_actions" onlogin="checkLoginState();"></fb:login-button></div>

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