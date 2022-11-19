<?php

namespace S9\DB;

use \S9\Cf;

class SQLGen{

	var $type = null;
	var $cols = null;
	var $tables = array();
	var $where = array();
	var $orderby = array();
	var $data = array();
	var $limit = "";
	var $dateformat = "";
	var $forupdate = false;

	static function colDateFormat($col){
		return array('date_format', $col);
	}
	static function colThru($col){
		return array('thru', $col);
	}

	static function wKV($k, $v, $type="and"){
		return array($type, array($k=>$v));
	}
	static function wData($data, $type="and"){
		return array($type, $data);
	}

	static function select($table=null){
		$gen = new self("select");
		if ($table){
			$gen->addTable($table);
		}
		return $gen;
	}
	static function insert($table){
		$gen = new self("insert");
		$gen->addTable($table);
		return $gen;
	}
	static function update($table){
		$gen = new self("update");
		$gen->addTable($table);
		return $gen;
	}

	static function selectNow($db){
		return (new self("now"))->sql($db);
	}

	function __construct($type){
		$this->type = $type;
		$this->dateformat = Cf::g('db.date_format');
	}

	function addTable($table){
		if (is_array($table)){
			$this->tables += $sub;
		}
		else{
			$this->tables[] = $table;
		}
	}

	function cols($cols){
		$this->cols = $cols;
	}

	function orderby($orderby){
		$this->orderby[] = $orderby;
	}

	function wherekv($k, $v){
		$this->where[] = SQLGen::wKV($k, $v, "and");
	}

	function where($where){
		$this->where[] = SQLGen::wData($where, "and");
	}

	function data($data){
		$this->data = $data;
	}
	function addData($data){
		$this->data += $data;
	}
	function updateData($data){
		$this->data = array_merge($this->data, $data);
	}

	function sqlValue($db, $col, $deftype){
		$type = $deftype;
		if (is_array($col)){
			$type = $col[0];
			$col = $col[1];
		}
		if ($type == "date_format"){
			$col = $this->sqlValue($db, $col, $deftype);
			if (is_array($col)){
				$as = $col[1];
				$col = $col[0];
			}
			else{
				$as = $col;
			}
			return "date_format(".$col.", '".$this->dateformat."') as ".$db->escape($as);
		}
		if ($type == "adddate"){
			$interval = preg_replace('/[^-\w ]/', ' ', $col[1]);
			$col = $this->sqlValue($db, $col[0], $deftype);
			return "adddate(".$col.", interval ".$interval.")";
		}
		if ($type == "thru"){
			return $col;
		}
		if ($type == "col"){
			$cols = preg_split('/\s+/', $col, 2);
			$ca = "";
			if (count($cols) == 2){
				$col = $cols[0];
				$ca = $cols[1];
			}
			$ret =  '`'.$db->escape($col).'`';
			if ($ca !== ""){
				$ret .= " as `".$db->escape($ca)."`";
			}
			return $ret;
		}
		if ($col === null) return "null";
		return "'".$db->escape($col)."'";
	}

	function sqlWhere($db, $where, $type="and"){
		$wsql = "";
		if ($where){
			$first = true;
			foreach ($where as $k=>$wdt){
				if ($first) $first = false;
				else{
					$wsql .= " ".$type." ";
				}
				if (is_int($k)){
					$wsql .= "(".$this->sqlWhere($db, $wdt[1], $wdt[0]).")";
				}
				else{
					$wsql .= $this->sqlWhereValue($db, $k, $wdt);
				}
			}
		}
		return $wsql;
	}
	function sqlWhereValue($db, $k, $v){
		if (!is_array($v)){
			return $this->sqlValue($db, $k, "col")."=".$this->sqlValue($db, $v, "value");
		}
		else{
			return $this->sqlValue($db, $k, "col").$v[0].$this->sqlValue($db, $v[1], "value");
		}
		return "";
	}
	function sqlTable($db, $tbl){
		$tbls = preg_split('/\s+/', $tbl, 2);
		$ta = "";
		if (count($tbls) == 2){
			$tbl = $tbls[0];
			$ta = ' as `'.$db->escape($tbls[1]).'`';
		}
		return '`'.$db->escape($tbl).'`'.$ta;
	}

	function sql($db){
		if ($this->type == "now"){
			return "select date_format(now(), '".$this->dateformat."') as `now`";
		}
		if ($this->type == "select"){
			$sql = "select ";
			$first = true;
			if ($this->cols){
				foreach ($this->cols as $col){
					if ($first) $first = false;
					else{
						$sql .= ",";
					}
					$sql .= $this->sqlValue($db, $col, "col");
				}
			}
			else{
				$sql .= "*";
			}
			if ($this->tables){
				$sql .= " from ";
				$first = true;
				foreach ($this->tables as $tbl){
					if ($first) $first = false;
					else{
						$sql .= ",";
					}
					$sql .= $this->sqlTable($db, $tbl);
				}
			}
			$wsql = $this->sqlWhere($db, $this->where);
			if ($wsql) $sql .= " where ".$wsql;
			if ($this->orderby){
				$osql = "";
				foreach ($this->orderby as $odsql){
					if ($osql) $osql .= ",";
					$osql .= $odsql;
				}
				$sql .= " order by ".$osql;
			}
			if ($this->limit){
				$sql .= ' limit '.$this->limit;
			}
			if ($this->forupdate){
				$sql .= " for update";
			}
			return $sql;

		}
		else if ($this->type == "insert"){
			if (!$this->tables) throw new \Exception("no table is setting.");
			if (!$this->data) throw new \Exception("no data is setting.");
			$sql = "insert into ".$this->sqlTable($db, $this->tables[0]);
			$cols = "";
			$vals = "";
			$first = true;
			foreach ($this->data as $k=>$v){
				if ($first) $first = false;
				else{
					$cols.=",";
					$vals.=",";
				}
				$cols.= $this->sqlValue($db, $k, "col");
				$vals.= $this->sqlValue($db, $v, "value");
			}
			$sql .= "(".$cols.")values(".$vals.")";
			return $sql;
		}
		else if ($this->type == "update"){
			if (!$this->tables) throw new \Exception("no table is setting.");
			if (!$this->data) throw new \Exception("no data is setting.");
			$sql = "update ".$this->sqlTable($db, $this->tables[0])." set ";
			$first = true;
			foreach ($this->data as $k=>$v){
				if ($first) $first = false;
				else{
					$sql.=",";
				}
				$sql.= $this->sqlValue($db, $k, "col")."=".$this->sqlValue($db, $v, "value");
			}
			$wsql = $this->sqlWhere($db, $this->where);
			if ($wsql) $sql .= " where ".$wsql;
			return $sql;
		}
	}



}