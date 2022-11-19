<?php

namespace MyApp\ControlU;

use \MyApp\Acsr\Acsr_user;
use \MyApp\Acsr\TransactionDB;
use \S9\Cf;
use \S9ChainU\MailObs;
use \S9\ParamCheck;
use \MyApp\Library\ParamCheckLogicCustom;

class AbstControlU_Web extends \S9ChainU\AbstWebControlU{

	var $acu;
	var $trans;

	function begin(){
		$this->trans->begin();
	}
	function rollback(){
		$this->trans->rollback();
	}
	function commit(){
		$this->trans->commit();
	}

	function beforeAction(){
		$this->trans = new TransactionDB();
		return null;
	}

	function viewCommonData(){
		$ret = parent::viewCommonData();
		$ret['_view_root'] = APP_ROOT.'/views';
		return $ret;
	}
	function mailsend($template, $data){
		$mailsetting = Cf::g('myapp.mailsetting');
		$mailsetting['default_common']['_base_root_full'] = $this->obs->fullWebRootPath();
		$mobs = new MailObs(
			$mailsetting,
			array('template'=>$template, 'data'=>$data)
		);
		$mobs->start(function($res){});
		return $mobs->issuccess;

	}


	function checker($target){
		$checker = new ParamCheck();
		$targetcf = Cf::g('paramcheck.target');
		if (!isset($targetcf[$target])){
			throw new \Exception('check target error');
		}
		$checker->target = $targetcf[$target];
		$checker->name = $target;
		$checker->messages = Cf::g('paramcheck.messages');
		$checker->logic = Cf::g('paramcheck.logic');
		$checker->customlogic['custom'] = ParamCheckLogicCustom::getInstance();
		$checker->inputcallback = function($v, $def){return $this->pv($v, $def);};
		return $checker;
	}


}