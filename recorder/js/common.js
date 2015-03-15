
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
	
	function validatePictureTaken(){
		//TODO
		return true;
	}
	
	function convertCanvasToImage(canvas) {
		var image = new Image();
		image.src = canvas.toDataURL("image/png");
		return image;
	}
	
	function convertImageToCanvas(image) {
		var canvas = document.createElement("canvas");
		canvas.width = image.width;
		canvas.height = image.height;
		canvas.getContext("2d").drawImage(image, 0, 0);

		return canvas;
	}
	
	function changeStatusMsg(newMsg){
		$("#statusMsg").text(newMsg);
	}
	
	function hasGetUserMedia() {
	  return !!(navigator.getUserMedia || navigator.webkitGetUserMedia ||
	            navigator.mozGetUserMedia || navigator.msGetUserMedia);
	}

	function hasGetUserMediaAvoidVendor(){
		var gUM = Modernizr.prefixed('getUserMedia', navigator);
		if (gUM != undefined  &&  gUM != null){
			return true;
		}else{
			return false;
		}
	}


	function getURLParametersOfJsRef() {
	    var scripts = document.getElementsByTagName('script');
	    var myScript = scripts[ scripts.length - 1 ];
	    var query = myScript.src.replace(/^[^\?]+\??/,'');
	    var Params = new Object ();
	    if ( ! query ) return Params; // return empty object
	    var Pairs = query.split(/[;&]/);
	    for ( var i = 0; i < Pairs.length; i++ ) {
	      var KeyVal = Pairs[i].split('=');
	      if ( ! KeyVal || KeyVal.length != 2 ) continue;
	      var key = unescape( KeyVal[0] );
	      var val = unescape( KeyVal[1] );
	      val = val.replace(/\+/g, ' ');
	      Params[key] = val;
	    }
	    return Params;
	}