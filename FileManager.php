<?php
//comment 3 - recent import cmd
// comment 5
final class FileManager{
    private $_ffmpegManagerClass = null;
    public static $_outMsg="";
    public static $_outStatusCode=500;
    public static $_fileName=null;
    private $_ftp_server="201.219.68.21";
    private $_ftp_user_name="voicemsg";
    private $_ftp_user_pass="voicemsg";	


    public static function getInstance(){
        static $inst = null;
        if ($inst === null) {
            $inst = new FileManager();
        }
        return $inst;
    }

    private function __construct(){
    }

	public function saveTmpImageFile($data){
		try{
			//$data = $_POST["imageData"];
			list($type, $data) = explode(';', $data);
			list(, $data)      = explode(',', $data);
			$data = base64_decode($data);
			$filepath = QueueManager::$_appTmpFolderPath.FileManager::$_fileName;
			if (file_put_contents($filepath, $data) == false ){
				FileManager::$_outStatusCode=500;
				FileManager::$_outMsg .='Error al intentar escribir en disco el archivo recibido usando el filePath: '.$filepath;
			}
		}catch (Exception $e) {
			FileManager::$_outStatusCode=500;
		    FileManager::$_outMsg .='Error! Excepcion capturada: '.$e->getMessage()."\n";
		}
	}

	public function printResultInJson(){
		$arr = array('statusCode' => FileManager::$_outStatusCode, 'msg' => utf8_encode(FileManager::$_outMsg)); //json_encode() will convert to null any non-utf8 String
		echo json_encode($arr);
	}

	public function saveTmpMP3File($postData){
		// pull the raw binary data from the POST array
		$data = substr($postData, strpos($postData, ",") + 1);
		// decode it
		$decodedData = base64_decode($data);
		//FileManager::$_fileName = urldecode($_POST['fname']);
		$filepath = QueueManager::$_appTmpFolderPath.FileManager::$_fileName;
		file_put_contents($filepath, $decodedData);
	}

	public function uploadFile($filepath=null){
		$canDoIt = false;
		try{
			if ($filepath==null){
				FileManager::$_outStatusCode=500;
				FileManager::$_outMsg .="Error, no se ha sub�do el archivo al FTP ya que el filepath espicifacado es nulo";
			}
			
			//date_default_timezone_set('America/Argentina/Buenos_Aires');
			//$hoy = date("Y-m-d__H_i_s");
			//$alternativeFilename="Msg_".$hoy.".wav";
			
			if ( strpos($filepath, "/") ){
				$pathParts = explode("/", $filepath, 6);
				FileManager::$_fileName = $pathParts[sizeof($pathParts)-1];
				$local_file = $pathParts[sizeof($pathParts)-2]."/".FileManager::$_fileName; //consider tmp folder
			}else{
				FileManager::$_fileName = $filepath;
				$local_file = QueueManager::$_appTmpFolderPath.FileManager::$_fileName;
			}
			$remote_file = FileManager::$_fileName;
	
			// establecer una conexion basica
			$conn_id = ftp_connect($this->_ftp_server);
			
			// iniciar sesion con nombre de usuario y contrasena
			$login_result = ftp_login($conn_id, $this->_ftp_user_name, $this->_ftp_user_pass);
			
			ftp_pasv($conn_id, true);
			 
			// cargar un archivo
			if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
				FileManager::$_outMsg .="Se ha cargado $local_file con exito.\n Se ha borrado el archivo del servidor web";
				FileManager::$_outStatusCode=200;
				$canDoIt=true;
			} else {
				FileManager::$_outStatusCode=500;
				FileManager::$_outMsg .="Hubo un problema durante la transferencia de localFile: $local_file con remoteFile: $remote_file";
			}
	
			// cerrar la conexion ftp
			ftp_close($conn_id);
		}catch (Exception $e) {
			FileManager::$_outStatusCode=500;
		    FileManager::$_outMsg .='Error! Excepcion capturada: '.$e->getMessage()."\n";
		}
		return $canDoIt;
	}

	public function deleteLocalFiles($filePath=null){ //change to array of $filePaths...
		$canDoIt=false;
		if ($filePath == null){
			FileManager::$_outStatusCode=500;
			FileManager::$_outMsg .="Error! NO se ha borrado el archivo $filePath del servidor web. El filePath recibido es nulo";
			return $canDoIt;
		}
		$pathParts = explode("/", $filepath, 6);
		$fileName = $pathParts[sizeof($pathParts)-1];
		if (unlink($pathParts[sizeof($pathParts)-2]."/".$fileName)){ //consider tmp folder
			FileManager::$_outStatusCode=200;
			FileManager::$_outMsg .="Se ha borrado el archivo $local_file con exito.";
			$canDoIt=true;
		}else{
			FileManager::$_outStatusCode=500;
			FileManager::$_outMsg .="Error! NO se ha borrado el archivo $filePath del servidor web";
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
			FileManager::$_outStatusCode=500;
		    FileManager::$_outMsg .='Excepcion capturada: '.$e->getMessage()."\n";
			printResultInJson();
		}
	}

}

?>