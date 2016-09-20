<?php
/**
 * Main panel page after login
 */
session_start();
if (!$_SESSION["admin"]){
    header("Location:login.php");
}

if ($_GET["op"]=="logout"){
    unset($_SESSION["admin"]);
    header("Location:login.php");
}

//$url = "http://localhost";
$url = "http://www.kontextful.com/panel";
$services = array('fetch', 'structure', 'tokenize', 'augment', 'stem', 'score');
?>
<html>
<head>
    <style>
        body, html, table{
            font-family: "Verdana";
            font-size: 12px;
        }
    </style>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
</head>
<body>
	<strong>Jobs</strong>
	<div align="left" id="jobsdiv">
		<?php
		//get jobs
		
		/*
		$content = json_decode(file_get_contents($url."/progress.php"), "true");
		$jobs = array();
		echo "<strong>Jobs</strong><br />";
		foreach($content as $key=>$value){
			if (!in_array($key, $jobs)){
				$jobs[] = $key;
				echo "<a href='index.php?job=$key'>$key</a><br />";
			}
		}

		$selectedJob = isset($_GET['job']) ? $_GET['job'] : $jobs[0];
		*/
		?>

	</div>

    <div align="right">
        <a href="index.php?op=logout">Logout</a><br />
        Checking: <span id="count_label">0</span>
    </div>

    <table width="500" border="0">
        <tr>
            <td width="%40">&nbsp;</td>
            <td width="%40"><strong>Current</strong></td>
            <td width="%10"><strong>Total</strong></td>
            <td width="%10"><strong>Completed</strong></td>
        </tr>
        <?php
        foreach ($services as $service){
        ?>
            <tr>
                <td><strong><?php echo ucwords($service)?></strong></td>
                <td><img id="<?php echo $service?>_progress" src="images/progress.png" width="0" height="10">&nbsp;<span id="<?php echo $service?>_current"></span> <span id="<?php echo $service?>_percentage"></span></td>
                <td><span id="<?php echo $service?>_total"></span></td>
                <td><span id="<?php echo $service?>_completed"></span></td>
            </tr>
        <?}?>
    </table>

    <script type="text/javascript">
        $(document).ready(function(){
        
			function GetURLParameter(sParam){
				var sPageURL = window.location.search.substring(1);
				var sURLVariables = sPageURL.split('&');
				for (var i = 0; i < sURLVariables.length; i++){
					var sParameterName = sURLVariables[i].split('=');
					if (sParameterName[0] == sParam){
						return sParameterName[1];
					}
				}
			}
        
            function getData(){
                count = $("#count_label").html();
                count = parseInt(count) + 1;
                $("#count_label").html(count);

				var selectedJob = GetURLParameter('job');

                $.ajax({
                    type: "GET",
                    url: "progress.php",
                    dataType: "json",
                    error: function(data){
                    },
                    success: function(json){
                    	$("#jobsdiv").empty();
                        $.each(json, function(i,s){

                        	//Update job list periodically here
                        	//by updating jobs div
                        	

                        	$("#jobsdiv").append('<a id=' + i + ' href=index.php?job=' + i + '>' + i + '</a><br />');
                        	
                        	if (selectedJob === undefined) selectedJob = i;
                        	$("#" + selectedJob).css('font-weight', 'bold')
                        	
                        	if (i==selectedJob){
                        	//if (i=='<?php echo $selectedJob?>'){
								
                            	$.each(s, function(a,n){ //fetch {}
                            		
                            		$.each(n, function(process,val){ //like : total = 40
                                    	$("#" + a + '_' + process).html(val);
                                    });
                                
                                	//total should not be 0, no infinity
                            		if (parseInt($("#" + a + '_total').html())!=0) {
                                		percentage = parseInt($("#" + a + '_current').html()) * 100 / parseInt($("#" + a + '_total').html());
                                		$("#" + a + '_percentage').html("%" + percentage.toFixed(1));
                                		$("#" + a + '_progress').width(percentage.toFixed(1));
                            		}
                            	});
							}
                        });
                    }
                });
            }
            
            getData();
            setInterval(function(){ getData(); }, 5000);

        });
    </script>
</body>
</html>