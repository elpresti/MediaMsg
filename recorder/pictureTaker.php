<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>

	<style>
		.snapButton{
			    /* background: none repeat scroll 0% 0% rgba(231, 105, 105, 0.6); */
				background: none repeat scroll 0% 0% #F9F9F9;
				display: inline-block;
				padding: 6px 14px;
				border: 1px solid #EEE;
				margin: 0px 10px 10px 0px;
				text-decoration: none;
				cursor: pointer;
				color: #07A;
				font-family: "freight-text-pro",Georgia,Cambria,"Times New Roman",Times,serif;
				font-size: 22px;
				line-height: 1.5;
				letter-spacing: 0.01rem;
				font-weight: 400;
		}
		.snapButton:hover{
			    /* background: none repeat scroll 0% 0% rgba(231, 125, 125, 0.9); */
				background: none repeat scroll 0% 0% #F9F9F0;
		}
		#statusMsg{
				padding: 6px 14px;
				border: 1px solid #EEE;
				margin: 0px 10px 10px 0px;
				color: #07A;
				font-family: "freight-text-pro",Georgia,Cambria,"Times New Roman",Times,serif;
				font-size: 22px;
				line-height: 1.5;
				letter-spacing: 0.01rem;
				font-weight: 400;
		}
	</style>


<?php 
	if (isset($_GET['filename']) &&  (strlen($_GET['filename'])>0) ) {
		$filename = $_GET['filename'];
	}
	require('util/common.php');
?>

<script type="text/javascript"
	src="https://code.jquery.com/jquery-1.11.2.min.js"></script>

<script type="text/javascript" src="js/common.js"></script>

<script>
	
	// Put event listeners into place
		window.addEventListener("DOMContentLoaded", function() {
			// Grab elements, create settings, etc.
			var canvas = document.getElementById("canvas"),
				context = canvas.getContext("2d"),
				video = document.getElementById("video"),
				videoObj = { "video": true },
				errBack = function(error) {
					console.log("Video capture error: ", error.code); 
				};

			// Put video listeners into place
			if(navigator.getUserMedia) { // Standard
				navigator.getUserMedia(videoObj, function(stream) {
					video.src = stream;
					video.play();
				}, errBack);
			} else if(navigator.webkitGetUserMedia) { // WebKit-prefixed
				navigator.webkitGetUserMedia(videoObj, function(stream){
					video.src = window.webkitURL.createObjectURL(stream);
					video.play();
				}, errBack);
			} else if(navigator.mozGetUserMedia) { // WebKit-prefixed
				navigator.mozGetUserMedia(videoObj, function(stream){
					video.src = window.URL.createObjectURL(stream);
					video.play();
				}, errBack);
			}

			// Trigger photo take
			document.getElementById("snapBtn").addEventListener("click", function() {
				canvas.width = video.width;
				canvas.height = video.height;
				context.drawImage(video, 0, 0, video.width, video.height);
				showPreview(true);
			});
			
			// Take new photo
			document.getElementById("discardBtn").addEventListener("click", function() {
				showPreview(false);
			});
			
		}, false);
		
	function showPreview(showIt){
		if (showIt){
			$('#video').hide();
			$('#snapBtn').hide();
			$('#uploadBtn').show();
			$('#discardBtn').show();
			$('#canvas').show();
			$('#newSnapBtn').hide();
		}else{
			$('#video').show();
			$('#snapBtn').show();
			$('#uploadBtn').hide();
			$('#discardBtn').hide();
			$('#canvas').hide();
			$('#newSnapBtn').hide();
		}
	}
	
	function showOnlyTakeNewBtn(){
		$('#video').hide();
		$('#snapBtn').hide();
		$('#uploadBtn').hide();
		$('#discardBtn').hide();
		$('#canvas').hide();
		$('#newSnapBtn').show();
	}
	
	function uploadSnapShot(){
		changeStatusMsg("Subiendo la foto...");
		var imgData = convertCanvasToImage(canvas);
		$.ajax({ 
			type: "POST", 
			url: "ftpUploader.php?mediaType=3&filename=<?php echo getFileName(3); ?>",
			datatype: 'image/png',
			data: {
				imageData : imgData.currentSrc
			},
			success: function (data) {
				//$('#CaptchaImg').attr('src', data);
				var jsonResponse = JSON && JSON.parse(data) || $.parseJSON(data);
				if (jsonResponse.statusCode==200){
					showOnlyTakeNewBtn();
					changeStatusMsg("Archivo subido!");
				}else{
					changeStatusMsg("Error! -> Details: "+jsonResponse);
				}
			}
		});
	}
	

	$( document ).ready(function() {
		showPreview(false);
	});
	
	
	
</script>
</head>

<body>
	
	<div id="mainContainer" >

		<video id="video" width="640" height="480" autoplay></video>
		<canvas id="canvas" width="640" height="480" ></canvas>
		<button id="snapBtn" class="snapButton">Sacar foto!</button>
		<button id="discardBtn" class="snapButton" >Descartar</button>
		<button id="newSnapBtn" class="snapButton" onClick="window.location.reload()">Tomar otra foto</button>
		<!-- <a	id="uploadBtn" class="snapButton" href="ftpUploader.php?filename=<?php echo getFileName(3); ?>">Enviar!</a><br /> -->
		<a	id="uploadBtn" class="snapButton" href="#" onclick="uploadSnapShot();">Enviar!</a>
		<p id="statusMsg">Status: No message</p>
	</div>
	
</body>

</html>
	