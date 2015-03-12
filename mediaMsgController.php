<?php

require('util/common.php');
require_once 'FfmpegManager.php';
require_once 'FileManager.php';
require "util/JsonDB.class.php";

$outMsg="NO MESSAGE";
$outStatusCode=500;
$dbObj = new JsonDB("./");
$fileManagerObj = FileManager::getInstance($dbObj);
FfmpegManager::getInstance($dbObj,$fileManagerObj);

function printResultInJson(){
	global $outMsg, $outStatusCode;
	$arr = array('statusCode' => $outStatusCode, 'msg' => utf8_encode($outMsg)); //json_encode() will convert to null any non-utf8 String
	echo json_encode($arr);
}

parse_str($_SERVER['QUERY_STRING'], $params);

try{
	if (isset($params['action'])  &&  strlen($params['action'])>0){
		$action = ( (isset($params['action'])) ? $params['action'] : null );
		$audioInput = ( (isset($params['audioInput'])) ? $params['audioInput'] : null );
		$videoInput = ( (isset($params['videoInput'])) ? $params['videoInput'] : null );
		$photosInput = ( (isset($params['photosInput'])) ? $params['photosInput'] : "test_img.png" );
		$outputFilename = ( (isset($params['outputFilename'])) ? $params['outputFilename'] : null );
		$outputMediaType = ( (isset($params['outputMediaType'])) ? $params['outputMediaType'] : null );
		$logFile = isset($params['logFile']) ? $params['logFile'] : null;
		$itemId = ( (isset($params['itemId'])) ? $params['itemId'] : null );
		
		$dangerousChars = array("!","#","$","%","&","'","(",")","*","+",",","-","/",":",";","<","=",">","?","@","[","\\","]","^","`","{","|","}","~");
		if (contains($action.$outputFilename.$audioInput.$videoInput.$logFile, $dangerousChars)){
			$outMsg="Error! Some parameter value is invalid\n\nString parsed: ".$outputFilename.$audioInput.$videoInput.$logFile;
			$outStatusCode=500;
		}else{
			switch (strtolower($params['action'])) {
			    case "additemtoqueue":
			        //$audioInput = ( (isset($params['audioInput'])) ? $params['audioInput'] : null );
					if ($audioInput != null){
						//$videoInput = ( (isset($params['videoInput'])) ? $params['videoInput'] : null );
						//$photosInput = ( (isset($params['photosInput'])) ? $params['photosInput'] : "test_img.png" );				
						//$outputMediaType = ( (isset($params['outputMediaType'])) ? $params['outputMediaType'] : null );
						//$logFile = isset($params['logFile']) ? $params['logFile'] : null;
						if ($outputMediaType == null){
							if ($videoInput != "test.webm"){
								$outputMediaType = FfmpegManager::getInstance()->_MEDIA_TYPE_2;
							}else{
								$outputMediaType = FfmpegManager::getInstance()->_MEDIA_TYPE_3;
							}
						}
						$outMsg="additemtoqueue processed. Result: \n".FfmpegManager::getInstance()->addItemToQueue($audioInput, $videoInput, $photosInput,$outputMediaType,null,null,null,$logFile,null);
						$outStatusCode=200;
					}else{
						$outMsg="audioInput parameter is mandatory";
						$outStatusCode=500;
					}
			        break;
			    case "savetmpimg":
					$outMsg="savetmpimg is executing";
					$outStatusCode=200;
			        break;
			    case "savetmpvideo":
					$outMsg="savetmpvideo is executing";
					$outStatusCode=200;
			        break;
			    case "savetmpaudio":
			    	$outMsg="savetmpaudio is executing";
					$outStatusCode=200;
			        break;
			    case "processqueue":
			        $outMsg="processQueue executed. Result: \n".FfmpegManager::getInstance()->processQueue();
					$outStatusCode=200;
			        break;
			    case "updateitemonqueue":
			        $itemId = ( (isset($params['itemId'])) ? $params['itemId'] : null );
					if ($itemId != null){
						$fileToUpload = getItemOutputFile();
			        	$outMsg="updateItemOnQueue executed. Result: \n".FileManager::getInstance($dbObj)->uploadFile($fileToUpload);
						$outStatusCode=200;
			        }else{
			        	$outMsg="Error! Invalid ID";
						$outStatusCode=500;
			        }
			        break;
			    case "getprocessingstatus":
			    	$itemId = ( (isset($params['itemId'])) ? $params['itemId'] : null );
			    	if ($itemId == null){
			    		$outMsg="itemId parameter is mandatory";
			    		$outStatusCode=500;
			    		break;
			    	}
			        $queueItem = FfmpegManager::getInstance()->getQueueItemById($itemId);
					if ($queueItem == null){
						$outMsg="The requested QueueItem doesn't exists in our Processing Table";
						$outStatusCode=500;
					}else{
						$queueItem = FfmpegManager::getInstance()->checkProcessingStatus($queueItem);
						FfmpegManager::getInstance()->updateItemOnQueue($queueItem);
						$outMsg="Item status and info updated: \n".print_r( $queueItem );
						$outStatusCode=200;
					}
			        break;
			    case "isprocessrunning":
			    	$processId=( (isset($params['processId'])) ? $params['processId'] : null );
					if ($processId != null  &&  is_numeric($processId)){
						if (FfmpegManager::getInstance()->is_process_running($processId)) {
							$outMsg="YES! It's running";
							$outStatusCode=200;
						}else{
							$outMsg="NO! It's not running";
							$outStatusCode=200;
						}
					}else{
						$outMsg="processId parameter must be present and have a valid value";
						$outStatusCode=500;
					}
			        break;
			    default:
					$outMsg="Action parameter must contain a valid value!";
					$outStatusCode=500;
			}
		}
	}else{
		$outMsg="Action parameter must be present!";
		$outStatusCode=500;
	}
} catch (Exception $e) {
	$outMsg="Error! Error details: ".(string)$e;
	$outStatusCode=500;
}

printResultInJson();



?>