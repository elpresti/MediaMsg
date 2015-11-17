<?php

error_reporting(E_ALL ^ E_WARNING);

if (isset($_GET['filename']) &&  (strlen($_GET['filename'])>0) ) {
	$filename = $_GET['filename'];
}
require('util/common.php');

$outMsg="NO MESSAGE";
$outStatusCode=500;

function printResultInJson(){
	global $outMsg, $outStatusCode;
	$arr = array('statusCode' => $outStatusCode, 'msg' => utf8_encode($outMsg)); //json_encode() will convert to null any non-utf8 String
	echo json_encode($arr);
}

function saveTmpImageFile(){
	global $filename;
	$data = $_POST["imageData"];
	list($type, $data) = explode(';', $data);
	list(, $data)      = explode(',', $data);
	$data = base64_decode($data);
	$filepath = "messages/".$filename;
	file_put_contents($filepath, $data);
}

function saveTmpMP3File(){
	global $filename;
	/*
	$data = $_POST["mp3Data"];
	list($type, $data) = explode(';', $data);
	list(, $data)      = explode(',', $data);
	$data = base64_decode($data);
	*/
	// pull the raw binary data from the POST array
	$data = substr($_POST['data'], strpos($_POST['data'], ",") + 1);
	// decode it
	$decodedData = base64_decode($data);
	//$filename = urldecode($_POST['fname']);
	$filepath = "messages/".$filename;
	file_put_contents($filepath, $decodedData);
}

function uploadFile(){
	global $outMsg, $outStatusCode;
	$ftp_server="201.219.68.21";
	$ftp_user_name="voicemsg";
	$ftp_user_pass="voicemsg";	
	
	date_default_timezone_set('America/Argentina/Buenos_Aires');
	$hoy = date("Y-m-d__H_i_s");
	$alternativeFilename="Msg_".$hoy.".wav";

	//Levanto el filename desde el parametro, o en caso de que no haya sido especificado, asigno uno por defecto
	parse_str($_SERVER['QUERY_STRING'], $params);
	$filename = isset($params['filename']) ? $params['filename'] : $alternativeFilename;

	$local_file = "messages/".$filename;
	//$local_file = $_FILES["messages/".$filename];
	$remote_file = $filename;

	// establecer una conexión básica
	$conn_id = ftp_connect($ftp_server);
	
	// iniciar sesión con nombre de usuario y contraseña
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
	
	ftp_pasv($conn_id, true);
	 
	// cargar un archivo
	if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
		if (unlink($local_file)){
			$outMsg="Se ha cargado $local_file con éxito.\n Se ha borrado el archivo del servidor web";
			$outStatusCode=200;
		}else{
			$outStatusCode=500;
			$outMsg="Se ha cargado $local_file con éxito.\n Error! NO se ha borrado el archivo del servidor web";
		}
	} else {
		$outStatusCode=500;
		$outMsg="Hubo un problema durante la transferencia de $local_file";
	}

	// cerrar la conexión ftp
	ftp_close($conn_id);
}

try {
	if ( isset($_GET['mediaType']) && strlen($_GET['mediaType'])>0 ){
		if ($_GET['mediaType']==3){
			saveTmpImageFile();
			uploadFile();
		}else{
			if ($_GET['mediaType']==1){
				uploadFile();
			}else{
				if ($_GET['mediaType']==4){
					saveTmpMP3File();
					uploadFile();
				}else{
					uploadFile();
				}
			}
		}
	}else{
		$outMsg="mediaType not specified!";
	}
	printResultInJson();
} catch (Exception $e) {
	$outStatusCode=500;
    $outMsg='Excepción capturada: '.$e->getMessage()."\n";
	printResultInJson();
}

?>