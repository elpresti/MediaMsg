<?php
// usage: php url_to_png.php http://example.com

function printResultInJson(){
	global $outMsg, $outStatusCode;
	$arr = array('statusCode' => $outStatusCode, 'msg' => utf8_encode($outMsg)); //json_encode() will convert to null any non-utf8 String
	echo json_encode($arr);
}

$outMsg="NO MESSAGE";
$outStatusCode=500;

try{
	parse_str($_SERVER['QUERY_STRING'], $params);
	$targetUrl = ( (isset($params['url'])) ? urldecode($params['url']) : null );
	$outFileName = ( (isset($params['outfilename'])) ? urldecode($params['outfilename']) : null );
	$cropsArray = ( (isset($params['crops'])) ? array_map('trim', explode(',', $params['crops'])) : null ); 
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
	if ($cropsArray != null){ 
		$i = 1;
		foreach ($cropsArray as $cropItem){
			$command = "convert $outFileName.png -crop ".$cropItem." ".$outFileName."_".$i.".png";
			$outMsg.="Executing this command: ".$command." | ";
			exec($command, $output, $ret);
			if ($ret){
				$outMsg.="Error cropping image ".$i."! | ";
				goto print_output;
			}
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