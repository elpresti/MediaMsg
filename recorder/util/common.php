<?php
	error_reporting(E_ALL ^ E_WARNING);
	
	date_default_timezone_set('America/Argentina/Buenos_Aires');

	function getFileName($mediaType){//mediaType: 1=AUDIO, 2=VIDEO, 3=PICTURE
		global $filename;
		if (isset($filename) && (strlen($filename)>0) ) {
			$posPunto = strrpos($filename, ".");
			if (!($posPunto === false)) {
				$filename = substr( $filename, 0, $posPunto );
			}
		}else{
			$hoy = date("Y_m_d__H_i_s");
			$filename="Msg_".$hoy;
		}
		if ($mediaType==1){
			$filename.=".wav";
		}else{
			if ($mediaType==2){
				$filename.=".webm";
			}else{
				if ($mediaType==3){
					$filename.=".png";
				}else{
					if ($mediaType==4){
						$filename.=".mp3";
					}
				}
			}
		}
		return $filename;
	}
	
?>