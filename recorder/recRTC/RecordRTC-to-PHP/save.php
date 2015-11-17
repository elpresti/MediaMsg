<?php
// Muaz Khan     - www.MuazKhan.com 
// MIT License   - https://www.webrtc-experiment.com/licence/
// Documentation - https://github.com/muaz-khan/WebRTC-Experiment/tree/master/RecordRTC
foreach(array('video', 'audio') as $type) {
    if (isset($_FILES["${type}-blob"])) {
    
        //echo 'uploads/';
        //echo '../../../tmp/';
        $fileName = $_POST["${type}-filename"];
        $uploadDirectory = '../../../tmp/'.$fileName;
        $tmpFileName = $_FILES["${type}-blob"]["tmp_name"];
        if (!move_uploaded_file($tmpFileName, $uploadDirectory)) {
            echo(" problem moving uploaded file. tmpFileName is $tmpFileName and uploadDirectory is $uploadDirectory");
        }else{
            //echo(" SUCCESS moving uploaded file. tmpFileName is $tmpFileName and uploadDirectory is $uploadDirectory");
            echo('../../../tmp/'.$fileName);
        }
        
        //echo('../../../tmp/'.$fileName);
    }
}
?>