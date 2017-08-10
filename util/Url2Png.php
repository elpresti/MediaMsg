<?php

require_once "FileManager.php";

final class Url2Png{
    public static $_outMsg="NO MESSAGE";
    public static $_outStatusCode=500;
    public static $_filesGenerated=null;
    public static $_urlParams=null;

    public static function getInstance(){
        static $inst = null;
        if ($inst === null) {
            $inst = new Url2Png();
        }
        parse_str($_SERVER['QUERY_STRING'], Url2Png::$_urlParams);
        return $inst;
    }

    private function __construct(){
    }

    public function printResultInJson(){
		$arr = array('statusCode' => Url2Png::$_outStatusCode, 'msg' => utf8_encode(Url2Png::$_outMsg)); //json_encode() will convert to null any non-utf8 String
		if (Url2Png::$_filesGenerated != null  &&  sizeof(Url2Png::$_filesGenerated)>0 ){
			$arr['filesGenerated'] = implode(',',Url2Png::$_filesGenerated);
		}
		$out = json_encode($arr);
		if (Url2Png::$_urlParams != null  &&  array_key_exists('echoprint', Url2Png::$_urlParams)  &&  Url2Png::$_urlParams['echoprint'] == 'true'){
			echo $out;
		}
		return $out;
	}

	public function execute($targetUrl=null,$outFileName=null,$crops=null,$scaleOut=null,$resizewidth=null){
		try{
			Url2Png::$_outMsg="";
			Url2Png::$_outStatusCode="500";
			Url2Png::$_filesGenerated=null;
			if ($targetUrl != null){
				$targetUrl = urldecode($targetUrl);
			}else{
				Url2Png::$_outMsg="specify site: e.g. http://example.com";
				return $this->printResultInJson();
			}
			if ($outFileName != null){
				$outFileName = urldecode($outFileName);
			}else{
				$outFileName = md5($targetUrl);
			}
			$crops = urldecode($crops);
			if ($crops != null){
				$cropsArray = array_map('trim', explode(',', $crops));
				//no usar urldecode para $params['crops'], ocaciona problemas
				//SMN: 990x434+538+980,456x348+346+1466,456x348+802+1466,462x347+1258+1466
			}else{
				$cropsArray = null;
			}
			if ($scaleOut != null){
				$scaleOut = urldecode($scaleOut);
			}
			$outFileName = "//var/www/mediamsg/tmp/".$outFileName;
			//$command = '//usr/local/bin/wkhtmltopdf.sh "'.$targetUrl.'" '.$outFileName.'.pdf';
			$command = 'wkhtmltopdf "'.$targetUrl.'" '.$outFileName.'.pdf';
			
			Url2Png::$_outMsg=" |  Executing this command: ".$command." | ";
			exec($command, $output, $ret);
			if ($ret) {
				Url2Png::$_outMsg.="|  Error fetching screen dump | Output: ".print_r($output,TRUE)." | ret: ".$ret;
				return $this->printResultInJson();
			}
			
			$command = "convert -density 300 $outFileName.pdf -append $outFileName.png";
			Url2Png::$_outMsg.="Executing this command: ".$command." | ";
			exec($command, $output, $ret);
			if ($ret){
				Url2Png::$_outMsg.="Error converting PDF2PNG! | ";
				return $this->printResultInJson();
			}
			Url2Png::$_filesGenerated = array();
			Url2Png::$_filesGenerated[] = $outFileName.".png";
			if ($cropsArray != null){ 
				$i = 1;
				foreach ($cropsArray as $cropItem){
					//$command = "convert $outFileName.png -crop ".urldecode($cropItem);
					$command = "convert $outFileName.png -crop ".$cropItem;
					if ($scaleOut != null){
						$command .= " -scale ".$scaleOut." ";
					}
					$command .= " ".$outFileName."_".$i.".png";
					Url2Png::$_outMsg.="Executing this command: ".$command." | ";
					exec($command, $output, $ret);
					if ($ret){
						Url2Png::$_outMsg.="Error cropping image ".$i."! | ABORTING CONVERT!";
						return $this->printResultInJson();
					}else{
						Url2Png::$_filesGenerated[] = $outFileName."_".$i.".png";
						if ($resizewidth != null){
							$filetoresize = Url2Png::$_filesGenerated[sizeof(Url2Png::$_filesGenerated)-1];
							$command = "convert $filetoresize -resize ".$resizewidth." $filetoresize";
							Url2Png::$_outMsg.="Executing this command: ".$command." | ";
							exec($command, $output, $ret);
							if ($ret){
								Url2Png::$_outMsg.="Error resizing image ".$i."! | ABORTING CONVERT!";
								return $this->printResultInJson();
							}
						}
					}
					$i++;
				}
			}
			Url2Png::$_outMsg.="Conversion completed: $targetUrl converted to $outFileName.png";
			if ($cropsArray!=null){
				Url2Png::$_outMsg.=" and crops done.";
			}
			if (sizeof(Url2Png::$_filesGenerated>0)){
				//unlink $outFileName.pdf
				if (FileManager::getInstance()->deleteLocalFiles($outFileName.".pdf",$dontTouchPath=true)){
					Url2Png::$_outMsg.="\n File $outFileName.pdf deleted";
				}else{
					Url2Png::$_outMsg.="\n File $outFileName.pdf CAN'T BE deleted";
				}
			}
			Url2Png::$_outStatusCode=200;
			return $this->printResultInJson();
		} catch (Exception $e) {
			Url2Png::$_outMsg="Error! Error details: ".(string)$e;
			Url2Png::$_outStatusCode=500;
			return $this->printResultInJson();
		}
	}

}

