<?php

final class FfmpegManager{
    private $_isProcessingQueue=false;

    public static function getInstance(){
        static $inst = null;
        if ($inst === null) {
        	$inst = new FfmpegManager();
        }
        return $inst;
    }

    /**
     * Private ctor so nobody else can instance it
     *
     */
    private function __construct(){
    }

    public function validateItemParametersAndBuildCommand($queueItem){
        $out = false;
        $nowDateTime = date("Y_m_d__H_i_s");
        //check for valid parameters
        $mandatoryParametersPresent = 
            strlen($queueItem[QueueManager::$_COLN_outputMediaType])>2  && ((
            strlen($queueItem[QueueManager::$_COLN_audioInput])>3  &&
            strlen($queueItem[QueueManager::$_COLN_outputFilename])>3  &&
            strlen($queueItem[QueueManager::$_COLN_logFile])>3 )  ||  (
            strlen($queueItem[QueueManager::$_COLN_cmd_to_execute])>3
            		))
        ;
        if ($mandatoryParametersPresent) {
            if (!(file_exists($queueItem[QueueManager::$_COLN_audioInput])  &&  (filesize($queueItem[QueueManager::$_COLN_audioInput]) > 10000))){
                $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
                $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                $queueItem[QueueManager::$_COLN_extra_info] .= "\n Can't start processing because audio file is not present or has a invalid size. File path: ".$queueItem[QueueManager::$_COLN_audioInput];
            }else{
                if ($queueItem[QueueManager::$_COLN_outputMediaType] ==  QueueManager::$_MEDIA_TYPE_1){
                    $queueItem[QueueManager::$_COLN_extra_info] .= "\n Required files are OK.\n";
                }else{
                    if ($queueItem[QueueManager::$_COLN_outputMediaType] ==  QueueManager::$_MEDIA_TYPE_2){
                    	if (strlen($queueItem[QueueManager::$_COLN_cmd_to_execute])>3){
                    		$abort=false;
                    		$queueItem[QueueManager::$_COLN_cmd_executed] = "/usr/local/bin/ffmpeg -y ";
                    		if (!$abort && strlen($queueItem[QueueManager::$_COLN_videoInput])>3){
                    			if (!( file_exists($queueItem[QueueManager::$_COLN_videoInput])  &&  (filesize($queueItem[QueueManager::$_COLN_videoInput]) > 10000) )) {
                    				$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
                    				$queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                    				$queueItem[QueueManager::$_COLN_extra_info] .= "\n Can't start processing because video file is not present or has a invalid size. File path: ".$queueItem[QueueManager::$_COLN_videoInput];
                    				$abort=true;
                    			}else{
                    				$queueItem[QueueManager::$_COLN_cmd_executed] .= " -i ".$queueItem[QueueManager::$_COLN_videoInput];
                    			}
                    		}
                    		if (!$abort  &&  strlen($queueItem[QueueManager::$_COLN_audioInput])>3){
                    			if (!( file_exists($queueItem[QueueManager::$_COLN_audioInput])  &&  (filesize($queueItem[QueueManager::$_COLN_audioInput]) > 10000) )) {
                    				$queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
                    				$queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                    				$queueItem[QueueManager::$_COLN_extra_info] .= "\n Can't start processing because audio file is not present or has a invalid size. File path: ".$queueItem[QueueManager::$_COLN_audioInput];
                    				$abort=true;
                    			}else{
                    				$queueItem[QueueManager::$_COLN_cmd_executed] .= " -i ".$queueItem[QueueManager::$_COLN_audioInput];
                    			}
                    		}
                    		/*
                    		if (strlen($queueItem[QueueManager::$_COLN_photosInput])>3){
                    			//TODO $queueItem[QueueManager::$_COLN_cmd_executed] .= " -i "//.$queueItem[QueueManager::$_COLN_audioInput];
                    		}
                    		*/
                    		if (!$abort  &&  strlen($queueItem[QueueManager::$_COLN_cmd_to_execute])>3){
                    			$queueItem[QueueManager::$_COLN_cmd_executed] .= " ".$queueItem[QueueManager::$_COLN_cmd_to_execute]." ";
                    		}
                    		if (!$abort){
	                    		$queueItem[QueueManager::$_COLN_cmd_executed] .= " ".$queueItem[QueueManager::$_COLN_outputFilename]." ";
	                    		$queueItem[QueueManager::$_COLN_cmd_executed] .= " </dev/null >/dev/null 2>".$queueItem[QueueManager::$_COLN_logFile]." & echo $!";
	                    		$queueItem[QueueManager::$_COLN_extra_info] .= "\n Direct command detected. Required files are OK. Command built. \n";
                    		}
                    	}else{
	                        if (!( file_exists($queueItem[QueueManager::$_COLN_videoInput])  &&  (filesize($queueItem[QueueManager::$_COLN_videoInput]) > 10000) )) {
	                            $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
	                            $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
	                            $queueItem[QueueManager::$_COLN_extra_info] .= "\n Can't start processing because video file is not present or has a invalid size. File path: ".$queueItem[QueueManager::$_COLN_videoInput];
	                        }else{
	                            $queueItem[QueueManager::$_COLN_cmd_executed] = "/usr/local/bin/ffmpeg -y -i ".$queueItem[QueueManager::$_COLN_videoInput]." -i ".$queueItem[QueueManager::$_COLN_audioInput]." -c:v libx264 -vf fps=25  -c:a aac -strict -2  ".$queueItem[QueueManager::$_COLN_outputFilename];
	                            $queueItem[QueueManager::$_COLN_cmd_executed] .= " </dev/null >/dev/null 2>".$queueItem[QueueManager::$_COLN_logFile]." & echo $!";
	                            $queueItem[QueueManager::$_COLN_extra_info] .= "\n Required files are OK. Command built. \n";
	                        }
                    	}
                    }else{
                        if ($queueItem[QueueManager::$_COLN_outputMediaType] ==  QueueManager::$_MEDIA_TYPE_3){
                            $photoFiles = explode(',', $queueItem[QueueManager::$_COLN_photosInput]);
                            $filesOk=0;
                            foreach ($photoFiles as $photoFile) {
                                if (!( file_exists($photoFile)  &&  (filesize($photoFile) > 3000) )) {
                                    $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
                                    $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                                    $queueItem[QueueManager::$_COLN_extra_info] .= "\n Can't start processing because couldn't locate photo file named ".$photoFile." or it has a invalid size. File path: ".$queueItem[QueueManager::$_COLN_audioInput];
                                    break;
                                }else{
                                    $filesOk++;
                                }
                            }
                            if (sizeof($photoFiles) == $filesOk  &&  sizeof($photoFiles)>0){
                                //ffmpeg -framerate 1/5  -i msg%03d.png -i audioMsg.mp3 -c:v libx264 -vf fps=25 -pix_fmt yuv420p -c:a aac -strict experimental -b:a 192k -shortest out44.mp4
                                //running CMD 01042015 (but cuts con end of slideshow instead of the end of audio): ffmpeg -framerate 1/3  -i %05d.morph.jpg -i audioMsg.mp3 -c:v libx264 -pix_fmt yuv420p -c:a aac -strict experimental -b:a 192k -shortest out56.mp4
                                $queueItem[QueueManager::$_COLN_cmd_executed] = "/usr/local/bin/ffmpeg -y -framerate 1/5 -i ".$photoFiles[0]." -i ".$queueItem[QueueManager::$_COLN_audioInput]." -c:v libx264 -vf fps=25 -pix_fmt yuv420p -c:a aac -strict experimental -b:a 192k -shortest ".$queueItem[QueueManager::$_COLN_outputFilename];
                                $queueItem[QueueManager::$_COLN_cmd_executed] .= " </dev/null >/dev/null 2>".$queueItem[QueueManager::$_COLN_logFile]." & echo $!";
                                $queueItem[QueueManager::$_COLN_extra_info] .= "\n Required files are OK. \n";
                            }else{
                                $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
                                $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                                $queueItem[QueueManager::$_COLN_extra_info] .= "\n Not all photo files succeded the validation test. Command not built. \n";
                            }
                        }
                    }
                }
            }
        }else{
            $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_8;
            $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
            $queueItem[QueueManager::$_COLN_extra_info] .= "Can't start processing because not all mandatory parameters are present. Item data: ".print_r($queueItem);
        }
        return $queueItem;
    }

