<?php

final class QueueManager{
	
	public static $_appFolderPath = "/var/www/mediamsg/"; // D:\\workspaces\\pwr\\MediaMsg\\ 
	public static $_appTmpFolderPath = "/var/www/mediamsg/tmp/"; // D:\\workspaces\\pwr\\MediaMsg\\tmp\\ 
	public static $_ITEM_STATUS_1 = "PENDING_TO_PROCESS";
	public static $_ITEM_STATUS_2 = "PROCESSING";
	public static $_ITEM_STATUS_3 = "PENDING_TO_UPLOAD";
	public static $_ITEM_STATUS_4 = "UPLOADING_TO_FTP";
	public static $_ITEM_STATUS_5 = "UPLOADED";
	public static $_ITEM_STATUS_6 = "FAILED_WHILE_PROCESSING";
	public static $_ITEM_STATUS_7 = "FAILED_WHILE_UPLOADING";
	public static $_ITEM_STATUS_8 = "FAILED_WHILE_VALIDATING";
	public static $_MEDIA_TYPE_1 = "AUDIO";
	public static $_MEDIA_TYPE_2 = "AUDIO_AND_VIDEO";
	public static $_MEDIA_TYPE_3 = "AUDIO_AND_IMAGES";
	public static $_MEDIA_TYPE_4 = "IMAGES";
	public static $_COLN_logFile = "logFile";
	public static $_COLN_outputFilename = "outputFilename";
	public static $_COLN_videoInput = "videoInput";
	public static $_COLN_audioInput = "audioInput";
	public static $_COLN_photosInput = "photosInput";
	public static $_COLN_outputMediaType = "outputMediaType";
	public static $_COLN_last_status_change = "last_status_change";
	public static $_COLN_status = "status";
	public static $_COLN_arrived_on = "arrived_on";
	public static $_COLN_ID = "id";
	public static $_COLN_process_id = "process_id";
	public static $_COLN_extra_info = "extra_info";
	public static $_COLN_cmd_executed = "cmd_executed";
	public static $_COLN_cmd_to_execute = "cmdToExecute";
	private $_dbInstance = null;
	private $_dbPath = "./";
	private $_dbTableName="processingQueue";
	public static $_isProcessingQueue=false;
	
