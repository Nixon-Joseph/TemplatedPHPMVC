<?php

function OpenFile($FileName,$BitSize=1000000)
{
	$File = "";
	if(file_exists($FileName)) {
		$fp=fopen($FileName, 'r');
		$File=fread($fp,$BitSize);
		fclose($fp);
	}
	else {
		echo "<!-- File '$FileName' does not exist -->\n";
	}
	return $File;
}

function WriteToFile($Data, $FileName)
{
	if(file_exists($FileName)) {
		if (unlink($FileName)) {
			$fp=fopen($FileName, 'w');
			fwrite($fp, $Data);
		}
	}
	else {
		$fp=fopen($FileName, 'w');
		fwrite($fp, $Data);
	}
}

function GetFromUrl($Uri, $Dest, $Referer = "http://armory.worldofwarcraft.com/")
{
	echo $Uri."<br>\n";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_REFERER, $Referer);
	//curl_setopt($ch, CURLOPT_REFERER, "http://www.serebii.net/pokedex-dp/icon/001.gif");
	// cos they do browser detection! :O
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close($ch);

	// basic check to see if it's real data, or error!
	if (strlen($data) < 100) { // rly basic atm
		return 1;
	}
	WriteFile($data, $Dest);
	return 0;
}

function WriteFile($Data,$FileName)
{
	$fp=fopen($FileName, 'w');
	if(!(fputs($fp,$Data))) {
		return FALSE;
	}
	fclose($fp);
	return TRUE;
}

function Spaces($num, $str='')
{
	$nbsp = "&nbsp;";
	if (''!=$str) {
		settype($str,"string");
		$sLen = strlen($str);
		$num = $num - $sLen;
	}
	for ($j=0;$j<$num;$j++) {
		$nbsp .= "&nbsp;";
	}
	return $nbsp;
}

function UploadFile($file, $dir='./upload/', $filename) {
	$out = '';
	$ext = '';
	$file['name'] = strtolower($file['name']);
	if ($file['error'] >0) {
		$out .= "Problem: ";
		switch ($file['error']) {
			case 1: $out .= "File Exceeded upload_max_filesize"; break;
			case 2: $out .= "File Exceeded max_file_size"; break;
			case 3: $out .= "File only partially uploaded"; break;
			case 4: $out .= "No file uploaded"; break;
		}
		return $out;
		exit;
	}
	if ($file['tmp_name']=='none') {
		$out .= "problem: Uploaded file of zero length";
		return $out;
		exit;
	}
	if (strpos($filename, ".") > 0) {
		$ext = strrchr($filename, ".");
		$filename = substr($filename, 0, strpos($filename, "."));
	}
	else {
		$ext = strrchr($file["name"], ".");
	}
	$upfile = $dir.$filename.$ext;
	$out .= "tmp_file -> ".$file['tmp_name']."<br />";
	if (is_uploaded_file($file['tmp_name'])) {
		$out .= "new_loc -> ".$upfile."<br />";
		if (!move_uploaded_file($file['tmp_name'], $upfile)) {
			$out .= "Problem: Could not move the file";
			return $out;
			exit;
		}
	}
	else {
		$out .= "Problem: Possible file upload attack.<br />\nFilename: ".$file['name']." (".$file['tmp_name'].")";
		return $out;
		exit;
	}
	return FALSE;
}
?>