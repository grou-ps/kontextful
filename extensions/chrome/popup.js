// popup.js
/*
var graphUrl;
if (localStorage.accessToken) {
            graphUrl = "https://graph.facebook.com/me?" + localStorage.accessToken + "&callback=displayUser";
            //console.log(graphUrl);
 
            var script = document.createElement("script");
            script.src = graphUrl;
            if(document.body) {
	            document.body.appendChild(script);
	            function displayUser(user) {
	                console.log(user);
	            }
            }
        }
*/

/*
window.fbAsyncInit = function() {
            FB.init({
                appId      : '598576316931483',
                status     : false, // check login status
                cookie     : true, // enable cookies to allow the server to access the session
                xfbml      : false  // parse XFBML
            });
};

// Load the SDK asynchronously
(function(d){
	var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement('script'); js.id = id; js.async = true;
	js.src = "https://connect.facebook.net/en_US/all.js";
	ref.parentNode.insertBefore(js, ref);
}(document));

function post_to_fb_group(fb_group_id) {
	FB.api(
		    "/{group-id}/feed",
		    "POST",
		    {
		        "object": {
		            "message": "This is a test message"
		        }
		    },
		    function (response) {
		      if (response && !response.error) {
		      	// handle the result
		      }
		    }
		);
}
*/

function warn(msg) {

   document.getElementById("overlay").style.display="block";
   document.getElementById("overlay_content").innerHTML = msg;
  
}

function restore() {
	   document.getElementById("overlay").style.display="none";
}

function share(mode) {
	switch(mode) {
		case 1:
			document.getElementById('context_id').value = document.getElementById('share').getAttribute('data-cid');
			break;
		case 2:
			document.getElementById('context_id').value = document.getElementById('second').getAttribute('data-cid');
			break;
		case 0:
		default:
			document.getElementById('context_id').value = "0";
			break;
	}
	
	// document.getElementById('kontextful').submit();
	
	var postUrl = "http://kontextful.com/share.php";
	var xhr = new XMLHttpRequest();
    xhr.open('POST', postUrl, true);
    
    var title = encodeURIComponent(document.getElementById('title').value);
    var url = encodeURIComponent(document.getElementById('url').value);
    var context_id = encodeURIComponent(document.getElementById('context_id').value);
    var context_type = encodeURIComponent(document.getElementById('context_type').value);
    var comments = encodeURIComponent(document.getElementById('comments').value);

    var params = 'title=' + title + 
                 '&url=' + url + 
                 '&context_id=' + context_id +
                 '&context_type=' + context_type +
                 '&comments=' + comments;

    // Replace any instances of the URLEncoded space char with +
    params = params.replace(/%20/g, '+');

    // Set correct header for form data 
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() { 
        // If the request completed
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
               warn("Link shared!");
            } else {
                warn("There was an error...");
            }
        }
    };

    // Send the request and set status
    xhr.send(params);
    
    switch(mode) {
		case 1:
			document.getElementById('share').value = "Sharing...";
			var button_interval = setInterval(function() {
	    		document.getElementById('share').value = 
	    			document.getElementById('share').value == "Sharing..." ? " " : "Sharing...";
	    	}, 1000);
			break;
		case 2:
			document.getElementById('second').value = "Sharing...";
			var button_interval = setInterval(function() {
	    		document.getElementById('second').value = 
	    			document.getElementById('second').value == "Sharing..." ? " " : "Sharing...";
	    	}, 1000);
			break;
		case 0:
		default:
			document.getElementById('everyone').value = "Sharing...";
		var button_interval = setInterval(function() {
    		document.getElementById('everyone').value = 
    			document.getElementById('everyone').value == "Sharing..." ? " " : "Sharing...";
    	}, 1000);
			break;
	}
	
}

window.onload = function() {
	
	setTimeout(function(){document.getElementById('comments').focus();}, 500);

	document.getElementById('share').addEventListener('click', function() {share(1);});
	document.getElementById('second').addEventListener('click', function() {share(2);});
	document.getElementById('everyone').addEventListener('click', function() {share(0);});
	
    chrome.tabs.getSelected(null,function(tab) {
    	
    	document.getElementById('title').value = tab.title;
    	document.getElementById('url').value = tab.url;
    	var button_interval = setInterval(function() {
    		document.getElementById('share').value = 
    			document.getElementById('share').value == "... Thinking ..." ? " " : "... Thinking ...";
    		document.getElementById('second').value = document.getElementById('share').value;
    	}, 1000);
    	document.getElementById('others').href = "http://facebook.com/sharer/sharer.php?u="+encodeURIComponent(tab.url);
    	
    	
    	var xhr = new XMLHttpRequest();
    	xhr.open("GET", "http://kontextful.com/button.php?analyze="+encodeURIComponent(tab.url)+"&title="+encodeURIComponent(tab.title), true);
    	xhr.onreadystatechange = function() {
    	  if (xhr.readyState == 4) {
    	    // JSON.parse does not evaluate the attacker's scripts.
    		  
    	    var resp = JSON.parse(xhr.responseText);
    	    console.log(resp);
    	    if(resp.error!=undefined ) {
    	    	switch( resp.error) { // not logged in.
	    	    	case 2:
	    	    		warn("You are not logged in. Please <a href=\"http://kontextful.com/login.hh\" target=\"_blank\">log in</a> first.");
	    	    		break;
	    	    	case 3:
	    	    		warn("Looks like you're still in the waiting list. We'll let you know when we're ready to let you in. Thanks for your patience!");
	    	    		break;
	    	    	default:
	    	    		warn("An exception occurred. You may want to <a href=\"http://kontextful.com/login.hh\" target=\"_blank\">log in</a> and try again. If the problem persists, feel free to contact us at emre@kontextful.com");
    	    			break;
    	    	}
    	    }
    	    else if(resp.success != undefined) {
    	    	if(resp.success[0]['title'] != undefined)
    	    		document.getElementById('share').value =  resp.success[0]['title'];
    	    	else
    	    		document.getElementById('share').disabled=true;
    	    	document.getElementById('share').setAttribute('data-cid', resp.success[0]['id']);
    	    	if(resp.success[1]['title'] != undefined)
    	    		document.getElementById('second').value = resp.success[1]['title'];
    	    	else
    	    		document.getElementById('second').disabled=true;
    	    	document.getElementById('second').setAttribute('data-cid', resp.success[1]['id']);
    	    	clearInterval(button_interval);
    	    }
    	    /*
    	    document.getElementById('main_title').innerText = resp.title;
    	    document.getElementById('main_img').src = resp.image;
    	    document.getElementById('main_description').innerText = resp.description;
    	    document.getElementById('urlis').innerText  = resp.title; // "emre"; //resp; */
    	  }
    	}
    	xhr.send();
    	
    	
    }
    
 );
    
    
    
    
    
    
}