    public function runEncoder($queueItem){
        echo "<br>Encoder method is being processed!<br>";
        $nowDateTime = date("Y_m_d__H_i_s");
        $queueItem = $this->validateItemParametersAndBuildCommand($queueItem);
        QueueManager::getInstance()->updateItemOnQueue($queueItem);
        if ($queueItem[QueueManager::$_COLN_status] != QueueManager::$_ITEM_STATUS_8){
            $queueItem[QueueManager::$_COLN_extra_info] .= "\n Starting ffmpeg...\n\n";
            try{
                $queueItem[QueueManager::$_COLN_process_id] = shell_exec($queueItem[QueueManager::$_COLN_cmd_executed]);
                $queueItem[QueueManager::$_COLN_process_id] = preg_replace("/[^0-9]/", "", $queueItem[QueueManager::$_COLN_process_id]);
            }catch(Exception $e){
                $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_6;
                $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                $queueItem[QueueManager::$_COLN_extra_info] .= "\n Exception while executing command. \n Exception content: \n ".((string) $e);
            }
            if (!($queueItem[QueueManager::$_COLN_process_id]>0)){
                $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_6;
                $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
                $queueItem[QueueManager::$_COLN_extra_info] .= "Can't get Process ID. Item data: ".print_r($queueItem);
            }else{
                $queueItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_2;
                $queueItem[QueueManager::$_COLN_last_status_change] = $nowDateTime;
            }
            $queueItem[QueueManager::$_COLN_extra_info] .= "\n\n Done...\n Data of processing Item: ".print_r($queueItem);
        }
        return $queueItem;
    }

