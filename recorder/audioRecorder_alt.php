<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<!-- swfobject is a commonly used library to embed Flash content -->
<script type="text/javascript"
	src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>

<script type="text/javascript"
	src="https://code.jquery.com/jquery-1.11.2.min.js"></script>

<!-- Setup the recorder interface -->
<script type="text/javascript" src="js/recorder_wami.js"></script>

<!-- GUI code... take it or leave it -->
<script type="text/javascript" src="gui.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>

<script type="text/javascript" src="js/common.js"></script>

<script>
	
/*
	function setupRecorder() {
		Wami.setup({
			id : "wami",
			onReady : setupGUI
		});
	}

	function setupGUI() {
		var gui = new Wami.GUI({
			id : "wami",
			//recordUrl : "https://wami-recorder.appspot.com/audio",
			recordUrl : "audio/server.php",
			playUrl : "audio/output.wav"
		});

		gui.setPlayEnabled(false);
	}
*/

  <?php
  		date_default_timezone_set('America/Argentina/Buenos_Aires');
  		$hoy = date("Y-m-d__H_i_s");
      $filename="Msg_".$hoy.".wav";
  ?>
	function setupRecorder() {
		Wami.setup({
			id : "wami",
			onReady : setupGUI
		});
	}

	function setupGUI() {
		var gui = new Wami.GUI({
			id : "wami",
			//recordUrl : "https://wami-recorder.appspot.com/audio",
			recordUrl : "messages/server.php?name=<?php echo $filename ?>",
			playUrl : "messages/<?php echo $filename ?>"
		});

		gui.setPlayEnabled(false);
		
	}
	
	function validateAudioRecording(){
		//TODO
		return true;
	}
	
	function enableUpload(state){
		if (state){
			//enable upload button
			$('#uploadBtn').show();
		}else{
			//disable upload button 
			$('#uploadBtn').hide();
		}
	}
	
	$( document ).ready(function() {
		$('#uploadBtn').hide();
	});
	
	
</script>
</head>

<body onload="setupRecorder()">
	<div id="wami" style="margin-left: 100px;"></div>
	<noscript>WAMI requires Javascript</noscript>

	<div
		style="position: absolute; left: 400px; top: 20px; font-family: arial, sans-serif; font-size: 82%">
		Right-click to Download<br /> <br /> <a
			href="https://wami-recorder.googlecode.com/hg/example/client/index.html">index.html</a><br />
		<a
			href="https://wami-recorder.googlecode.com/hg/example/client/Wami.swf">Wami.swf</a><br />
		<a
			href="https://wami-recorder.googlecode.com/hg/example/client/buttons.png">buttons.png</a><br />
		<a
			href="https://wami-recorder.googlecode.com/hg/example/client/recorder.js">recorder.js</a><br />
		<a
			href="https://wami-recorder.googlecode.com/hg/example/client/gui.js">gui.js</a><br />

		<a	id="uploadBtn" href="ftpUploader.php?mediaType=1&filename=<?php echo $filename; ?>">UPLOAD FILE!!</a><br />

	</div>
</body>
</html>
	