<?php
//comment
final class FfmpegManager{
    private $_jsonDbClass = null;
    private $_fileManagerClass = null;
    private $_appFolderPath = "/var/www/mediamsg/";
    public $_appTmpFolderPath = "/var/www/mediamsg/tmp/";
    private $_ITEM_STATUS_1 = "PENDING_TO_PROCESS";
    private $_ITEM_STATUS_2 = "PROCESSING";
    private $_ITEM_STATUS_3 = "PENDING_TO_UPLOAD";
    private $_ITEM_STATUS_4 = "UPLOADING_TO_FTP";
    private $_ITEM_STATUS_5 = "UPLOADED";
    private $_ITEM_STATUS_6 = "FAILED_WHILE_PROCESSING";
    private $_ITEM_STATUS_7 = "FAILED_WHILE_UPLOADING";
    private $_ITEM_STATUS_8 = "FAILED_WHILE_VALIDATING";
    public $_MEDIA_TYPE_1 = "AUDIO";
    public $_MEDIA_TYPE_2 = "AUDIO_AND_VIDEO";
    public $_MEDIA_TYPE_3 = "AUDIO_AND_PHOTOS";
    private $_COLN_logFile = "logFile";
    private $_COLN_outputFilename = "outputFilename";
    private $_COLN_videoInput = "videoInput";
    private $_COLN_audioInput = "audioInput";
    private $_COLN_photosInput = "photosInput";
    private $_COLN_outputMediaType = "outputMediaType";
    private $_COLN_last_status_change = "last_status_change";
    private $_COLN_status = "status";
    private $_COLN_arrived_on = "arrived_on";
    private $_COLN_ID = "id";
    private $_COLN_process_id = "process_id";
    private $_COLN_extra_info = "extra_info";
    private $_COLN_cmd_executed = "cmd_executed";
    private $_dbInstance = null;
    private $_dbPath = "./";
    private $_dbTableName="processingQueue";
    private $_isProcessingQueue=false;

    public static function getInstance($_jsonDbClass=null,$_fileManagerClass=null){
        static $inst = null;
        if ($inst === null) {
        	if ($_jsonDbClass != null  &&  $_fileManagerClass != null){
        		$inst = new FfmpegManager($_jsonDbClass,$_fileManagerClass);
        	}
        }
        return $inst;
    }

    /**
     * Private ctor so nobody else can instance it
     *
     */
    private function __construct($_jsonDbClass,$_fileManagerClass){
        $this->_jsonDbClass = $_jsonDbClass;
        $this->_fileManagerClass = $_fileManagerClass;
    }

