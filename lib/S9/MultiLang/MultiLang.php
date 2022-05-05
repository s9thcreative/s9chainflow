<?php
namespace S9\MultiLang;

class MultiLang{
	static  $root;
	static  $current = null;

	static  $cache;

	static function getCurrent(){
		if (!self::$current) return null;
		return self::object($self::$current);
	}
	static function object($lang){
		if (self::$cache && isset(self::$cache[$lang])){
			return self::$cache[$lang];
		}
		if (!self::$cache) self::$cache = array();
		self::$cache[$lang] = new MultiLang($lang);
		return self::$cache[$lang];
	}

	static function currentText($wd, $cate=""){
		if (!self::$current) return $wd;
		return self::$current->text($wd, $cate);
	}

	static function currentList($cate=""){
		if (!self::$current) return array();
		return self::$current->list($cate);
	}


	static function currentLoad($cate="main"){
		$ml = self::getCurrent();
		$ml->load($cate);
	}

	var $loaded = array();
	var $lang = "";
	
	function __construct($lang){
		$this->lang = $lang;
	}

	function load($cate="main"){
		if (!self::$root) return false;
		if (isset($this->loaded[$cate])) return true;
		$path = self::$root.'/'.$this->lang.'-'.$cate.'.txt';
		$setting = array();
		if (is_readable($path)){
			$flns = file($path);
			$cur = "";
			foreach ($flns as $fln){
				$fln = preg_replace('/[\r\n]/', '', $fln);
				if ($cur == ""){
					if ($fln == "") continue;
					$cur = $fln;
					continue;
				}
				else{
					$setting[$cur] = $fln;
					$cur = "";
				}
			}
		}
		$this->loaded[$cate] = $setting;
	}

	function text($wd, $cate=""){
		if ($cate != ""){
			$this->load($cate);
		}
		foreach ($this->loaded as $c=>$setting){
			if ($cate && $c != $cate) continue;
			if (isset($setting[$wd])) return $setting[$wd];
		}
		return $wd;
	}
	function list($cate=""){
		if ($cate){
			if (isset($this->loaded[$cate])){
				return $this->loaded[$cate];
			}
			return arry();
		}
		$ret = array();
		foreach ($this->loaded as $c=>$setting){
			$ret = array_merge($ret, $setting);
		}
		return $ret;
	}

}