    public function processQueue(){
        //echo "<br>table content BEFORE processing queue:<br>".print_r(QueueManager::getInstance()->getDb()->selectAll(QueueManager::$_dbTableName))."<br><br>";
        //echo "<br>result of insert(): ".QueueManager::getInstance()->addItemToQueue("audioMsg.mp3", "test.webm");
        //echo "<br>result of update(): ".QueueManager::getInstance()->updateItemOnQueue(2,null, "videoooInputt.exe", QueueManager::$_ITEM_STATUS_2QueueManager::     //echo "<br>result of delete(): ".QueueManager::getInstance()->deleteItemOnQueue(1);
        //echo "<br>result of getQueueItemById(): ".print_r(QueueManager::getInstance()->getQueueItemById(2));

        //check if it's processing any item
        $processingItem = QueueManager::getInstance()->getOlderItemByStatus(QueueManager::$_ITEM_STATUS_2);
        if ($processingItem == null){
            $this->_isProcessingQueue = false;
        }else{
            //update status and check if it's still processing that item
            QueueManager::getInstance()->updateItemOnQueue(QueueManager::getInstance()->checkProcessingStatus($processingItem));
            $processingItem = QueueManager::getInstance()->getOlderItemByStatus(QueueManager::$_ITEM_STATUS_2);
            if ($processingItem != null  &&  ($processingItem[QueueManager::$_COLN_status] ==  QueueManager::$_ITEM_STATUS_2) ){
                $this->_isProcessingQueue = true;
            }else{
                $this->_isProcessingQueue = false;
            }
        }
        if (!$this->_isProcessingQueue){
            $this->_isProcessingQueue = true;
            $pendingItem = QueueManager::getInstance()->getOlderItemByStatus(QueueManager::$_ITEM_STATUS_1);
            echo "<br>\n pendingItem content: ".print_r($pendingItem);
            if ($pendingItem != null  &&  sizeof($pendingItem)>0){
                $pendingItem = $this->runEncoder($pendingItem);
                QueueManager::getInstance()->updateItemOnQueue($pendingItem);
                if ($pendingItem[QueueManager::$_COLN_status] !=  QueueManager::$_ITEM_STATUS_6){
                    //sleep(5);
                    //QueueManager::getInstance()->updateItemOnQueue(QueueManager::getInstance()->checkProcessingStatus($pendingItem));
                }else{
                    echo $pendingItem[QueueManager::$_COLN_status]."<br>\n".$pendingItem[QueueManager::$_COLN_extra_info];
                }
            }else{
                echo "No items match!";
            }
            $this->_isProcessingQueue = false;
            //echo "<br>table content AFTER processing queue:<br>".print_r(QueueManager::getInstance()->getDb()->selectAll(QueueManager::$_dbTableName))."<br>";
        }else{
            echo "Processing this item: \n".print_r($processingItem)."\n Wait until it finish or try again later";
        }
        $pendingUploadItem = QueueManager::getInstance()->getOlderItemByStatus(QueueManager::$_ITEM_STATUS_3);
        if ($pendingUploadItem != null){
        	$this->_isProcessingQueue = true;
        	$pendingUploadItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_4;
        	QueueManager::getInstance()->updateItemOnQueue($pendingUploadItem);
        	FileManager::getInstance()->uploadFile($pendingUploadItem[QueueManager::$_COLN_outputFilename]);
        	if (FileManager::getInstance()->_outStatusCode == 500){
        		$pendingUploadItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_7;
        		echo FileManager::getInstance()->_outMsg;
        	}else{
        		$pendingUploadItem[QueueManager::$_COLN_status] = QueueManager::$_ITEM_STATUS_5;
        		$localFilesToDelete = array($pendingUploadItem[QueueManager::$_COLN_audioInput],$pendingUploadItem[QueueManager::$_COLN_videoInput],$pendingUploadItem[QueueManager::$_COLN_outputFilename]);
        		FileManager::getInstance()->deleteLocalFiles($localFilesToDelete);
        	}
        	$pendingUploadItem[QueueManager::$_COLN_extra_info] .= ". \n ".FileManager::getInstance()->_outMsg;
        	QueueManager::getInstance()->updateItemOnQueue($pendingUploadItem);
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

    

    //update errorLog field

    //mark as uploading

    //mark as uploaded

    
}

?>