    public function validateItemParametersAndBuildCommand($queueItem){
        $out = false;
        $nowDateTime = date("Y_m_d__H_i_s");
        //check for valid parameters
        $mandatoryParametersPresent = 
            strlen($queueItem[$this->_COLN_outputMediaType])>2  &&
            strlen($queueItem[$this->_COLN_audioInput])>3  &&
            strlen($queueItem[$this->_COLN_outputFilename])>3  &&
            strlen($queueItem[$this->_COLN_logFile])>3
        ;
        if ($mandatoryParametersPresent) {
            if (!(file_exists($queueItem[$this->_COLN_audioInput])  &&  (filesize($queueItem[$this->_COLN_audioInput]) > 10000))){
                $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_8;
                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                $queueItem[$this->_COLN_extra_info] .= "\n Can't start processing because audio file is not present or has a invalid size. File path: ".$queueItem[$this->_COLN_audioInput];
            }else{
                if ($queueItem[$this->_COLN_outputMediaType] ==  $this->_MEDIA_TYPE_1){
                    $queueItem[$this->_COLN_extra_info] .= "\n Required files are OK.\n";
                }else{
                    if ($queueItem[$this->_COLN_outputMediaType] ==  $this->_MEDIA_TYPE_2){
                        if (!( file_exists($queueItem[$this->_COLN_videoInput])  &&  (filesize($queueItem[$this->_COLN_videoInput]) > 10000) )) {
                            $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_8;
                            $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                            $queueItem[$this->_COLN_extra_info] .= "\n Can't start processing because video file is not present or has a invalid size. File path: ".$queueItem[$this->_COLN_audioInput];
                        }else{
                            $queueItem[$this->_COLN_cmd_executed] = "/root/ffmpeg/ffmpeg -y -i ".$queueItem[$this->_COLN_videoInput]." -i ".$queueItem[$this->_COLN_audioInput]." -c:v libx264 -vf fps=25  -c:a aac -strict -2  ".$queueItem[$this->_COLN_outputFilename];
                            $queueItem[$this->_COLN_cmd_executed] .= " </dev/null >/dev/null 2>".$queueItem[$this->_COLN_logFile]." & echo $!";
                            $queueItem[$this->_COLN_extra_info] .= "\n Required files are OK. Command built. \n";
                        }
                    }else{
                        if ($queueItem[$this->_COLN_outputMediaType] ==  $this->_MEDIA_TYPE_3){
                            $photoFiles = explode(',', $queueItem[$this->_COLN_photosInput]);
                            $filesOk=0;
                            foreach ($photoFiles as $photoFile) {
                                if (!( file_exists($photoFile)  &&  (filesize($photoFile) > 3000) )) {
                                    $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_8;
                                    $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                                    $queueItem[$this->_COLN_extra_info] .= "\n Can't start processing because couldn't locate photo file named ".$photoFile." or it has a invalid size. File path: ".$queueItem[$this->_COLN_audioInput];
                                    break;
                                }else{
                                    $filesOk++;
                                }
                            }
                            if (sizeof($photoFiles) == $filesOk  &&  sizeof($photoFiles)>0){
                                //ffmpeg -framerate 1/5  -i msg%03d.png -i audioMsg.mp3 -c:v libx264 -vf fps=25 -pix_fmt yuv420p -c:a aac -strict experimental -b:a 192k -shortest out44.mp4
                                $queueItem[$this->_COLN_cmd_executed] = "/root/ffmpeg/ffmpeg -y -framerate 1/5 -i ".$photoFiles[0]." -i ".$queueItem[$this->_COLN_audioInput]." -c:v libx264 -vf fps=25 -pix_fmt yuv420p -c:a aac -strict experimental -b:a 192k -shortest ".$queueItem[$this->_COLN_outputFilename];
                                $queueItem[$this->_COLN_cmd_executed] .= " </dev/null >/dev/null 2>".$queueItem[$this->_COLN_logFile]." & echo $!";
                                $queueItem[$this->_COLN_extra_info] .= "\n Required files are OK. \n";
                            }else{
                                $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_8;
                                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                                $queueItem[$this->_COLN_extra_info] .= "\n Not all photo files succeded the validation test. Command not built. \n";
                            }
                        }
                    }
                }
            }
        }else{
            $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_8;
            $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
            $queueItem[$this->_COLN_extra_info] .= "Can't start processing because not all mandatory parameters are present. Item data: ".print_r($queueItem);
        }
        return $queueItem;
    }

    public function runEncoder($queueItem){
        echo "<br>Encoder method is being processed!<br>";
        $nowDateTime = date("Y_m_d__H_i_s");
        $queueItem = $this->validateItemParametersAndBuildCommand($queueItem);
        $this->updateItemOnQueue($queueItem);
        if ($queueItem[$this->_COLN_status] != $this->_ITEM_STATUS_8){
            $queueItem[$this->_COLN_extra_info] .= "\n Starting ffmpeg...\n\n";
            try{
                $queueItem[$this->_COLN_process_id] = shell_exec($queueItem[$this->_COLN_cmd_executed]);
                $queueItem[$this->_COLN_process_id] = preg_replace("/[^0-9]/", "", $queueItem[$this->_COLN_process_id]);
            }catch(Exception $e){
                $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_6;
                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                $queueItem[$this->_COLN_extra_info] .= "\n Exception while executing command. \n Exception content: \n ".((string) $e);
            }
            if (!($queueItem[$this->_COLN_process_id]>0)){
                $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_6;
                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                $queueItem[$this->_COLN_extra_info] .= "Can't get Process ID. Item data: ".print_r($queueItem);
            }else{
                $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_2;
                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
            }
            $queueItem[$this->_COLN_extra_info] .= "\n\n Done...\n Data of processing Item: ".print_r($queueItem);
        }
        return $queueItem;
    }

