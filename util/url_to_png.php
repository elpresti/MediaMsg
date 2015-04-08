<?php
// save this snippet as url_to_png.php
// usage: php url_to_png.php http://example.com
if (!isset($argv[1])){
	die("specify site: e.g. http://example.com\n");
}

$md5 = md5($argv[1]);
$command = "/usr/local/bin/wkhtmltopdf.sh $argv[1] $md5.pdf";
exec($command, $output, $ret);
if ($ret) {
	echo "error fetching screen dump\n";
	die;
}

$command = "convert -density 300 $md5.pdf -append $md5.png";
exec($command, $output, $ret);
if ($ret){
	echo "Error converting\n";
	die;
}

echo "Conversion compleated: $argv[1] converted to $md5.png\n";