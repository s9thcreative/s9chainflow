<?php

namespace MyApp\ControlU;

class ControlU_page extends AbstControlU_Web{
	function getSetting(){
		$root = $this->obs->webSetting;
		$grp = $this->obs->data['siteusage']['group'];
		if (isset($root['group']) && isset($root['group'][$grp])){
			$root = $root['group'][$grp];
		}
		return $root;
	}

	function action_show(){
		$params = $this->setting['flow']['params'];
		$pathparams = array();
		$res = $this->pathConvert($params);
		if ($res){
			$params = $res['path'];
			$pathparams = $res['params'];
		}
		$pagepath = join('/', $params);
		$pagepath = preg_replace('/\/+$/', '', $pagepath);
		$this->appendData($pagepath, $pathparams);
		$path = '/page/'.$pagepath;
		return $path;
	}
	function pathConvert($params){
		if (count($params) == 0) return null;
		if ($params[0] == 's9thmusic'){
			if (count($params) == 1) return null;
			if ($params[1] == "musics"){
				return array('path'=>array_splice($params, 0, 2), 'params'=>$params);
			}
		}
		return null;
	}
	function appendData($pagepath, $params){
		return array();
	}
}
