<?php



namespace S9;


class Util{

	static function gen($n=16){
		$str = "";
		for($i = 0; $i < $n; ++$i){
			$r = rand(0, 61);
			$c = "";
			if ($r < 10) $c = chr(0x30+$r);
			else if ($r < 36) $c = chr(0x41+$r-10);
			else $c = chr(0x61+$r-36);
			$str .= $c;
		}
		return $str;

	}

	static function ext($mimetype){
		if ($mimetype == "image/png") return ".png";
		if ($mimetype == "image/jpeg") return ".jpg";
		if ($mimetype == "image/gif") return ".gif";
		return "";
	}

	static function detectImageType($file){
		if (!is_readable($file)) return -1;
		$fp = fopen($file, 'r');
		$src = "   ";
		$ret = -1;
		$c = fgetc($fp);
		if ($c == "\xff") {
			$ret = IMAGETYPE_JPEG;
			$rem = "\xd8";
		}
		else if ($c == "G"){
			$ret = IMAGETYPE_GIF;
			$rem = "IF";
		}
		else if ($c == "\x89"){
			$ret = IMAGETYPE_PNG;
			$rem = "PNG";
		}
		if ($ret != -1){
			for($i = 0; $i < strlen($rem); $i++){
				$c = fgetc($fp);
				if ($c === false){
					$ret = -1;
					break;
				}
				if ($rem[$i] != $c){
					$ret = -1;
					break;
				}
			}
		}
		fclose($fp);
		return $ret;
	}

}