<?php

namespace S9\DB;

class DB{
	var $con;
	var $config;
	function __construct($config){
		$config = array_merge(array(
			'host'=>ini_get("mysqli.default_host"),
			'user'=>ini_get("mysqli.default_user"),
			'password'=>ini_get("mysqli.default_pw"),
			'db'=>'',
			'port'=>ini_get("mysqli.default_port"),
			'socket'=>ini_get("mysqli.default_socket"),
			'charset'=>'utf8mb4',
			'collate'=>'utf8mb4_general_ci',
		), $config);
		$con = new \mysqli(
			$config['host'],
			$config['user'],
			$config['password'],
			$config['db'],
			$config['port'],
			$config['socket']
		);
		if (!$con){
			throw new \Exception(mysqli::$connect_error);
		}
		$res = $con->set_charset($config['charset']);
		if (!$res){
			throw new \Exception($con->error);
		}
		$this->con = $con;
		$this->config = $config;
	}

	function begin(){
		if (!$this->con) throw new \Exception('no connect');
		$this->query('begin');
	}
	function rollback(){
		if (!$this->con) throw new \Exception('no connect');
		$this->query('rollback');
	}
	function commit(){
		if (!$this->con) throw new \Exception('no connect');
		$this->query('commit');
	}


	function select($sql){
		if (!$this->con) throw new \Exception('no connect');
		$result = $this->con->query($sql);
		if ($result === false)  throw new \Exception('select error:'.$this->con->error);
		$list = array();
		while($data = $result->fetch_assoc()){
			$list[] = $data;
		}
		$result->close();
		return $list;
	}
	function query($sql){
		if (!$this->con) throw new \Exception('no connect');
		$result = $this->con->query($sql);
		if ($result === false)  throw new \Exception('select error:'.$this->con->error);
		return true;
	}
	function one($sql){
		if (!$this->con) throw new \Exception('no connect');
		$result = $this->con->query($sql);
		if ($result === false) throw new \Exception('select error:'.$this->con->error);
		$data = $result->fetch_array();
		if (!$data) return null;
		return $data[0];
	}
	function line($sql){
		if (!$this->con) throw new \Exception('no connect');
		$result = $this->con->query($sql);
		if ($result === false) throw new \Exception('select error:'.$this->con->error);
		$data = $result->fetch_assoc();
		if (!$data) return null;
		return $data;
	}

	function escape($v){
		if (!$this->con) throw new \Exception('no connect');
		return $this->con->escape_string($v);
	}

	function lastid(){
		if (!$this->con) throw new \Exception('no connect');
		return $this->one('select last_insert_id()');
	}

	function rows(){
		if (!$this->con) throw new \Exception('no connect');
		return $this->one('select found_rows()');
	}
}