    public function processQueue(){
        //echo "<br>table content BEFORE processing queue:<br>".print_r($this->getDb()->selectAll($this->_dbTableName))."<br><br>";
        //echo "<br>result of insert(): ".$this->addItemToQueue("audioMsg.mp3", "test.webm");
        //echo "<br>result of update(): ".$this->updateItemOnQueue(2,null, "videoooInputt.exe", $this->_ITEM_STATUS_2);
        //echo "<br>result of delete(): ".$this->deleteItemOnQueue(1);
        //echo "<br>result of getQueueItemById(): ".print_r($this->getQueueItemById(2));

        //check if it's processing any item
        $processingItem = $this->getOlderItemByStatus($this->_ITEM_STATUS_2);
        if ($processingItem == null){
            $this->_isProcessingQueue = false;
        }else{
            //update status and check if it's still processing that item
            $this->updateItemOnQueue($this->checkProcessingStatus($processingItem));
            $processingItem = $this->getOlderItemByStatus($this->_ITEM_STATUS_2);
            if ($processingItem != null  &&  ($processingItem[$this->_COLN_status] ==  $this->_ITEM_STATUS_2) ){
                $this->_isProcessingQueue = true;
            }else{
                $this->_isProcessingQueue = false;
            }
        }
        if (!$this->_isProcessingQueue){
            $this->_isProcessingQueue = true;
            $pendingItem = $this->getOlderItemByStatus($this->_ITEM_STATUS_1);
            echo "<br>\n pendingItem content: ".print_r($pendingItem);
            if ($pendingItem != null  &&  sizeof($pendingItem)>0){
                $pendingItem = $this->runEncoder($pendingItem);
                $this->updateItemOnQueue($pendingItem);
                if ($pendingItem[$this->_COLN_status] !=  $this->_ITEM_STATUS_6){
                    //sleep(5);
                    //$this->updateItemOnQueue($this->checkProcessingStatus($pendingItem));
                }else{
                    echo $pendingItem[$this->_COLN_status]."<br>\n".$pendingItem[$this->_COLN_extra_info];
                }
            }else{
                echo "No items match!";
            }
            $this->_isProcessingQueue = false;
            //echo "<br>table content AFTER processing queue:<br>".print_r($this->getDb()->selectAll($this->_dbTableName))."<br>";
        }else{
            echo "Processing this item: \n".print_r($processingItem)."\n Wait until it finish or try again later";
        }
        $pendingUploadItem = $this->getOlderItemByStatus($this->_ITEM_STATUS_3);
        if ($pendingUploadItem != null){
        	$this->_isProcessingQueue = true;
        	$pendingUploadItem[$this->_COLN_status] = $this->_ITEM_STATUS_4;
        	$this->updateItemOnQueue($pendingUploadItem);
        	$this->_fileManagerClass->uploadFile($pendingUploadItem[$this->_COLN_outputFilename]);
        	if ($this->_fileManagerClass->_outStatusCode == 500){
        		$pendingUploadItem[$this->_COLN_status] = $this->_ITEM_STATUS_7;
        		echo $this->_fileManagerClass->_outMsg;
        	}else{
        		$pendingUploadItem[$this->_COLN_status] = $this->_ITEM_STATUS_5;
        		$localFilesToDelete = array($pendingUploadItem[$this->_COLN_audioInput],$pendingUploadItem[$this->_COLN_videoInput],$pendingUploadItem[$this->_COLN_outputFilename]);
        		$this->_fileManagerClass->deleteLocalFiles($localFilesToDelete);
        	}
        	$pendingUploadItem[$this->_COLN_extra_info] .= ". \n ".$this->_fileManagerClass->_outMsg;
        	$this->updateItemOnQueue($pendingUploadItem);
        	$this->_isProcessingQueue = false;
        	// seguir acá!!
        }else{
        	$this->_isProcessingQueue = false;
        }
    }

