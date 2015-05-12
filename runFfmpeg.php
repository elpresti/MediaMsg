<?php

error_reporting(E_ALL ^ E_WARNING);
require('util/common.php');
require_once 'FfmpegManager.php';
//echo "<br>print algo antes de llamar a la clase";
require "util/JsonDB.class.php";
//echo "<br>print algo despues de llamar a la clase";

parse_str($_SERVER['QUERY_STRING'], $params);

//$alternativeFilename = getFileName();
//$outputFilename = isset($params['outputFilename']) ? $params['outputFilename'] : $alternativeFilename.".mp4";
$outputFilename = isset($params['outputFilename']) ? $params['outputFilename'] : null;
$audioInput = isset($params['audioInput']) ? $params['audioInput'] : null;
$videoInput = isset($params['videoInput']) ? $params['videoInput'] : null;
//$logFile = isset($params['logFile']) ? $params['logFile'] : $alternativeFilename.".log";
$logFile = isset($params['logFile']) ? $params['logFile'] : null;

$audioInput = "audioMsg.mp3";
$videoInput = "test.webm";
if ($audioInput == null  ||  $videoInput == null){
	exit("Error! audioInput and videoInput are mandatory parameters");
}
$dangerousChars = array("!","#","$","%","&","'","(",")","*","+",",","-","/",":",";","<","=",">","?","@","[","\\","]","^","`","{","|","}","~");
if (contains($outputFilename.$audioInput.$videoInput.$logFile, $dangerousChars)){
	exit("Error! Some parameter value is invalid\n\nString parsed: ".$outputFilename.$audioInput.$videoInput.$logFile);
}

/* ---------- TEST ---------------- */
//$jsonFile = "/var/www/mediamsg/queue.json";
//QueueManager::getInstance()->getProcessingQueue($jsonFile);
//addItemToQueue($jsonFile);
echo "<br>Running encoder with $audioInput and $videoInput<br>";
//$db = new JsonDB("./my_json_database/"); //parameter => directory to your json files
//$db = new JsonDB("./");
if (isset($params['addItemToQueue'])  &&  $params['addItemToQueue'] == "true"){
	$audioInput = ( (isset($params['audioInput'])) ? $params['audioInput'] : null );
	//$audioInput = "audioMsg.mp3";
	if ($audioInput == null){
		echo "audioInput parameter is mandatory";
		exit();
	}
	$videoInput = ( (isset($params['videoInput'])) ? $params['videoInput'] : null );
	$photosInput = ( (isset($params['photosInput'])) ? $params['photosInput'] : "test_img.png" );
	//"test.webm"
	
	$outputMediaType = ( (isset($params['outputMediaType'])) ? $params['outputMediaType'] : null );
	if ($outputMediaType == null){
		if ($videoInput != "test.webm"){
			$outputMediaType = FfmpegManager::getInstance(new JsonDB("./"))->_MEDIA_TYPE_2;
		}else{
			$outputMediaType = FfmpegManager::getInstance(new JsonDB("./"))->_MEDIA_TYPE_3;
		}
	}
	//echo QueueManager::getInstance(new JsonDB("./"))->addItemToQueue($audioInput, $videoInput,null,null,null,null,null);
	echo QueueManager::getInstance(new JsonDB("./"))->addItemToQueue($audioInput, $videoInput, $photosInput,$outputMediaType,null,null,null,null,null);
	exit();
}

if (isset($params['processQueue'])  &&  $params['processQueue'] == "true"){
	echo FfmpegManager::getInstance()->processQueue();
	exit();
}

if (isset($params['getProcessingStatus'])  &&  $params['getProcessingStatus'] > 0){
	echo "<br>getQueueItemById: <br>";
	$queueItem = QueueManager::getInstance()->getQueueItemById($params['getProcessingStatus']);
	if ($queueItem == null){
		echo "The requested QueueItem doesn't exists in our Processing Table";
	}else{
		echo print_r($queueItem);
		echo "<br><br>checkProcessingStatus(): <br>";
		$queueItem = QueueManager::getInstance()->checkProcessingStatus($queueItem);
		QueueManager::getInstance()->updateItemOnQueue($queueItem);
		echo print_r( $queueItem );
	}
	exit();
}

if (isset($params['isProcessRunning'])  &&  $params['isProcessRunning'] > 0){
	echo "<br>isProcessRunning(): <br>";
	if (QueueManager::getInstance()->is_process_running($params['isProcessRunning'])) {
		echo "YES! It's running";
	}else{
		echo "NO! It's not running";
	}
	exit();
}

exit();

if (sizeof($db->selectAll("processingQueue")) == 0){
	$db->createTable("processingQueue");
}

$db->insert("processingQueue",array("ID" => 1, "Name" => "Fani Zwidawurzn", "Age" => 66));
echo "<br>sizeOf: ".sizeof($db->selectAll("processingQueue"))."<br>";
print_r($db->selectAll ( "processingQueue" ));
exit();
//echo FfmpegManager::getInstance()->runEncoder($audioInput,$videoInput, getFileName(), $outputFilename, $logFile);
//echo FfmpegManager::getInstance()->processQueue();
//echo FfmpegManager::getInstance()->sayHello("textooo");
exit();
/* -------------------------- */

$tmpFolderPath = "/var/www/mediamsg/";

$commandToExecute = "/root/ffmpeg/ffmpeg -y -i ".$tmpFolderPath.$videoInput." -i ".$tmpFolderPath.$audioInput." -c:v libx264 -vf fps=25  -c:a aac -strict -2  ".$tmpFolderPath.$outputFilename;
$commandToExecute .= " </dev/null >/dev/null 2>".$tmpFolderPath.$logFile." &";

echo "Starting ffmpeg...\n\n";
echo shell_exec($commandToExecute);
echo "Done.\n";
echo "\n\naltFileName: ".$alternativeFilename;

?>