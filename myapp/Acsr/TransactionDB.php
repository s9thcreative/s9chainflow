<?php

namespace MyApp\Acsr;


use \S9\DB\DBCon;

class TransactionDB{
	static $instance;
	static function getInstance(){
		if (!self::$instance){
			self::$instance = new self();
		}
		return self::$instance();
	}

	function begin(){
		$con = DBCon::connect();
		$con->begin();
	}
	function commit(){
		$con = DBCon::connect();
		$con->commit();
	}
	function rollback(){
		$con = DBCon::connect();
		$con->rollback();
	}
}