    //add a new record to process

    // process the queue

    //update the status to processing

    //mark as processed

    public function checkProcessingStatus($queueItem){
        if ($queueItem[$this->_COLN_status] == $this->_ITEM_STATUS_2  &&  ((int) $queueItem[$this->_COLN_process_id]) > 0){
            if (!($this->is_process_running($queueItem[$this->_COLN_process_id]))) {
                $queueItem[$this->_COLN_last_status_change] = $nowDateTime;
                if (file_exists($queueItem[$this->_COLN_outputFilename])) {
                    if (filesize($queueItem[$this->_COLN_outputFilename]) > 100000 ) { //filezie()>100kb
                        $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_3;
                        $queueItem[$this->_COLN_extra_info] .= "\n Files well processed!";
                    }else{
                        $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_6;
                        $queueItem[$this->_COLN_extra_info] .= "Process end with an output file of ".filesize($queueItem[$this->_COLN_outputFilename])." bytes.\n";
                        //$queueItem[$this->_COLN_extra_info] .= "This is logfile content:\n---\n".file_get_contents('./gente.txt', true);
                        $queueItem[$this->_COLN_extra_info] .= "This is logfile content:\n---\n".file_get_contents($queueItem[$this->_COLN_logFile], true)."\n---\n";
                    }
                }else{
                    $queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_6;
                    $queueItem[$this->_COLN_extra_info] .= "Process end but the output file was not created.\n This is logfile content:\n---\n".file_get_contents($queueItem[$this->_COLN_logFile], true)."\n---\n";
                }
            }else{
                echo "Still working!";
            }
        }else{
            //$queueItem[$this->_COLN_status] = $this->_ITEM_STATUS_6;
            //$queueItem[$this->_COLN_last_status_change] = $nowDateTime;
            //$queueItem[$this->_COLN_extra_info] = "QueueItem status: ".$queueItem[$this->_COLN_status].".\n QueueItem Process ID: ".$queueItem[$this->_COLN_process_id];
        }
        return $queueItem;
    }

    //update errorLog field

    //mark as uploading

    //mark as uploaded

