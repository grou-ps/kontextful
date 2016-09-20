<?php
    session_start();
    /*if(isset($_SESSION['uid'])) {
        print_r($_SESSION);
        exit;
    }*/
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

	

    <div id="fb-root"></div>
    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId      : '598576316931483',
                status     : true, // check login status
                cookie     : true, // enable cookies to allow the server to access the session
                xfbml      : true  // parse XFBML
            });
        };

        // Load the SDK asynchronously
        (function(d){
            var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement('script'); js.id = id; js.async = true;
            js.src = "http://connect.facebook.net/en_US/all.js";
            ref.parentNode.insertBefore(js, ref);
        }(document));

    </script>



    <!-- Modals -->
    
    <div class="modal fade" id="invite_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Please enter your invitation code:</h4>
                </div>
                <div class="modal-body">
                   <div class="form-group">
                    	<input type="text" class="form-control" id="invitation_code" placeholder="Example: AB03QD_3">
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <!--<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>-->
                  	<button type="button" class="btn btn-default" onclick="invitation_submit()">Send</button>
                </div>
            </div>
        </div>
    </div>
    
    <iframe id="animation" src="animation/index.html" style="display:none;width:540px;height:190px;position:absolute;z-index:2001;border:none;"></iframe>

    <!-- hidden form for POST redirect -->
    <form method="POST" style="display:none;" action="auth.hh" name="poster">
        <input type="hidden" name="first_name" />
        <input type="hidden" name="username" />
        <input type="hidden" name="facebook_id" />
        <input type="hidden" name="access_token" />
        <input type="hidden" name="invitation_code" id="form_invitation_code" />
    </form>

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
          <form class="navbar-form navbar-right" role="form">
            <div class="form-group">
                
				<div align="top" style="padding:0;font-size:16px;line-height:16px;font-family:verdana;color:#f00;font-weight:bold;margin-top:10px;height:16px;"><img src="img/motto.png" style="height:15px;width:203px;padding:0;margin:0;visibility:hidden;"></div>
          </form>
        </div><!--/.navbar-collapse -->
      </div>
    </div>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
        <h1>Share in relevance &amp; delight</h1>
        <p>Kontextful is a Chrome button that lets you share easily with relevant people in your social networks
           <!--<br>We analyze the circles in your social network(s) and help you decide who to share with.
            <br>Results: (1) Friends not spammed. (2) Newsfeeds all clean. -->
        <!-- plugin that allows you to share web pages you like with only the relevant group of people. -->
        </p>
        <p><a class="btn btn-primary btn-lg ladda-button" data-style="expand-left" role="button" id="start">Request an invite</a> 
        <a class="btn btn-default btn-lg ladda-button" data-style="expand-left" role="button" id="have_invite">Have a code? Get Started &raquo;</a></p>
      </div>
    </div>



    <div class="container">
      <!-- Example row of columns -->
      
      
      
      <div class="row">
        <div class="col-md-8">
          <h2><a name="forewords">Forewords</a></h2>
          <p>Kontextful is a research project from GROU.PS Inc. At GROU.PS, our mission is to make the social web more contextual. Kontextful will help us in this mission by enabling public social network users to share more granularly. For more information about our motivations, please read <a href="#rationale">Rationale</a>.</p>
          <p>If you are interested in being one of our beta subscribers please click on the "Request An Invite" link above. Please note that this research project requires a lot of computing resources, hence we are only opening this to a select few for now. We will be accepting more people in gradually and welcome you to subscribe to our wait list. Our aim is to open to the public by the end of 2014 Q3. Thanks for your understanding and interest!</p> 
        </div>
        
        <div class="col-md-4">
          <h2><a name="browsers">Supported Browsers</a></h2>
          <p><img src="img/browser_support.png" style="width:356px; height: 90px;"></p>
        </div>
      </div>
      
      
      
      
      <div class="row">
        <div class="col-md-8">
        
          <h2><a name="rationale">Rationale</a></h2>
          <p>The biggest problem that's facing macro social networks such as Facebook, Twitter, LinkedIn, and Google+ is that, as you add more friends or follow more people, the context of your feed becomes broader and broader. While you may be interested in personal sharings of a college friend of yours, seeing their constant work-related status updates may be of little interest to you. This type of shares ultimately has has a negative impact on you, your friends, and the social network itself. We try to solve this problem by making sure your shared content, as a social network node, always goes to relevant circles. While Facebook, Google+ and many others offer circle features such as Groups and Friend Lists, many users prefer to skip an extra step to select who to share their status updates with, hence the updates including page likes go to all of their followers - which results in social network pollution.</p>
          <p>Our aim is to solve this problem with advanced NLP, Big Data methods and our exclusive patented semantic technologies.</p>
          
          <h2><a name="how">How it works?</a></h2>
	      <p>Kontextful uses smart algorithms in order to predict what social network, group, friend list, or circle to share this particular content you've selected with. The techniques used by Kontextful are partially inspired by k-means clustering, and that's where our name derives from.</p>
        
        </div>
        
        <div class="col-md-4">
          <h2><a name="about">About us</a></h2>
          <p><img src="img/emre.jpeg" style="-webkit-border-radius: 100%;-moz-border-radius: 100%;border-radius: 100%;width:62px; height:62px" align="right">We are a group of people who are dedicated to make the social web spam-free, more contextual and more meaningful. The team is led by the inventor of Kontextful and Grou.ps chief architect, <a href="http://medium.com/@emresokullu">Emre Sokullu</a>.</p>
          <p>Kontextful frontend is written in the hack language, backend in PHP and Java. Some of our work is open-sourced, including this ultra-fast in-memory Wordnet server, wordnetd and the MVC framework that this site runs on, hack-mvc. All others are patented.</p>
          <p>We heavily rely on Ubuntu Linux, MySQL, RabbitMQ and Redis. Thanks all the nice folks at Zend, Facebook, Oracle, Pivotal Labs, Canonical and of course, not to mention, the open source community.</p>
        </div>
      </div>

        <div class="row">
            <div class="col-md-8">
	          
	       	</div>
        </div>
        
        <div class="row">
        	<div class="col-md-8">
	        	<h2><a name="limitations">Limitations</a></h2>
                <p>In this beta version, we don't support:</p>
                <ul>
                	<li>Frames and content that is asychronously loaded via AJAX calls.</li>
                	<li>Most HTTP redirects</li>
                </ul>
                <h2><a name="roadmap">Roadmap</a></h2>
                <p>We're planning to add more services and contexts into the list of services provided. We currently support Facebook Groups and GROU.PS networks. There are some technicalities with supporting other social networks or other social network features, such as Facebook Friend Lists, but we believe we'll handle these with the nice folks at Facebook and Google soon. Next up, we'll bring in LinkedIn Groups.</p>
                <p>As for browsers, currently we only support Chrome but Safari and Firefox will be next. And then Internet Explorer will follow.</p>
            </div>
            <div class="col-md-4">
                <h2><a name="free">Is this free?</a></h2>
                <p>Yes, while our <a href="http://grou.ps">flagship product</a> is paid-only, Kontextful should and will always remain free. </p>
            </div>
		</div>

      <hr>

      <footer>
        <p>&copy; Kontextful. Patents pending. Palo Alto, CA 2014</p>
      </footer>
    </div> <!-- /container -->


    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.0.min.js"><\/script>')</script>

        <script src="js/vendor/bootstrap.min.js"></script>

        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>

    <script src="js/spin.min.js"></script>
    <script src="js/ladda.min.js"></script>

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
