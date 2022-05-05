<?php
namespace MyApp\Acsr;
use S9\DB\DBCon;
use S9\DB\SQLGen;
use S9\Util;

class Acsr_user{

	function genPassword($password){
		return password_hash($password, PASSWORD_DEFAULT);
	}


	function insertUser($data){
		$dbh = DBCon::connect();
		$gen = SQLGen::insert('nkm_user');
		$insdata = array(
			'nickname'=>$data['nickname'],
			'uniqid'=>$data['uniqid'],
			'auth'=>0,
			'create_date'=>array('thru', 'now()'),
			'update_date'=>array('thru', 'now()'),
		);
		$gen->data($insdata);
		$dbh->query($gen->sql($dbh));
		$uid = $dbh->lastId();
		$gens = SQLGen::insert('nkm_user_secure');
		$inssdata = array(
			'id'=>$uid,
			'email'=>$data['email'],
			'password'=>$this->genPassword($data['password']),
			'create_date'=>array('thru', 'now()'),
			'update_date'=>array('thru', 'now()'),
		);
		$gens->data($inssdata);
		$dbh->query($gens->sql($dbh));
		return $uid;
	}


	function useridFromEmail($email){
		$dbh = DBCon::connect();
		$gen = SQLGen::select("nkm_user_secure");
		$gen->cols(array("id"));
		$gen->where(array('email'=>$email, 'deleted'=>0));
		return $dbh->one($gen->sql($dbh));
	}
	function getUser($val, $target="id"){
		if (!in_array($target, array('id','uniqid'))) throw \Exception();
		$dbh = DBCon::connect();
		$gen = SQLGen::select("nkm_user");
		$gen->cols(array("id","uniqid","nickname",array("date_format", "create_date"), array("date_format", "update_date")));
		$gen->wherekv($target, $val);
		$gen->where(array('deleted'=>0));
		return $dbh->line($gen->sql($dbh));
	}

	function login($uid){
		$lss = Util::gen();
		$dbh = DBCon::connect();
		$gen = SQLGen::select('nkm_user_loginsession');
		$gen->cols(array("id"));
		$gen->where(
			array(array('or', array('expire_date'=>array('<', array('thru','now()')), 'deleted'=>1)))
		);
		$gen->forupdate = true;
		$updid = $dbh->one($gen->sql($dbh));
		$upddata = array(
			'loginsession'=>$lss,
			'userid'=>$uid,
			'create_date'=>array('thru','now()'),
			'expire_date'=>array('adddate', array(array('thru','now()'),'7 day')),
			'deleted'=>0,
		);
		if (!$updid){
			$geni = SQLGen::insert('nkm_user_loginsession');
			$geni->data($upddata);
			$dbh->query($geni->sql($dbh));
		}
		else{
			$geni = SQLGen::update('nkm_user_loginsession');
			$geni->data($upddata);
			$geni->wherekv("id", $updid);
			$dbh->query($geni->sql($dbh));
		}
		return $lss;
	}

	function authLogin($tgval, $password, $tgtype="userid"){
		$dbh = DBCon::connect();
		$gen = SQLGen::select('nkm_user_secure');
		$gen->cols(array('id','password'));
		$gen->wherekv($tgtype, $tgval);
		$udata = $dbh->line($gen->sql($dbh));
		if (!$udata){
			return 0;
		}
		if (!password_verify($password, $udata['password'])){
			return 0;
		}
		return $udata['id'];
	}

	function checkLogin($lss, $expand=false){
		$dbh = DBCon::connect();
		$gen = SQLGen::select("nkm_user_loginsession");
		$gen->cols(array('id','userid'));
		$gen->where(array('expire_date'=>array('>', array('thru','now()')), 'deleted'=>0));
		if ($expand){
			$gen->forupdate = true;
		}
		$res = $dbh->line($gen->sql($dbh));
		if (!$res){
			return 0;
		}
		if ($expand){
			$genx = SQLGen::update("nkm_user_loginsession");
			$genx->data(array('expire_date'=>array('adddate', array(array('thru','now()'),'7 day'))));
			$genx->wherekv('id',$res['id']);
			$dbh->query($genx->sql($dbh));
		}
		return $res['userid'];
	}

	function changePassword($uid, $password){
		$dbh = DBCon::connect();
		$gen = SQLGen::update("nkm_user_secure");
		$gen->data(array('password'=>$this->genPassword($password)));
		$gen->wherekv("id", $uid);
		$dbh->query($gen->sql($dbh));
	}
}