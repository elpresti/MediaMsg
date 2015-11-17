<?php
//phpinfo();print_r(stream_get_wrappers());die();
header('Access-Control-Allow-Origin: *');  //remove on production


require('util/common.php');
require_once 'FfmpegManager.php';
require_once 'FileManager.php';
require_once 'QueueManager.php';
require "util/JsonDB.class.php";
require "util/Url2Png.php";

$outMsg="NO MESSAGE";
$outStatusCode=500;
//$dbObj = new JsonDB("./");
//QueueManager::getInstance($dbObj,$fileManagerObj);
FileManager::getInstance();
QueueManager::getInstance();
FfmpegManager::getInstance();

function printResultInJson(){
	global $outMsg, $outStatusCode;
	$arr = array('statusCode' => $outStatusCode, 'msg' => utf8_encode($outMsg)); //json_encode() will convert to null any non-utf8 String
	$out = json_encode($arr);
	$out = str_replace("\\\\\\", "", $out);
	echo $out;
	$outMsg="NO MESSAGE"; $outStatusCode=500; //reset global vars
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
		$processId = ( (isset($params['processId'])) ? $params['processId'] : null );
		$itemId = ( (isset($params['itemId'])) ? $params['itemId'] : null );
		$cmdToExecute = ( (isset($params['cmdToExecute'])) ? $params['cmdToExecute'] : null );
		
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
							if ($videoInput == "test.webm"){
								$outputMediaType = QueueManager::$_MEDIA_TYPE_2;
							}else{
								$outputMediaType = QueueManager::$_MEDIA_TYPE_3;
							}
						}
						$outMsg="additemtoqueue processed. Result: \n".QueueManager::getInstance()->addItemToQueue($audioInput, $videoInput, $photosInput,$outputMediaType,null,null,null,$logFile,null,$cmdToExecute);
						$outStatusCode=200;
					}else{
						$outMsg="audioInput parameter is mandatory";
						$outStatusCode=500;
					}
			        break;
		        case "updateitemonqueue":
		        	if ($itemId == null){
		        		$outMsg="itemId parameter is mandatory";
		        		$outStatusCode=500;
		        		break;
		        	}
		        	$queueItem = QueueManager::getInstance()->getQueueItemById($itemId);
		        	if ($queueItem == null){
		        		$outMsg="The requested QueueItem doesn't exists in our Processing Table";
		        		$outStatusCode=500;
		        	}else{
		        		if ($audioInput != null){
		        			$queueItem[QueueManager::$_COLN_audioInput]=$audioInput;
		        		}
		        		if ($cmdToExecute != null){
		        			$queueItem[QueueManager::$_COLN_cmd_to_execute]=$cmdToExecute;
		        		}
		        		if ($logFile != null){
		        			$queueItem[QueueManager::$_COLN_logFile]=$logFile;
		        		}
		        		if ($outputFilename != null){
		        			$queueItem[QueueManager::$_COLN_outputFilename]=$outputFilename;
		        		}
		        		if ($outputMediaType != null){
		        			$queueItem[QueueManager::$_COLN_outputMediaType]=$outputMediaType;
		        		}
		        		if ($photosInput != null){
		        			$queueItem[QueueManager::$_COLN_photosInput]=$photosInput;
		        		}
		        		if ($videoInput != null){
		        			$queueItem[QueueManager::$_COLN_videoInput]=$videoInput;
		        		}
		        		if ($processId != null){
		        			$queueItem[QueueManager::$_COLN_process_id]=$processId;
		        		}
		        		if ($cmdToExecute != null){
		        			$queueItem[QueueManager::$_COLN_cmd_to_execute]=$cmdToExecute;
		        		}
		        		$outMsg="updateItemOnQueue executed. Result: \n".QueueManager::getInstance()->updateItemOnQueue($queueItem);
		        		$outStatusCode=200;
		        	}
		        	break;
	        	case "deleteitemonqueue":
	        		if ($itemId == null){
	        			$outMsg="itemId parameter is mandatory";
	        			$outStatusCode=500;
	        			break;
	        		}
	        		$queueItem = QueueManager::getInstance()->getQueueItemById($itemId);
	        		if ($queueItem == null){
	        			$outMsg="The requested QueueItem doesn't exists in our Processing Table";
	        			$outStatusCode=500;
	        		}else{
	        			$outMsg="deleteItemOnQueue executed. Result: \n".QueueManager::getInstance()->deleteItemOnQueue($itemId);
	        			$outStatusCode=200;
	        		}
	        		break;
        		case "getitemonqueue":
        			if ($itemId == null){
        				$outMsg="itemId parameter is mandatory";
        				$outStatusCode=500;
        				break;
        			}
        			$queueItem = QueueManager::getInstance()->getQueueItemById($itemId);
        			if ($queueItem == null){
        				$outMsg="The requested QueueItem doesn't exists in our Processing Table";
        				$outStatusCode=500;
        			}else{
        				$outMsg="getItemOnQueue executed. Result: \n".print_r($queueItem,TRUE);
        				$outStatusCode=200;
        			}
        			break;
			    case "savetmpimg":
			    	//$imageData = ( (isset($_POST["imageData"])) ? $_POST["imageData"] : null );
			    	if (isset($_POST["imageData"])  &&  $outputFilename != null ){
			    		FileManager::$_fileName = $outputFilename;
			    		FileManager::getInstance()->saveTmpImageFile($_POST["imageData"]);
			    		FileManager::getInstance()->uploadFile($outputFilename);
			    		//$outMsg="savetmpimg executed OK! tmpImage saved and uploaded!";
			    		//$outStatusCode=200;
			    		$outMsg = FileManager::$_outMsg;
			    		$outStatusCode=FileManager::$_outStatusCode;
			    	}else{
			    		$outMsg="imageData or outputFilename parameters are null, but are mandatory to execute";
			    		$outStatusCode=500; 
			    	}			    	
			        break;
			    case "savetmpvideo":
					$outMsg="savetmpvideo is executing";
					$outStatusCode=200;
			        break;
			    case "savetmpaudio":
					if (isset($_POST["data"])  &&  $outputFilename != null ){
			    		FileManager::$_fileName = $outputFilename;
			    		FileManager::getInstance()->saveTmpMP3File($_POST["data"]);
			    		FileManager::getInstance()->uploadFile($outputFilename);
			    		$outMsg = FileManager::$_outMsg;
			    		$outStatusCode=FileManager::$_outStatusCode;
			    	}else{
			    		$outMsg="posted data or outputFilename parameters are null, but are mandatory to execute";
			    		$outStatusCode=500; 
			    	}
			        break;
			    case "processqueue":
			        $outMsg="processQueue executed. Result: \n".FfmpegManager::getInstance()->processQueue();
					$outStatusCode=200;
			        break;
			    case "uploaditemonqueue":
			        $itemId = ( (isset($params['itemId'])) ? $params['itemId'] : null );
					if ($itemId != null){
						$fileToUpload = getItemOutputFile();
			        	$outMsg="updateItemOnQueue executed. Result: \n".FileManager::getInstance()->uploadFile($fileToUpload);
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
			        $queueItem = QueueManager::getInstance()->getQueueItemById($itemId);
					if ($queueItem == null){
						$outMsg="The requested QueueItem doesn't exists in our Processing Table";
						$outStatusCode=500;
					}else{
						$queueItem = QueueManager::getInstance()->checkProcessingStatus($queueItem);
						QueueManager::getInstance()->updateItemOnQueue($queueItem);
						$outMsg="Item status and info updated: \n".print_r( $queueItem );
						$outStatusCode=200;
					}
			        break;
			    case "isprocessrunning":
			    	$processId=( (isset($params['processId'])) ? $params['processId'] : null );
					if ($processId != null  &&  is_numeric($processId)){
						if (QueueManager::getInstance()->is_process_running($processId)) {
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
			    case "getanduploadurlimage":
			        $urlParam = ( (isset($params['url'])) ? $params['url'] : null );
			        $crops = ( (isset($params['crops'])) ? $params['crops'] : null );
			        $outfilenameParam = ( (isset($params['outfilename'])) ? $params['outfilename'] : null );
			        $scaleOut = ( (isset($params['scaleout'])) ? $params['scaleout'] : null );
			        //generate images and return its paths:
			        $outMsg="Analizing URL action and parameters... ";
			        //echo print_r($params,TRUE);break;
			        if ($urlParam == null){
			        	//$urlParam = 'url=http%3A%2F%2Fwww.resultadosba.gob.ar%2Fweb%2Fdat02%2FDCO02090M.htm%3Fd%3D2371';
			        	$defaultUrlParam = "http://powerhd.aws.af.cm/webroot/widgets/voteStats/";
			        	$urlParam = urlencode($defaultUrlParam);
			        	$outMsg.=" |  url parameter is null, using default target URL: $defaultUrlParam | ";
			        }
			        //$baseUrl = 'http://vps1-web.sebapresti.com/mediamsg/util/Url2Png.php';
			        if ($outfilenameParam == null){
			        	date_default_timezone_set('America/Argentina/Buenos_Aires');
						//$date = date('m/d/Y h:i:s a', time());
						$date = date('Ymd_Hi', time());
			        	$outfilenameParam = 'paso_'.$date;
			        	$outMsg.=" |  outfilename parameter is null, using default $outfilenameParam | ";
			        }
			        if ($crops == null){
			        	//$crops = urlencode("990x434+538+980");
			        	$defaultCrops = "2228x309+131+131";
			        	$crops = urlencode($defaultCrops);
			        	$outMsg.=" |  crops parameter is null, using default crops $defaultCrops | ";
			        }
			        if ($scaleOut == null){
			        	$defaultScaleOut = "1280x178";
			        	$scaleOut= urlencode($defaultScaleOut);
			        	$outMsg.=" |  scaleOut parameter is null, using default scaleOut: $defaultScaleOut | ";
			        }
			        $jsonResponse1 = Url2Png::getInstance()->execute($urlParam,$outfilenameParam,$crops,$scaleOut);

			        $obj = json_decode($jsonResponse1);
					if (property_exists($obj, "filesGenerated")){
						$filesGenerated = explode(',',$obj->{'filesGenerated'});
						//upload files
						if (sizeof($filesGenerated)>0){
							$outMsg.="getanduploadurlimage executed. Result: \n<br>---- url_to_png1 RESULT: ----<br>".$jsonResponse1."<br><br>---- uploadFile RESULT: ----<br>".FileManager::getInstance()->uploadFile($filesGenerated[sizeof($filesGenerated)-1]);
							$outStatusCode=200;
						}else{
							$outMsg.="getanduploadurlimage executed. Result: \n<br>---- url_to_png2 RESULT: ----<br>".$jsonResponse1."<br><br>Not executing file_upload because filesGenerated=0";
							$outStatusCode=500;
						}
					}else{
						$outMsg.="getanduploadurlimage executed. Result: \n<br>---- url_to_png3 RESULT: ----<br>".$jsonResponse1."<br><br>Not executing file_upload because filesGenerated=null";
						$outStatusCode=500;
					}
					// verificar si existe $filesGenerated = $obj->{'filesGenerated'}, en caso negativo mostrar error y el $jsonResponse

			        //upload de estos archivos:
			        // $filesGenerated = explode(',',$filesGenerated);
			        // quitar parte del path q sobre (o no)
			        // $baseUrl = 'http://vps1-web.sebapresti.com/mediamsg/ftpUploader.php'
			        // incluir en el query estos parametros 'avoidMediaType=true&sourcefilepath=XXX'
			        /*
					if ($itemId != null){
						$fileToUpload = getItemOutputFile();
			        	$outMsg="updateItemOnQueue executed. Result: \n".FileManager::getInstance()->uploadFile($fileToUpload);
						$outStatusCode=200;
			        }else{
			        	$outMsg="Error! Invalid ID";
						$outStatusCode=500;
			        } */
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