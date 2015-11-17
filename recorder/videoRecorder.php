<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Video recorder</title>
    <style type="text/css">
        body {
            background-color:#f00;
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
    <script type="text/javascript" src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script>
    	
/*
		var constraints = {
		  video: {
		    mandatory: {
		      maxWidth: 640,
		      maxHeight: 360
		    },
		    optional: [{sourceId: videoSource}]
		  },
		  audio: {
	      	optional: [{sourceId: audioSource}]
	      }
		};
*/
		var gUM = Modernizr.prefixed('getUserMedia', navigator);
		function init(){
			if (hasGetUserMediaAvoidVendor()){
				$("#oldWay").hide();
				$( "#videoElementsContainer" ).append( "<h1>getUserMedia() is supported on this browser!</h1>" );
				$( "#videoElementsContainer" ).append( "<video id=\"video\" width=\"320\" height=\"240\" autoplay></video>" );
				$( "#videoElementsContainer" ).append( "<button id=\"getMediaSourcesBtn\" onclick=\"getMediaSources();\">getMediaSources()</button>" );
			}else{
				$( "#videoElementsContainer" ).append( "<h1>getUserMedia() is NOT supported on this browser...</h1>" );
				$("#oldWay").show();
			}
		}

		function successGettingCamcorder(localMediaStream){
 	    	video.src = window.URL.createObjectURL(localMediaStream);
	    	// Note: onloadedmetadata doesn't fire in Chrome when using it with getUserMedia.
	    	// See crbug.com/110938.
	    	video.onloadedmetadata = function(e) {
	       		// Ready to go. Do some stuff.
	     	};
    	}

    	function fallbackGettingCamcorder(e) {
    		console.log('Reeeejected! fallbackGettingCamcorder(): ', e);
		  	video.src = 'fallbackvideo.webm';
		}

    	function activateCamcorder(){
			var video = document.querySelector('video');
			if (hasGetUserMediaAvoidVendor()){
				var constraints = { video: true, audio: true };
				gUM(constraints, successGettingCamcorder, fallbackGettingCamcorder);
			} else {
				video.src = 'somevideo.webm'; // fallback.
			}
    	}

    	function getMediaSources(){
    		if (typeof MediaStreamTrack === 'undefined'){
				alert('This browser does not support MediaStreamTrack.\n\nPlease, use a decent browser.');
			} else {
				MediaStreamTrack.getSources(function(sourceInfos) {
					var audioSource = null;
					var videoSource = null;
	
					for (var i = 0; i != sourceInfos.length; ++i) {
					  	var sourceInfo = sourceInfos[i];
						if (sourceInfo.kind === 'audio') {
					      console.log(sourceInfo.id, sourceInfo.label || 'microphone');
	
					      audioSource = sourceInfo.id;
					    } else if (sourceInfo.kind === 'video') {
					      console.log(sourceInfo.id, sourceInfo.label || 'camera');
	
					      videoSource = sourceInfo.id;
					    } else {
					      console.log('Some other kind of source: ', sourceInfo);
					    }
					    if (audioSource != null  &&  videoSource != null){
						    break;
					    }
					  }

				  	sourceSelected(audioSource, videoSource);
				});
			}
    	}

    	function sourceSelected(audioSource, videoSource){
    		var constraints = {
    				  video: {
    				    mandatory: {
    				      maxWidth: 640,
    				      maxHeight: 360
    				    },
    				    optional: [{sourceId: videoSource}]
    				  },
    				  audio: {
    			      	optional: [{sourceId: audioSource}]
    			      }
    				};
    		//navigator.getUserMedia(constraints, successGettingCamcorder, fallbackGettingCamcorder);
    		gUM(constraints, successGettingCamcorder, fallbackGettingCamcorder);
    	}

    </script>
</head>
<body>
	<div id="oldWay">
		<h2>Upload image from device=camera</h2>
		<input type="file" accept="image/*;capture=camera">

		<h2>Upload video from device=camcorder</h2>
		<input type="file" accept="video/*;capture=camcorder">

		<h2>Upload audio from device=microphone</h2>
		<input type="file" accept="audio/*;capture=microphone">
	</div>
	<div id="videoElementsContainer">

	</div>
	
	<script>
		$( document ).ready(function() {
		    init();
		});
	</script>
</body>
</html>