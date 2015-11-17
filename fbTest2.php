<?php
//header('Content-Type: image/jpg');

$filename = "https://scontent.xx.fbcdn.net/hphotos-xfp1/t31.0-8/p960x960/11011945_598124316956392_2336392518776829819_o.jpg";
/*
$gestor = fopen($filename, "r");
$contenido = fread($gestor, filesize($filename));
fclose($gestor);
echo $contenido;
*/
echo file_get_contents ($filename);

?>