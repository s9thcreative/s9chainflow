<?php

namespace MyApp\ControlU;

class ControlU_test extends AbstControlU_Web{

	function beforeAction(){
		$redirect = $this->gv('redirect','');
		if ($redirect=="before"){
			return array('class'=>'redirect', 'url'=>'https://yupj.jp', 'direct'=>true);
		}
		$ret = parent::beforeAction();
		var_dump('test before action');
		return $ret;
	}

	function action_index(){
		$redirect = $this->gv('redirect','');
		if ($redirect=="action"){
			return array('class'=>'redirect', 'url'=>'https://yupj.jp', 'direct'=>true);
		}
		$error = $this->gv('error', '');
		if ($error == "exception"){
			throw new \Exception("call exception");
		}
		return null;
	}

	function action_mail(){
		try{
			$tmpldata = array(
				'to'=>'info@yupj.jp',
				'name'=>'test calling'
			);
			$res = $this->mailsend("test.mail", $tmpldata);
			if (!$res){
				throw new \Exception("mail error");
			}
		}
		catch(\Exception $e){
			throw $e;
		}
		return array('class'=>'redirect', 'url'=>'/test');
	}

	function action_form(){
		$mode = $this->param(0, "");
		if (!in_array($mode, array('',"send"))){
			throw new \Exception('param error');
		}
		$this->setdata['mode'] = "";
		if ($mode == ""){
		}
		else if ($mode == "send"){
			$checker = $this->checker('form');
			$data = $checker->input();
			$msg = $checker->check($data, function($key, $data){
				if ($key == "email"){
					$v = $data[$key];
					if (preg_match('/@yupj\.jp$/', $v)){
						return 'exists';
					}
				}
				return null;
			});
			if ($msg){
				$this->setdata['data'] = $data;
				$this->setdata['msg'] = $msg;
				$this->setdata['mode'] = '';
				return null;
			}
			return array('class'=>'redirect', 'url'=>'/test');
		}


	}
}