    public function addItemToQueue($audioInput, $videoInput=null, $photosInput = null, $outputMediaType = null, $status=null, $alternativeFilename = null, $outputFilename = null, $logFile = null, $extra_info = null){
        if ($audioInput == null  ||  strlen($audioInput)<5){
            return "Error! audioInput parameter is mandatory. Item not added.";
        }
        $nowDateTime = date("Y_m_d__H_i_s");
        //set default values and add the correct file path
        $audioInput = $this->_appTmpFolderPath.$audioInput;
        if ($videoInput != null){
            $videoInput = $this->_appTmpFolderPath.$videoInput;
        }
        if ($photosInput != null){
            $photosInputArray = explode(',', $photosInput);
            if (sizeof($photosInputArray)>0){
                for ($i=0; $i<sizeof($photosInputArray); $i++){
                    $photosInputArray[$i] = $this->_appTmpFolderPath.trim($photosInputArray[$i]);
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
        $outputFilename = $this->_appTmpFolderPath.$outputFilename;
        if ($logFile == null){
            $logFile = $alternativeFilename.".log";
        }
        if ($status == null){
            $status = $this->_ITEM_STATUS_1;
        }
        if ($extra_info == null){
            $extra_info = "JUST ARRIVED";
        }
        if ($outputMediaType == null){
            if ($videoInput == null  &&  $photosInput != null){
                $outputMediaType = $this->_MEDIA_TYPE_3;
            }else{
                if ($videoInput != null  &&  $photosInput == null){
                    $outputMediaType = $this->_MEDIA_TYPE_2;
                }else{
                    $outputMediaType = $this->_MEDIA_TYPE_1;
                }
            }
        }
        
        $rowData = array(
            $this->_COLN_ID => $this->getNextId(),
            $this->_COLN_arrived_on => $nowDateTime,
            $this->_COLN_status => $status,
            $this->_COLN_last_status_change => $nowDateTime,
            $this->_COLN_audioInput => $audioInput,
            $this->_COLN_videoInput => $videoInput,
            $this->_COLN_photosInput => $photosInput,
            $this->_COLN_outputMediaType => $outputMediaType,
            $this->_COLN_outputFilename => $outputFilename,
            $this->_COLN_process_id => 0,
            $this->_COLN_extra_info => $extra_info,
            $this->_COLN_cmd_executed => null,
            $this->_COLN_logFile => $this->_appTmpFolderPath.$logFile
        );
        if ($this->getDb()->insert($this->_dbTableName, $rowData) > 0){
            return ($this->getNextId() - 1);
        }else{
            return "Error trying to add a new item to queue";
        }
    }

    public function updateItemOnQueue($queueItem, $id=null, $audioInput=null, $videoInput=null, $photosInput=null, $outputMediaType=null, $status=null, $outputFilename = null, $logFile = null, $process_id = null, $extra_info = null, $cmd_executed = null){
        if ($id == null  &&  (!($queueItem[$this->_COLN_ID] > 0)) ){
            return false;
        }
        $nowDateTime = date("Y_m_d__H_i_s");
        if ($id == null){
            $id = $queueItem[$this->_COLN_ID];
        }
        $itemToUpdate = $this->getDb()->select($this->_dbTableName, $this->_COLN_ID, $id);
        if (sizeof($itemToUpdate) != 1){
            return false;
        }
        $itemToUpdate=$itemToUpdate[0];
        if ($status != null  ||  ($queueItem[$this->_COLN_status] != null) ){
            $itemToUpdate[$this->_COLN_status] = ( ($status != null) ? $status : $queueItem[$this->_COLN_status] );
            $itemToUpdate[$this->_COLN_last_status_change] = $nowDateTime;
        }
        if ($audioInput != null  ||  ($queueItem[$this->_COLN_audioInput] != null) ){
            $itemToUpdate[$this->_COLN_audioInput] = ( ($audioInput != null) ? $audioInput : $queueItem[$this->_COLN_audioInput] );
            if (strrpos($itemToUpdate[$this->_COLN_audioInput], $this->_appTmpFolderPath) === false){
                $itemToUpdate[$this->_COLN_audioInput] = $this->_appTmpFolderPath.$itemToUpdate[$this->_COLN_audioInput];
            }
        }
        if ($videoInput != null  ||  ($queueItem[$this->_COLN_videoInput] != null) ){
            $itemToUpdate[$this->_COLN_videoInput] = ( ($videoInput != null) ? $videoInput : $queueItem[$this->_COLN_videoInput] );
            if (strrpos($itemToUpdate[$this->_COLN_videoInput], $this->_appTmpFolderPath) === false){
                $itemToUpdate[$this->_COLN_videoInput] = $this->_appTmpFolderPath.$itemToUpdate[$this->_COLN_videoInput];
            }
        }
        if ($photosInput != null  ||  ($queueItem[$this->_COLN_photosInput] != null) ){
            $itemToUpdate[$this->_COLN_photosInput] = ( ($photosInput != null) ? $photosInput : $queueItem[$this->_COLN_photosInput] );
            $photosInputArray = explode(',', $itemToUpdate[$this->_COLN_photosInput]);
            if (sizeof($photosInputArray)>0){
                for ($i=0; $i<sizeof($photosInputArray); $i++){
                    if (strrpos($photosInputArray[$i], $this->_appTmpFolderPath) === false){
                        $photosInputArray[$i] = $this->_appTmpFolderPath.trim($photosInputArray[$i]);
                    }
                }
                $itemToUpdate[$this->_COLN_photosInput] = implode(",", $photosInputArray);
            }
        }
        if ($outputMediaType != null  ||  ($queueItem[$this->_COLN_outputMediaType] != null) ){
            $itemToUpdate[$this->_COLN_outputMediaType] = ( ($outputMediaType != null) ? $outputMediaType : $queueItem[$this->_COLN_outputMediaType] );
        }
        if ($outputFilename != null  ||  ($queueItem[$this->_COLN_outputFilename] != null) ){
            $itemToUpdate[$this->_COLN_outputFilename] = ( ($outputFilename != null) ? $outputFilename : $queueItem[$this->_COLN_outputFilename] );
            if (strrpos($itemToUpdate[$this->_COLN_outputFilename], $this->_appTmpFolderPath) === false){
                $itemToUpdate[$this->_COLN_outputFilename] = $this->_appTmpFolderPath.$itemToUpdate[$this->_COLN_outputFilename];
            }
        }
        if ($logFile != null  ||  ($queueItem[$this->_COLN_logFile] != null) ){
            $itemToUpdate[$this->_COLN_logFile] = ( ($logFile != null) ? $logFile : $queueItem[$this->_COLN_logFile] );
            if (strrpos($itemToUpdate[$this->_COLN_logFile], $this->_appTmpFolderPath) === false){
                $itemToUpdate[$this->_COLN_logFile] = $this->_appTmpFolderPath.$itemToUpdate[$this->_COLN_logFile];
            }
        }
        if ($process_id != null  ||  ($queueItem[$this->_COLN_process_id] != null) ){
            $itemToUpdate[$this->_COLN_process_id] = ( ($process_id != null) ? $process_id : $queueItem[$this->_COLN_process_id] );
        }
        if ($extra_info != null  ||  ($queueItem[$this->_COLN_extra_info] != null) ){
            $itemToUpdate[$this->_COLN_extra_info] = ( ($extra_info != null) ? $extra_info : $queueItem[$this->_COLN_extra_info] );
        }
        if ($cmd_executed != null  ||  ($queueItem[$this->_COLN_cmd_executed] != null) ){
            $itemToUpdate[$this->_COLN_cmd_executed] = ( ($cmd_executed != null) ? $cmd_executed : $queueItem[$this->_COLN_cmd_executed] );
        }
        return $this->getDb()->update($this->_dbTableName, $this->_COLN_ID, $id, $itemToUpdate);
    }

    public function deleteItemOnQueue($id){
        if ($id == 0){
            return false;
        }
        $itemToUpdate = $this->getDb()->select($this->_dbTableName, $this->_COLN_ID, $id);
        if (sizeof($itemToUpdate) != 1){
            return false;
        }
        return $this->getDb()->delete($this->_dbTableName, $this->_COLN_ID, $id);
    }

    public function getQueueItemById($id){
        if ($id == 0){
            return null;
        }
        $item = $this->getDb()->select($this->_dbTableName, $this->_COLN_ID, $id);
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
        $items = $this->getDb()->select($this->_dbTableName, $this->_COLN_status, $wantedStatus);
        if (sizeof($items) <= 0){
            return $item;
        }
        if (sizeof($items) > 1){
            //find the older
            $olderItem = $items[0];
            foreach ($items as $item){
                if ($item[$this->_COLN_arrived_on] < $olderItem[$this->_COLN_arrived_on]){
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
            return $lastElement[$this->_COLN_ID]+1;
        }else{
            return 1;
        }
    }

}

?>