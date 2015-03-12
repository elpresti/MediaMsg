<?php
//comment 3 - recent import cmd
// comment 5
final class FileManager{
    private $_ffmpegManagerClass = null;
    public $_outMsg="NO MESSAGE";
    public $_outStatusCode=500;
    private $_fileName=null;
    private $_ftp_server="201.219.68.21";
    private $_ftp_user_name="voicemsg";
    private $_ftp_user_pass="voicemsg";	


    public static function getInstance($_ffmpegManagerClass){
        static $inst = null;
        if ($inst === null) {
            $inst = new FileManager($_ffmpegManagerClass);
        }
        return $inst;
    }

    private function __construct($_ffmpegManagerClass){
        $this->_ffmpegManagerClass = $_ffmpegManagerClass;
    }

	public function saveTmpImageFile($data){
		//$data = $_POST["imageData"];
		list($type, $data) = explode(';', $data);
		list(, $data)      = explode(',', $data);
		$data = base64_decode($data);
		$filepath = $this->_ffmpegManagerClass->_appTmpFolderPath.$this->_fileName;
		file_put_contents($filepath, $data);
	}

	public function printResultInJson(){
		$arr = array('statusCode' => $this->_outStatusCode, 'msg' => utf8_encode($this->_outMsg)); //json_encode() will convert to null any non-utf8 String
		echo json_encode($arr);
	}

	public function saveTmpMP3File($postData){
		// pull the raw binary data from the POST array
		$data = substr($postData, strpos($postData, ",") + 1);
		// decode it
		$decodedData = base64_decode($data);
		//$this->_fileName = urldecode($_POST['fname']);
		$filepath = $this->_ffmpegManagerClass->_appTmpFolderPath.$this->_fileName;
		file_put_contents($filepath, $decodedData);
	}

	public function uploadFile($filepath=null){
		$canDoIt = false;
		try{
			if ($filepath==null){
				$this->_outStatusCode=500;
				$this->_outMsg="Error, no se ha subído el archivo al FTP ya que el filepath espicifacado es nulo";
			}
			
			//date_default_timezone_set('America/Argentina/Buenos_Aires');
			//$hoy = date("Y-m-d__H_i_s");
			//$alternativeFilename="Msg_".$hoy.".wav";
			
			$pathParts = explode("/", $filepath, 6);
			$this->_fileName = $pathParts[sizeof($pathParts)-1];
			$local_file = $pathParts[sizeof($pathParts)-2]."/".$this->_fileName; //consider tmp folder
			$remote_file = $this->_fileName;
	
			// establecer una conexion basica
			$conn_id = ftp_connect($this->_ftp_server);
			
			// iniciar sesion con nombre de usuario y contrasena
			$login_result = ftp_login($conn_id, $this->_ftp_user_name, $this->_ftp_user_pass);
			
			ftp_pasv($conn_id, true);
			 
			// cargar un archivo
			if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
				$this->_outMsg="Se ha cargado $local_file con exito.\n Se ha borrado el archivo del servidor web";
				$this->_outStatusCode=200;
				$canDoIt=true;
			} else {
				$this->_outStatusCode=500;
				$this->_outMsg="Hubo un problema durante la transferencia de $local_file";
			}
	
			// cerrar la conexion ftp
			ftp_close($conn_id);
		}catch (Exception $e) {
			$this->_outStatusCode=500;
		    $this->_outMsg='Error! Excepcion capturada: '.$e->getMessage()."\n";
			printResultInJson();
		}
		return $canDoIt;
	}

	public function deleteLocalFiles($filePath=null){ //change to array of $filePaths...
		$canDoIt=false;
		if ($filePath == null){
			$this->_outStatusCode=500;
			$this->_outMsg="Error! NO se ha borrado el archivo $filePath del servidor web. El filePath recibido es nulo";
			return $canDoIt;
		}
		$pathParts = explode("/", $filepath, 6);
		$fileName = $pathParts[sizeof($pathParts)-1];
		if (unlink($pathParts[sizeof($pathParts)-2]."/".$fileName)){ //consider tmp folder
			$this->_outStatusCode=200;
			$this->_outMsg="Se ha borrado el archivo $local_file con exito.";
			$canDoIt=true;
		}else{
			$this->_outStatusCode=500;
			$this->_outMsg="Error! NO se ha borrado el archivo $filePath del servidor web";
		}
		return $canDoIt;
	}
	
	public function manageGetAndUpload($mediaType=null,$postData=null,$filepath=null){
		try {
			if ( $mediaType != null ){
				if ($mediaType==3){
					saveTmpImageFile($postData);
					uploadFile($filepath);
				}else{
					if ($mediaType==1){
						uploadFile($filepath);
					}else{
						if ($mediaType==4){
							saveTmpMP3File($postData);
							uploadFile($filepath);
						}else{
							uploadFile($filepath);
						}
					}
				}
			}else{
				$outMsg="mediaType not specified!";
			}
			printResultInJson();
		} catch (Exception $e) {
			$this->_outStatusCode=500;
		    $this->_outMsg='Excepcion capturada: '.$e->getMessage()."\n";
			printResultInJson();
		}
	}

}

?>