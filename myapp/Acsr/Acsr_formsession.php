<?php
namespace MyApp\Acsr;

use S9\DB\DBCon;
use S9\DB\SQLGen;
use S9\Util;

class Acsr_formsession{

	function saveSession($target, $ss, $data){
		$updid = null;
		if ($ss){
			$updid = $this->getSession($target, $ss);
		}
		if ($updid){
			$this->updateSession($updid, $data);
		}
		else{
			$ss = $this->startSession($target, $data);
		}
		return $ss;
	}

	function startSession($target, $data=null){
		$ss = Util::gen();

		$dbh = DBCon::connect();
		$gen = SQLGen::select("nkm_formsession");
		$gen->cols(array("id"));
		$gen->where(
			array(SQLGen::wData(array("deleted"=>1, "expire_date"=>array("<", array("thru", "now()"))), "or"))
		);
		$sql = $gen->sql($dbh);
		$updid = $dbh->one($sql);
		$upddata = array(
			'session'=>$ss,
			'target'=>$target,
			'create_date'=>array('thru','now()'),
			'expire_date'=>array('adddate', array(array('thru', 'now()'), '3600 second')),
			'deleted'=>0,
			'data'=>json_encode($data)
		);
		if ($updid){
			$ugen = SQLGen::update('nkm_formsession');
			$ugen->data($upddata);
			$ugen->wherekv("id", $updid);
			$dbh->query($ugen->sql($dbh));
		}
		else{
			$ugen = SQLGen::insert('nkm_formsession');
			$ugen->data($upddata);
			$dbh->query($ugen->sql($dbh));
		}
		return $ss;
	}

	function updateSession($id, $data=null){
		$upddata = array(
			'expire_date'=>array('adddate', array(array('thru', 'now()'), '3600 second')),
			'data'=>json_encode($data)
		);
		$dbh = DBCon::connect();
		$gen = SQLGen::update("nkm_formsession");
		$gen->data($upddata);
		$gen->wherekv("id", $id);
		$dbh->query($gen->sql($dbh));
	}

	function getSession($target, $ss, $col="id"){
		$dbh = DBCon::connect();
		$gen = SQLGen::select('nkm_formsession');
		$gen->cols(array($col));
		$gen->where(array('session'=>$ss, 'target'=>$target, 'deleted'=>array("!=", 1), 'expire_date'=>array('>', array('thru', 'now()'))));
		$res = $dbh->one($gen->sql($dbh));
		if ($col == "data"){
			return json_decode($res, true);
		}
		return $res;
	}


	function deleteSession($target, $ss){
		$id = $this->getSession($target, $ss);
		if (!$id) return;
		$dbh = DBCon::connect();
		$gen = SQLGen::update('nkm_formsession');
		$gen->data(array('deleted'=>1));
		$gen->wherekv('id', $id);
		$dbh->query($gen->sql($dbh));
	}
}