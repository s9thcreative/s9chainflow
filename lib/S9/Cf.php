<?php
namespace S9;

class Cf{
	static $configs = array();

	static function g($key, $def=null){
		if (array_key_exists($key, self::$configs)){
			return self::$configs[$key];
		}
		return $def;
	}

	static function s($key, $v){
		self::$configs[$key] = $v;
	}


	static function merge($key, $v){
		if (!isset(self::$configs[$key])){
			self::s($key, $v);
		}
		else{
			self::$configs[$key] = array_replace_recursive(self::$configs[$key], $v);
		}
	}

}