	public static function getInstance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new QueueManager();
		}
		return $inst;
	}
	
	private function __construct(){
	}
	
	public function checkProcessingStatus($queueItem){
		$nowDateTime = date("Y_m_d__H_i_s");
		if ($queueItem[QueueManager::$_COLN_status] == QueueManager::$_ITEM_STATUS_2  &&  ((int) $queueItem[QueueManager::$_COLN_process_id]) > 0){
			if (!($this->is_process_running($queueItem[QueueManager::$_COLN_process_id]))) {
				$queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
				if (file_exists($queueItem[QueueManager::$_COLN_outputFilename])) {
					if (filesize($queueItem[QueueManager::$_COLN_outputFilename]) > 100000 ) { //filezie()>100kb
						$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_3;
						$queueItem[QueueManager::$_COLN_extra_info] .= "\n Files well processed!";
					}else{
						$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_6;
						$queueItem[QueueManager::$_COLN_extra_info] .= "Process end with an output file of ".filesize($queueItem[QueueManager::$_COLN_outputFilename])." bytes.\n";
						$queueItem[QueueManager::$_COLN_extra_info] .= "This is logfile content:\n---\n".file_get_contents($queueItem[QueueManager::$_COLN_logFile], true)."\n---\n";
					}
				}else{
					$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_6;
					$queueItem[QueueManager::$_COLN_extra_info] .= "Process end but the output file was not created.\n This is logfile content:\n---\n".file_get_contents($queueItem[QueueManager::$_COLN_logFile], true)."\n---\n";
				}
			}else{
				echo "Still working!";
			}
		}else{
			//$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_6;
			//$queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
			//$queueItem[QueueManager::$_COLN_extra_info] = "QueueItem status: ".$queueItem[QueueManager::$_COLN_status].".\n QueueItem Process ID: ".$queueItem[QueueManager::$_COLN_process_id];
		}
		return $queueItem;
	}
	
	public function addItemToQueue($audioInput, $videoInput=null, $photosInput = null, $outputMediaType = null, 
			$status=null, $alternativeFilename = null, $outputFilename = null, $logFile = null, $extra_info = null, $cmd_to_execute = null){
		if ($audioInput == null  ||  strlen($audioInput)<5){
			return "Error! audioInput parameter is mandatory. Item not added.";
		}
		$nowDateTime = date("Y_m_d__H_i_s");
		//set default values and add the correct file path
		$audioInput = QueueManager::$_appTmpFolderPath.$audioInput;
		if ($videoInput != null){
			$videoInput = QueueManager::$_appTmpFolderPath.$videoInput;
		}
		if ($photosInput != null){
			$photosInputArray = explode(',', $photosInput);
			if (sizeof($photosInputArray)>0){
				for ($i=0; $i<sizeof($photosInputArray); $i++){
					$photosInputArray[$i] = QueueManager::$_appTmpFolderPath.trim($photosInputArray[$i]);
				}
				$photosInput = implode(",", $photosInputArray);
			}
		}
		if ($alternativeFilename == null){
			$alternativeFilename = "Msg_".$nowDateTime;
		}
		if ($outputFilename == null){
			$outputFilename = $alternativeFilename.".mp4";
		}
		$outputFilename = QueueManager::$_appTmpFolderPath.$outputFilename;
		if ($logFile == null){
			$logFile = $alternativeFilename.".log";
		}
		if ($status == null){
			$status = QueueManager::$_ITEM_STATUS_1;
		}
		if ($extra_info == null){
			$extra_info = "JUST ARRIVED";
		}
		if ($outputMediaType == null){
			if ($videoInput == null  &&  $photosInput != null){
				$outputMediaType = QueueManager::$_MEDIA_TYPE_3;
			}else{
				if ($videoInput != null  &&  $photosInput == null){
					$outputMediaType = QueueManager::$_MEDIA_TYPE_2;
				}else{
					$outputMediaType = QueueManager::$_MEDIA_TYPE_1;
				}
			}
		}
	
		$rowData = array(
				QueueManager::$_COLN_ID => $this->getNextId(),
				QueueManager::$_COLN_arrived_on => $nowDateTime,
				QueueManager::$_COLN_status => $status,
				QueueManager::$_COLN_last_status_change => $nowDateTime,
				QueueManager::$_COLN_audioInput => $audioInput,
				QueueManager::$_COLN_videoInput => $videoInput,
				QueueManager::$_COLN_photosInput => $photosInput,
				QueueManager::$_COLN_outputMediaType => $outputMediaType,
				QueueManager::$_COLN_outputFilename => $outputFilename,
				QueueManager::$_COLN_process_id => 0,
				QueueManager::$_COLN_extra_info => $extra_info,
				QueueManager::$_COLN_cmd_executed => null,
				QueueManager::$_COLN_cmd_to_execute => $cmd_to_execute,
				QueueManager::$_COLN_logFile => QueueManager::$_appTmpFolderPath.$logFile
		);
		if ($this->getDb()->insert($this->_dbTableName, $rowData) > 0){
			return ($this->getNextId() - 1);
		}else{
			return "Error trying to add a new item to queue";
		}
	}
	
	public function updateItemOnQueue($queueItem, $id=null, $audioInput=null, $videoInput=null, $photosInput=null, $outputMediaType=null, 
			$status=null, $outputFilename = null, $logFile = null, $process_id = null, $extra_info = null, $cmd_executed = null, $cmd_to_execute = null){
		if ($id == null  &&  (!($queueItem[QueueManager::$_COLN_ID] > 0)) ){
			return false;
		}
		$nowDateTime = date("Y_m_d__H_i_s");
		if ($id == null){
			$id = $queueItem[QueueManager::$_COLN_ID];
		}
		$itemToUpdate = $this->getDb()->select($this->_dbTableName, QueueManager::$_COLN_ID, $id);
		if (sizeof($itemToUpdate) != 1){
			return false;
		}
		$itemToUpdate=$itemToUpdate[0];
		if ($status != null  ||  ($queueItem[QueueManager::$_COLN_status] != null) ){
			$itemToUpdate[QueueManager::$_COLN_status] = ( ($status != null) ? $status : $queueItem[QueueManager::$_COLN_status] );
			$itemToUpdate[QueueManager::$_COLN_last_status_change] = $nowDateTime;
		}
		if ($audioInput != null  ||  ($queueItem[QueueManager::$_COLN_audioInput] != null) ){
			$itemToUpdate[QueueManager::$_COLN_audioInput] = ( ($audioInput != null) ? $audioInput : $queueItem[QueueManager::$_COLN_audioInput] );
			if (strrpos($itemToUpdate[QueueManager::$_COLN_audioInput], QueueManager::$_appTmpFolderPath) === false){
				$itemToUpdate[QueueManager::$_COLN_audioInput] = QueueManager::$_appTmpFolderPath.$itemToUpdate[QueueManager::$_COLN_audioInput];
			}
		}
		if ($videoInput != null  ||  ($queueItem[QueueManager::$_COLN_videoInput] != null) ){
			$itemToUpdate[QueueManager::$_COLN_videoInput] = ( ($videoInput != null) ? $videoInput : $queueItem[QueueManager::$_COLN_videoInput] );
			if (strrpos($itemToUpdate[QueueManager::$_COLN_videoInput], QueueManager::$_appTmpFolderPath) === false){
				$itemToUpdate[QueueManager::$_COLN_videoInput] = QueueManager::$_appTmpFolderPath.$itemToUpdate[QueueManager::$_COLN_videoInput];
			}
		}
		if ($photosInput != null  ||  ($queueItem[QueueManager::$_COLN_photosInput] != null) ){
			$itemToUpdate[QueueManager::$_COLN_photosInput] = ( ($photosInput != null) ? $photosInput : $queueItem[QueueManager::$_COLN_photosInput] );
			$photosInputArray = explode(',', $itemToUpdate[QueueManager::$_COLN_photosInput]);
			if (sizeof($photosInputArray)>0){
				for ($i=0; $i<sizeof($photosInputArray); $i++){
					if (strrpos($photosInputArray[$i], QueueManager::$_appTmpFolderPath) === false){
						$photosInputArray[$i] = QueueManager::$_appTmpFolderPath.trim($photosInputArray[$i]);
					}
				}
				$itemToUpdate[QueueManager::$_COLN_photosInput] = implode(",", $photosInputArray);
			}
		}
		if ($outputMediaType != null  ||  ($queueItem[QueueManager::$_COLN_outputMediaType] != null) ){
			$itemToUpdate[QueueManager::$_COLN_outputMediaType] = ( ($outputMediaType != null) ? $outputMediaType : $queueItem[QueueManager::$_COLN_outputMediaType] );
		}
		if ($outputFilename != null  ||  ($queueItem[QueueManager::$_COLN_outputFilename] != null) ){
			$itemToUpdate[QueueManager::$_COLN_outputFilename] = ( ($outputFilename != null) ? $outputFilename : $queueItem[QueueManager::$_COLN_outputFilename] );
			if (strrpos($itemToUpdate[QueueManager::$_COLN_outputFilename], QueueManager::$_appTmpFolderPath) === false){
				$itemToUpdate[QueueManager::$_COLN_outputFilename] = QueueManager::$_appTmpFolderPath.$itemToUpdate[QueueManager::$_COLN_outputFilename];
			}
		}
		if ($logFile != null  ||  ($queueItem[QueueManager::$_COLN_logFile] != null) ){
			$itemToUpdate[QueueManager::$_COLN_logFile] = ( ($logFile != null) ? $logFile : $queueItem[QueueManager::$_COLN_logFile] );
			if (strrpos($itemToUpdate[QueueManager::$_COLN_logFile], QueueManager::$_appTmpFolderPath) === false){
				$itemToUpdate[QueueManager::$_COLN_logFile] = QueueManager::$_appTmpFolderPath.$itemToUpdate[QueueManager::$_COLN_logFile];
			}
		}
		if ($process_id != null  ||  ($queueItem[QueueManager::$_COLN_process_id] != null) ){
			$itemToUpdate[QueueManager::$_COLN_process_id] = ( ($process_id != null) ? $process_id : $queueItem[QueueManager::$_COLN_process_id] );
		}
		if ($extra_info != null  ||  ($queueItem[QueueManager::$_COLN_extra_info] != null) ){
			$itemToUpdate[QueueManager::$_COLN_extra_info] = ( ($extra_info != null) ? $extra_info : $queueItem[QueueManager::$_COLN_extra_info] );
		}
		if ($cmd_executed != null  ||  ($queueItem[QueueManager::$_COLN_cmd_executed] != null) ){
			$itemToUpdate[QueueManager::$_COLN_cmd_executed] = ( ($cmd_executed != null) ? $cmd_executed : $queueItem[QueueManager::$_COLN_cmd_executed] );
		}
		if ($cmd_to_execute != null  ||  ($queueItem[QueueManager::$_COLN_cmd_to_execute] != null) ){
			$itemToUpdate[QueueManager::$_COLN_cmd_to_execute] = ( ($cmd_to_execute != null) ? $cmd_to_execute : $queueItem[QueueManager::$_COLN_cmd_to_execute] );
		}
		return $this->getDb()->update($this->_dbTableName, QueueManager::$_COLN_ID, $id, $itemToUpdate);
	}
	
	public function deleteItemOnQueue($id){
		if ($id == 0){
			return false;
		}
		$itemToUpdate = $this->getDb()->select($this->_dbTableName, QueueManager::$_COLN_ID, $id);
		if (sizeof($itemToUpdate) != 1){
			return false;
		}
		return $this->getDb()->delete($this->_dbTableName, QueueManager::$_COLN_ID, $id);
	}
	
	public function getQueueItemById($id){
		if ($id == 0){
			return null;
		}
		$item = $this->getDb()->select($this->_dbTableName, QueueManager::$_COLN_ID, $id);
		if (sizeof($item) != 1){
			return null;
		}
		if (sizeof($item) == 1){
			return $item[0];
		}else{
			return $item;
		}
	}
	
	public function getOlderItemByStatus($wantedStatus){
		$item = null;
		$items = $this->getDb()->select($this->_dbTableName, QueueManager::$_COLN_status, $wantedStatus);
		if (sizeof($items) <= 0){
			return $item;
		}
		if (sizeof($items) > 1){
			//find the older
			$olderItem = $items[0];
			foreach ($items as $item){
				if ($item[QueueManager::$_COLN_arrived_on] < $olderItem[QueueManager::$_COLN_arrived_on]){
					$olderItem = $item;
				}
			}
			$item = $olderItem;
		}else{
			$item = $items[0];
		}
		return $item;
	}
	
	public function getProcessingQueue($jsonFile){
		$json= file_get_contents($jsonFile);
		$queueObj = json_decode($json);
		$i=0;
		foreach ($queueObj->Items as $item){
			$i++;
			echo "<br>".$item->Name." : ".$item->Comment;
		}
		if ($i == 0){
			echo "The queue is empty!";
		}
	}
	
	public function getDb(){
		if ($this->_dbInstance == null){
			$this->_dbInstance = new JsonDB($this->_dbPath);
			try{
				$this->_dbInstance->selectAll($this->_dbTableName);
			}catch(Exception $e){
				$this->_dbInstance->createTable($this->_dbTableName);
			}
		}
		return $this->_dbInstance;
	}
	
	//Verifies if a process is running in linux
	public function is_process_running($PID){
		exec("ps $PID", $ProcessState);
		return(count($ProcessState) >= 2);
	}
	
	public function getNextId(){
		$fullTable = $this->getDb()->selectAll($this->_dbTableName);
		$lastElement = end(array_values($fullTable));
		if (sizeof($lastElement)>=1){
			return $lastElement[QueueManager::$_COLN_ID]+1;
		}else{
			return 1;
		}
	}
	
	
}