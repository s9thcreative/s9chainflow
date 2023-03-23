<?php

namespace S9;


class ParamCheck{

	var $customlogic = array();
	var $inputcallback = null;
	var $msg = array();
	var $name = "";
	var $target = null;
	var $logic = null;
	var $messages = array();
	


	function input($default=""){
		if (!$this->inputcallback){
			throw new \Exception('input callback not set');
		}
		$data = array();
		foreach ($this->target as $k=>$v){
			$key = "";
			if (is_int($k)){
				$key = $v;
			}
			else{
				$key = $k;
			}
			$def = null;
			if (is_array($default)){
				if (array_key_exists($k, $default)){
					$def = $default[$k];
				}
			}
			$data[$key] = call_user_func_array($this->inputcallback, array($key, $def));
		}
		return $data;
	}


	function check($data, $addchecker=null){
		$msg = array();
		$target = array();
		foreach ($this->target as $k=>$v){
			$key = "";
			$logic = "";
			if (is_int($k)){
				$key = $v;
				$logic = $this->name.'.'.$key;
			}
			else{
				$key = $k;
				$logic = $v;
			}
			if (!array_key_exists($logic, $this->logic)){
				continue;
			}
			$baselogic = $this->logic[$logic];
			if (isset($baselogic['merge']) && isset($this->logic[$baselogic['merge']])){
				$baselogic = array_merge($this->logic[$baselogic['merge']], $baselogic);
			}
			$ardef = array(
				'label'=>'',
				'required'=>false,
				'min'=>0,
				'max'=>0,
				'logic'=>null,
				'logic_target'=>null
			);
			$logicar = array_merge($ardef, $baselogic);
			$target[$key] = $logicar;
		}
		foreach ($target as $key=>$logicar){
			$label = $logicar['label'];
			if ($logicar['required']){
				if (!isset($data[$key]) || strlen($data[$key]) == 0){
					$msg[$key] = $label.$this->genMessage("required");
					continue;
				}
			}
			else{
				if (!isset($data[$key]) || strlen($data[$key]) == 0){
					continue;
				}
			}
			if ($logicar['min'] > 0){
				if (mb_strlen($data[$key]) < $logicar['min']){
					$msg[$key] = $label.$this->genMessage("min", $logicar['min']);
					continue;
				}
			}
			if ($logicar['max'] > 0){
				if (mb_strlen($data[$key]) > $logicar['max']){
					$msg[$key] = $label.$this->genMessage("max", $logicar['max']);;
					continue;
				}
			}
			if ($logicar['logic']){
				$logicclass = $this;
				if ($logicar['logic_target']){
					if (isset($this->customlogic[$logicar['logic_target']])){
						$logicclass = $this->customlogic[$logicar['logic_target']];
					}
				}
				if (method_exists($logicclass, "checkLogic")){
					$msgv = call_user_func_array(array($logicclass, "checkLogic"), array($logicar['logic'], $key, $data));
					if ($msgv){
						$msg[$key] = $label.$this->genMessage($msgv);
						continue;
					}
				}
			}
			if ($addchecker){
				$msgv = call_user_func_array($addchecker, array($key, $data));
				if ($msgv){
					$msg[$key] = $label.$this->genMessage($msgv);
					continue;
				}
			}
		}
		return $msg;


	}

	function checkLogic($logic, $key, $data){
		$v = $data[$key];
		if ($logic == "email"){
			if (!preg_match('/^[-.\w]+@([-\w]+\.)+[-\w]+$/', $v)){
				return "type";
			}
		}
		else if ($logic == "datetime14"){
			if (!preg_match('/^\d{14}$/', $v)){
				return "datetime";
			}
			$arconf = array(array(4), array(2,1,12), array(2,1,31), array(2,0,23), array(2,0,59), array(2,0,59));
			$ar = array();
			$pos = 0;
			foreach ($arconf as $cf){
				$vv = substr($v, $pos, $cf[0]);
				$ar[] = $vv;
				$pos += $cf[0];
				if (count($cf) > 1){
					if (($vv < $cf[1]) || ($vv > $cf[2])){
						return "datetime";
					}
				}
			}
			if (!checkdate($ar[1], $ar[2], $ar[0])){
				return "datetime";
			}
		}
		return null;
	}

	function genMessage($err, $ex=null){
		if(!isset($this->messages[$err])){
			return "異常エラー";
		}
		$msg = $this->messages[$err];
		if (strpos($msg, "%d") !== false){
			$msg = sprintf($msg, $ex);
		}
		return $msg;
	}

}