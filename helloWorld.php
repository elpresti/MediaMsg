<?php

//echo "Holaaaa! esto es un hola mundo\nCon un salto de linea.\n\nFIN!\n\n";

echo phpinfo();

$cmd = "/root/ffmpeg/ffmpeg -y -i /var/www/mediamsg/test.webm -i /var/www/mediamsg/audioMsg.mp3 -c:v libx264 -vf fps=25  -c:a aac -strict -2  /var/www/mediamsg/outtest.mp4 </dev/null >/dev/null 2>/var/www/mediamsg/logtest.log & echo $!";

echo shell_exec($cmd);

?>