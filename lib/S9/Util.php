<?php



namespace S9;


class Util{

	static function gen($n=16){
		$str = "";
		for($i = 0; $i < $n; ++$i){
			$r = rand(0, 62);
			$c = "";
			if ($r < 10) $c = chr(0x30+$r);
			else if ($r < 36) $c = chr(0x41+$r-10);
			else $c = chr(0x61+$r-36);
			$str .= $c;
		}
		return $str;

	}

}