/*
// usage: php url_to_png.php http://example.com
function printResultInJson(){
	global $outMsg, $outStatusCode, $filesGenerated;
	$arr = array('statusCode' => $outStatusCode, 'msg' => utf8_encode($outMsg)); //json_encode() will convert to null any non-utf8 String
	if ($filesGenerated != null  &&  sizeof($filesGenerated)>0 ){
		$arr['filesGenerated'] = implode(',',$filesGenerated);
	}
	echo json_encode($arr);
}

$outMsg="NO MESSAGE";
$outStatusCode=500;
$filesGenerated = null;

try{
	parse_str($_SERVER['QUERY_STRING'], $params);
	$targetUrl = ( (isset($params['url'])) ? urldecode($params['url']) : null );
	$outFileName = ( (isset($params['outfilename'])) ? urldecode($params['outfilename']) : null );
	$cropsArray = ( (isset($params['crops'])) ? array_map('trim', explode(',', $params['crops'])) : null );
	$scaleOut = ( (isset($params['scaleOut'])) ? $params['scaleOut'] : null );
	//no usar urldecode para $params['crops'], ocaciona problemas
	//SMN: 990x434+538+980,456x348+346+1466,456x348+802+1466,462x347+1258+1466
	if ($targetUrl == null){
		$outMsg="specify site: e.g. http://example.com";
		goto print_output;
	}
	
	if ($outFileName == null){
		$outFileName = md5($targetUrl);
	}
	$outFileName = "//var/www/mediamsg/tmp/".$outFileName;
	$command = '//usr/local/bin/wkhtmltopdf.sh "'.$targetUrl.'" '.$outFileName.'.pdf';
	$outMsg=" |  Executing this command: ".$command." | ";
	exec($command, $output, $ret);
	if ($ret) {
		$outMsg.="|  Error fetching screen dump | Output: ".print_r($output,TRUE)." | ret: ".$ret;
		goto print_output;
	}
	
	$command = "convert -density 300 $outFileName.pdf -append $outFileName.png";
	$outMsg.="Executing this command: ".$command." | ";
	exec($command, $output, $ret);
	if ($ret){
		$outMsg.="Error converting PDF2PNG! | ";
		goto print_output;
	}
	$filesGenerated = array();
	$filesGenerated[] = $outFileName.".png";
	if ($cropsArray != null){ 
		$i = 1;
		foreach ($cropsArray as $cropItem){
			$command = "convert $outFileName.png -crop ".$cropItem;
			if ($scaleOut != null){
				$command .= " -scale ".$scaleOut." ";
			}
			$command .= " ".$outFileName."_".$i.".png";
			$outMsg.="Executing this command: ".$command." | ";
			exec($command, $output, $ret);
			if ($ret){
				$outMsg.="Error cropping image ".$i."! | ";
				goto print_output;
			}
			$filesGenerated[] = $outFileName."_".$i.".png";
			$i++;
		}
	}
	$outMsg.="Conversion completed: $targetUrl converted to $outFileName.png";
	if ($cropsArray!=null){
		$outMsg.=" and crops done.";
	}
} catch (Exception $e) {
	$outMsg="Error! Error details: ".(string)$e;
	$outStatusCode=500;
}

print_output:
printResultInJson();


*/

	parse_str($_SERVER['QUERY_STRING'], $params);
	$targetUrl = ( (isset($params['url'])) ? $params['url'] : null );
	$outFileName = ( (isset($params['outfilename'])) ? $params['outfilename'] : null );
	$crops = ( (isset($params['crops'])) ? $params['crops'] : null );
	$scaleOut = ( (isset($params['scaleOut'])) ? $params['scaleOut'] : null );
	if ($targetUrl != null  ||  $outFileName != null  ||  $crops != null  ||  $scaleOut != null  ||  sizeof($params)>3){
		Url2Png::getInstance()->execute($targetUrl,$outFileName,$crops,$scaleOut);
	}
