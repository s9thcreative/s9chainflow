<?php

namespace MyApp\Library;


class ParamCheckLogicCustom{

	static $instance;

	static function getInstance(){
		if (self::$instance == null){
			self::$instance = new ParamCheckLogicCustom();
		}
		return self::$instance;
	}

	function checkLogic($logic, $key, $data){
		$v = $data[$key];
		if ($logic == "password"){
			if (!preg_match('/^[\x21-\x7e]+$/', $v)){
				return "password";
			}
		}
		else if ($logic == "password_conf"){
			$basekey = substr($key, 0, strlen($key)-5);
			$baseval = "";
			if (isset($data[$basekey])) $baseval = $data[$basekey];
			if ($baseval != $v){
				return "password_conf";
			}
		}
		return null;
	}

}