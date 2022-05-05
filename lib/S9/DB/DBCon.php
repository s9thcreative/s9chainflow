<?php
namespace S9\DB;

use \S9\Cf;

class DBCon{

	static $connects = array();

	static function connect($target="default"){
		if (isset(self::$connects[$target])){
			return self::$connects[$target];
		}
		$config = Cf::g('db.'.$target);
		if (!$config) return null;
		$db = new DB($config);
		self::$connects[$target] = $db;
		return $db;
	}

	static function now($target="default"){
		$db = self::connect($target);
		return $db->one(SQLGen::selectNow($db));
	}

}