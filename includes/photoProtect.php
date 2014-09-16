<?php
$urlparts = explode("/",$_SERVER['QUERY_STRING']);
$photoName = $urlparts[count($urlparts)-1]; // get the last part. This should be the photo filename

$titleBase =  preg_replace('/(.jpg|.png|.gif|.JPG|.PNG|.GIF)/',"", $photoName);
$titleBase = preg_replace('/([0-9]+[a-x][0-9]+)/','',$titleBase);

//path to the url
$dir = dirname(realpath($_SERVER['QUERY_STRING']));
$dh  = opendir($dir);
$biggest = 0;
while (false !== ($filename = @readdir($dh))) {
$var1 = preg_match("/(_watermarked)/", $filename);
$var2 = preg_match("/".$titleBase."/", $filename);
    if(preg_match("/(_watermarked)/", $filename) and preg_match("/".$titleBase."/", $filename) )
	if($biggest <= filesize($dir."/".$filename)){
	$file = $dir."/".$filename;
	$biggest = filesize($dir."/".$filename);
	}
}



//prevent the echo of any other type of file
if (preg_match("/(.jpg|.png|.gif|.JPG|.PNG|.GIF)/",$photoName)==1) {
    ob_start();
	$filename = basename($file);
	$file_extension = strtolower(substr(strrchr($filename,"."),1));
	
	switch( $file_extension ) {
		case "gif": $ctype="image/gif"; break;
		case "png": $ctype="image/png"; break;
		case "jpeg":
		case "jpg": $ctype="image/jpg"; break;
		default:
	}
	
	header('Content-type: ' . $ctype);
    $contents = file_get_contents($file);
    echo $contents;
    ob_end_flush();
} else {
	//echo a blank image    
}