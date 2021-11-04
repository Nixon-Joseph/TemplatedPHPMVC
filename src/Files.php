<?php namespace devpirates\MVC;
class Files {
	public static function OpenFile(string $fileName, $BitSize=1000000)
	{
		$file = "";
		if(file_exists($fileName)) {
			$fp = fopen($fileName, 'r');
			$file = fread($fp,$BitSize);
			fclose($fp);
		} else {
			echo "<!-- File '$fileName' does not exist -->\n";
		}
		return $file;
	}
	
	public static function WriteToFile(string $data, string $fileName)
	{
		if(file_exists($fileName)) {
			if (unlink($fileName)) {
				$fp = fopen($fileName, 'w');
				fwrite($fp, $data);
			}
		} else {
			$fp = fopen($fileName, 'w');
			fwrite($fp, $data);
		}
	}
	
	public static function GetFromUrl(string $uri, string $dest, string $referer = "http://armory.worldofwarcraft.com/")
	{
		echo $uri."<br>\n";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
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
		self::WriteFile($data, $dest);
		return 0;
	}
	
	public static function WriteFile(string $data, string $fileName)
	{
		$fp = fopen($fileName, 'w');
		if ((fputs($fp, $data)) === false) {
			return false;
		}
		fclose($fp);
		return true;
	}
	
	public static function Spaces(int $num, string $str = '')
	{
		$nbsp = "&nbsp;";
		if ('' != $str) {
			settype($str, "string");
			$sLen = strlen($str);
			$num = $num - $sLen;
		}
		for ($j = 0; $j < $num; $j++) {
			$nbsp .= "&nbsp;";
		}
		return $nbsp;
	}
	
	public static function UploadFile($file, string $filename, string $dir = './upload/') {
		$out = '';
		$ext = '';
		$file['name'] = strtolower($file['name']);
		if ($file['error'] > 0) {
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
		if ($file['tmp_name'] == 'none') {
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
		$upfile = $dir  .$filename . $ext;
		$out .= "tmp_file -> " . $file['tmp_name'] . "<br />";
		if (is_uploaded_file($file['tmp_name'])) {
			$out .= "new_loc -> " . $upfile . "<br />";
			if (!move_uploaded_file($file['tmp_name'], $upfile)) {
				$out .= "Problem: Could not move the file";
				return $out;
				exit;
			}
		} else {
			$out .= "Problem: Possible file upload attack.<br />\nFilename: ".$file['name']." (".$file['tmp_name'].")";
			return $out;
			exit;
		}
		return false;
	}

	public static function Fingerprint(string $resourcePath, string $relativeDir = "./", string $fingerprintParam = 'x') {
		try {
			$time = filemtime(implode('/', [trim($relativeDir, '/'), trim($resourcePath, '/')]));
			if (strpos($resourcePath, '?') > 0) { //already has query param
				$resourcePath .= "&";
			} else {
				$resourcePath .= "?";
			}
			$resourcePath .= "$fingerprintParam=$time";
		} catch (\Throwable $th) { }
		return $resourcePath;
	}
}
?>