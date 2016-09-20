var invitation_submit = function() {
			$('#form_invitation_code').val($('#invitation_code').val());
			$('#invite_modal').modal('hide');
			// alert($('#form_invitation_code'));
			// alert(document.forms[0].invitation_code.value);
			$('#start').click();
}

var is_modal_shown = function() {
	//var shown_start = false;
	var shown_have_invite = false;
	/*if($("#start").css('visibility') != "hidden" && $("#start").css('display') != "none")
		shown_start = true;*/
	if($(".modal").css('visibility') != "hidden" && $(".modal").css('display') != "none")
		shown_have_invite = true;
	if(/*shown_start||*/shown_have_invite)
		return true;
	else
		return false;
}

$(document).ready(function()
    {
		var show_video = function() {
			var vright = Math.round($('.jumbotron .container').position().left + $('.jumbotron .container').width());
			var vbottom = Math.round($('.jumbotron').position().top + $('.jumbotron').outerHeight());
			$('#animation').css("left", vright - $('#animation').width() );
			$('#animation').css("top", vbottom - $('#animation').height() );
			if(!is_modal_shown())
				$('#animation').show();
		}
		window.setInterval(show_video, 3000);
	
        var l1 = Ladda.create(document.querySelector("#start"));
        $("#start").click(function(event) {
        	if(!l2.isLoading())
        		l1.start();
        	var error_msg = "There was a problem logging you in. Please close this window and try again.";
            FB.login(function(response) {
                if(response.status=='connected') {
                    if (response.authResponse) {
                        console.log('connected');
                        login(response);
                    }
                    else {
                        console.log('x 2');
                        alert(error_msg);
                        if(!l2.isLoading())
                        	l1.stop();
                        else
                        	l2.stop();
                    }
                }
                else if(response.status=='not_authorized') {
                    console.log('not authorized');
                    // change the content into something else.
                    alert(error_msg);
                    if(!l2.isLoading())
                    	l1.stop();
                    else
                    	l2.stop();
                }
                else {
                    console.log('x 1');
                    alert(error_msg);
                    if(!l2.isLoading())
                    	l1.stop();
                    else
                    	l2.stop();
                }
            }, {scope: 'public_profile,email,user_groups,publish_actions'});
        });
        
        
        var l2 = Ladda.create(document.querySelector("#have_invite"));
        $("#have_invite").click(function(event) {
        	l2.start();
        	$('#animation').hide();
        	$("#invite_modal").modal('show');
        	setTimeout(l2.stop, 3000);
        });

    }

);

function login(parent_response) {
    console.log('Welcome!  Fetching your information.... ');
    FB.api('/me', function(response) {
        console.log(response);
        document.forms['poster'].first_name.value = response.first_name;
        document.forms['poster'].username.value = response.username;
        document.forms['poster'].facebook_id.value = parent_response.authResponse.userID;
        document.forms['poster'].access_token.value = parent_response.authResponse.accessToken;
        document.forms['poster'].submit();
    });
}

