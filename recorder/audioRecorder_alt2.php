<html>
	<head>
		<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
	    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
	    <script type="text/javascript" src="js/common.js"></script>
	    <script type="text/javascript" src="js/recorder_lib.js"></script>
	    <script type="text/javascript" src="js/html5AudioRecorder.js"></script>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Live input record and playback</title>
	  	<style>
			html { overflow: hidden; }
			body { 
				font: 14pt Arial, sans-serif; 
				background: lightgrey;
				display: flex;
				flex-direction: column;
				height: 100vh;
				width: 100%;
				margin: 0 0;
			}
			#controls {
				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: space-around;
				height: 20%;
				width: 100%;
			}
			#record { height: 15vh; }
			#record.recording { 
				background: red;
				background: -webkit-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%); 
				background: -moz-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%); 
				background: radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%); 
			}
			#save, #save img { height: 10vh; }
			#save { opacity: 0.25;}
			#save[download] { opacity: 1;}
			#viz {
				height: 80%;
				width: 100%;
				display: flex;
				flex-direction: column;
				justify-content: space-around;
				align-items: center;
			}
			@media (orientation: landscape) {
				body { flex-direction: row;}
				#controls { flex-direction: column; height: 100%; width: 10%;}
				#viz { height: 100%; width: 90%;}
			}

		</style>
		<script>
			<?php 
				if (isset($_GET['filename']) &&  (strlen($_GET['filename'])>0) ) {
					$filename = $_GET['filename'];
				}
				require('util/common.php');
			?>
			var filename="<?php echo getFileName(4); ?>";
		</script>
	</head>

	<body>
		<div id="controls">
			<img id="record" src="img/mic128x128.png" onclick="toggleRecording(this);">
			<a id="save" href="#"><img src="img/saveIcon.svg"></a>
			<button id="enableAudioInBtn" onclick="enableAudioInput();">Enable Audio Input</button>
			<button id="encodeAndUploadBtn" onclick="encodeAndUpload();">Encode and upload</button>
		</div>

	</body>
</html>