<?php
/**
 * s9-chain-flow
 */
namespace S9ChainU;

class Obs{
	var $callback = null;
	var $data = array();
	function start($callback){
		$this->callback = $callback;
		$u = $this->genU(null, $this->firstSetting());
		while(true){
			if (!$u) break;
			try{
				$setting = $u->process();
			}
			catch(\Exception $e){
				$setting = array('class'=>'error', 'exception'=>$e, 'setting'=>array('data'=>array('errmsg'=>$e->getMessage())));
			}
			$nu = $this->genU($u, $setting);
			if ($nu){
				$u->connect($nu);
			}
			$u = $nu;
		}
		$this->result();
	}

	function result(){
		if ($this->callback){
			($this->callback)($this);
		}
	}

	function genU($u, $setting){
		$nu = $this->genUDo($u, $setting);
		if ($nu){
			$nu->obs = $this;
			$nu->setting = $setting;
		}
		return $nu;
	}

	function firstSetting(){
		return null;
	}
	function genUDo($u, $setting){
		return null;
	}
}

class U{
	var $obs = null;
	var $setting = null;
	function process(){
	}
	function connect($u){
	}
}

class WebU extends U{
	function valueAs($type, $v, $def){
		if ($type == "int"){
			if (preg_match('/\D/', $v)) return $def;
			return (int)($v);
		}
		return $v;
	}
	function param($idx, $def=null){
		if (isset($this->setting['flow']['params'])){
			$params = $this->setting['flow']['params'];
			if ($idx < count($params)) return $params[$idx];
		}
		return $def;
	}
	function paramAs($idx, $type, $def=null){
		$v = $this->param($idx, $def);
		return $this->valueAs($type, $v, $def);
	}
	function gv($name, $def=null){
		$get = $_GET;
		if (isset($this->setting['flow']['get'])){
			$get = $this->setting['flow']['get'];
		}
		if (!isset($get[$name])) return $def;
		return $get[$name];
	}
	function pv($name, $def=null){
		$post = $_POST;
		if (isset($this->setting['flow']['post'])){
			$post = $this->setting['flow']['post'];
		}
		if (!isset($post[$name])) return $def;
		return $post[$name];
	}
	function cv($name, $def=null){
		$root = $this->obs->groupSetting();
		if (!isset($root['cookie_setting']) || !isset($root['cookie_setting'][$name])){
			return $def;
		}
		$cf = $root['cookie_setting'][$name];
		if (isset($cf['name'])){
			$name = $cf['name'];
		}
		return $this->cookiev($name, $def);
	}
	function cookiev($name, $def=null){
		if (!isset($this->obs->data['cookie']['data'])){
			return $def;
		}
		$cookie = $this->obs->data['cookie']['data'];
		if (!isset($cookie[$name])) return $def;
		return $cookie[$name];
	}
	function setcookie($nm, $val){
		$root = $this->obs->groupSetting();
		if (!isset($root['cookie_setting'][$nm])){
			throw new \Exception('cookie setting error');
		}
		$ckcf = $root['cookie_setting'][$nm];
		$cknm = $nm;
		if (isset($ckcf['name'])) $cknm = $ckcf['name'];
		$expiretm = 0;
		if (isset($ckcf['expire'])){
			if ($val === null || $val == ""){
				$expiretm = time()-1;
			}
			else{
				$expiretm = time()+$ckcf['expire'];
			}
		}

		$siteusage = $this->obs->data['siteusage'];
		$path = WEB_ROOT_PATH.$siteusage['basepath'];
		$domain = $this->obs->webSetting['domain'];
		$issecure = $this->obs->webSetting['issecure'];
		if ($path == "") $path = "/";
		setcookie($cknm, $val, $expiretm, $path, $domain, $issecure);
	}

	function nocacheHeader(){
		header('Cache-Control: no-store');
	}
}


class ObsWeb extends Obs{
	var $webSetting = null;
	var $webrootpath = "";
	var $webrootpathfull = "";
	var $webhost = "";
	function fullWebHost(){
		if ($this->webhost) return $this->webhost;
		$http = "http";
		if (getenv('HTTPS')) $http .= "s";
		$url = $http."://";
		$host = null;
		if ($this->webSetting && isset($this->webSetting['site']) && isset($this->webSetting['site']['host'])){
			$host = $this->webSetting['site']['host'];
		}
		if (!$host){
			$host = getenv('HTTP_HOST');
		}
		$url .= $host;
		$this->webhost = $url;
		return $url;
	}
	function fullWebRootPath(){
		if ($this->webrootpathfull) return $this->webrootpathfull;
		$url = $this->fullWebHost();
		$url .= $this->webrootpath;
		$this->webrootpathfull = $url;
		return $url;
	}
	function fullWebCurrent($path, $addition=array()){
		if (!$addition){
			if ($path == "" || $path == "/"){
				return $this->fullWebHost()."/";
			}
		}
		if (!$path) $path = "/";
		if ($path[0] != "/") $path = "/".$path;
		$addpath = "";
		foreach ($addition as $addtarget){
			if (isset($this->data['siteusage'][$addtarget])){
				$addpath .= "/".$this->data['siteusage'][$addtarget];
			}
		}
		$url = $this->fullWebHost().$this->webrootpath.$addpath.$path;
		return $url;
	}
	function isAllowLang($lang){
		if (isset($this->webSetting['siteusage']['lang'])){
			$tgar = $this->webSetting['siteusage']['lang'];
		}
		else{
			return false;
		}
		foreach ($tgar as $tgk=>$tgv){
			if (is_int($tgk)){
				$key = $tgv;
			}
			else{
				$key = $tgk;
			}
			if ($key[0] == "!") $key = substr($key, 1);
			if ($lang == $key){
				return true;
			}
		}
		return false;
	}

	function groupSetting(){
		$group = $this->data['siteusage']['group'];
		$root = $this->webSetting;
		if (isset($this->webSetting['group']) && isset($this->webSetting['group'][$group])){
			$root = $this->webSetting['group'][$group];
		}
		return $root;
	}

	function firstSetting(){
		return array('class'=>'first');
	}
	function genUDo($u, $setting){
		if (!$setting) return null;
		if ($setting['class'] == 'first'){
			return new WebFirstU();
		}
		else if ($setting['class'] == 'auth'){
			return $this->genAuthU($setting);
		}
		else if ($setting['class'] == 'control'){
			return $this->genControlU($setting);
		}
		else if ($setting['class'] == 'view'){
			return new WebViewU();
		}
		else if ($setting['class'] == 'output'){
			return new WebOutputU();
		}
		else if ($setting['class'] == 'redirect'){
			return new WebRedirectU();
		}
		else if ($setting['class'] == 'error'){
			return new WebErrorU();
		}
	}

	function genAuthU($setting){
		$authlist = array();
		$group = $this->data['siteusage']['group'];
		$root = $this->webSetting;
		if (isset($this->webSetting['group']) && isset($this->webSetting['group'][$group])){
			$root = $this->webSetting['group'][$group];
		}
		if (isset($root['auth'])){
			$authlist = $root['auth'];
		}
		$authindex = 0;
		if (isset($setting['authindex'])){
			$authindex = (int)$setting['authindex'];
		}
		if ($authindex >= count($authlist)){
			return new WebLastAuthU();
		}
		$ns = "MyApp\\";
		if ($this->webSetting && isset($this->webSetting['app_namespace'])){
			$ns = $this->webSetting['app_namespace'];
		}
		$gpath = "";
		if ($group != "default"){
			$gpath = $group."\\";
		}
		$authsetting = $authlist[$authindex];
		$authclass = $ns."AuthU\\".$gpath."AuthU_".$authsetting['name'];
		if (!class_exists($authclass)){
			return null;
		}
		$ao = new $authclass();
		$ao->authindex = $authindex;
		$ao->authsetting = $authsetting;
		return $ao;
	}
	function controlClassName($control){
		$ns = "MyApp\\";
		if ($this->webSetting && isset($this->webSetting['app_namespace'])){
			$ns = $this->webSetting['app_namespace'];
		}
		$group = $this->data['siteusage']['group'];
		$gpath = "";
		if ($group != "default"){
			$gpath = $group."\\";
		}
		return $ns."ControlU\\".$gpath."ControlU_".$control;
	}
	function genControlU($setting){
		$classname = $this->controlClassName($setting['flow']['control']);
		if (!class_exists($classname)){
			return null;
		}
		return new $classname();
	}
}

class WebFirstU extends WebU{
	function process(){
		if (isset($this->obs->webSetting['issecure']) && $this->obs->webSetting['issecure']){
			if (!$_SERVER['HTTPS']){
				$url = "https://".getenv('HTTP_HOST').getenv('REQUEST_URI');
				return array('class'=>'redirect', 'url'=>$url, 'direct');
			}
		}

		$webrootpath = $this->obs->webrootpath;
		$webrootpathlen = strlen($webrootpath);
		$this->obs->data['webrootpath'] = $webrootpath;
		$path = getenv('REQUEST_URI');
		$qpos = strpos($path, "?");
		if ($qpos !== false){
			$path = substr($path, 0, $qpos);
		}
		if (preg_match('/^\/*$/', $path)){
			$rpath = "/";
		}
		else{
			$hpath = substr($path, 0, $webrootpathlen);
			if ($hpath != $webrootpath){
				return array('class'=>'error');
			}
			$rpath = substr($path, $webrootpathlen);
		}
		if (strlen($rpath) == 0 || $rpath[0] != "/"){
			$rpath = "/".$rpath;
		}
		$ar = explode('/', $rpath);
		array_shift($ar);
		$nar = array();
		foreach ($ar as $arv){
			if ($arv != "") $nar[] = $arv;
		}
		$currentpath = join('/', $ar);
		$ar = $nar;
		$siteusage = array('lang'=>null, 'lang_alias'=>'', 'group'=>'default', 'group_alias'=>'', 'currentpath'=>$currentpath);
		$flow = array(
			'control'=>'Top',
			'action'=>'index',
			'params'=>array()
		);
		$gpath = "";
		if (isset($this->obs->webSetting['siteusage'])){
			$usageorder = null;
			if (isset($this->obs->webSetting['siteusage']['order'])){
				$usageorder  = $this->obs->webSetting['siteusage']['order'];
			}
			if (!$usageorder) $usageorder = array('group','lang');
			if (!is_array($usageorder)) $usageorder = array($usageorder);
			foreach ($usageorder as $tg){
				$v = null;
				$tgar = null;
				if (isset($this->obs->webSetting['siteusage'][$tg])){
					$tgar = $this->obs->webSetting['siteusage'][$tg];
				}
				if (count($ar) > 0){
					if ($tgar){
						foreach ($tgar as $tgv){
							$chv = $tgv;
							if (is_array($chv)){
								$chv = $chv['alias'];
							}
							$notaddpath = false;
							if ($chv[0] == "!"){
								$notaddpath = true;
								$chv = substr($chv, 1);
							}
							if ($chv == $ar[0]){
								$v = $tgv;
								$path = array_shift($ar);
								if (!$notaddpath){
									$gpath .= "/".$path;
								}
								break;
							}
						}
					}
				}
				$usedefault = false;
				if (!$v){
					if ($tgar){
						$v = $tgar[0];
						$usedefault = true;
					}
				}
				if (is_array($v)){
					$va = $v['alias'];
					$v = $v['target'];
				}
				else{
					if ($v[0] == "!") $v = substr($v, 1);
					$va = $v;
				}
				$siteusage[$tg] = $v;
				$siteusage[$tg.'_alias'] = $va;
				$siteusage[$tg.'_usedefault'] = $usedefault;
			}
		}

		$siteusage['basepath'] = $gpath;

		$root = $this->obs->webSetting;
		if ($this->obs->webSetting['group'] && $this->obs->webSetting['group'][$siteusage['group']]){
			$root = $this->obs->webSetting['group'][$siteusage['group']];
		}
		if (isset($root['toppagepath'])){
			$siteusage['toppagepath'] = $root['toppagepath'];
		}
		else{
			$siteusage['toppagepath'] = $webrootpath.$gpath."/";
		}

		if (array_key_exists('lang_usedefault', $siteusage) && $siteusage['lang_usedefault']){
			$setlang = null;
			if (isset($root['lang_cookie_name'])){
				if (isset($_COOKIE[$root['lang_cookie_name']])){
					$setlang = $_COOKIE[$root['lang_cookie_name']];
				}
			}
			if (!$setlang){
				$lang = getenv('HTTP_ACCEPT_LANGUAGE');
				if ($lang){
					$headerlang = substr($lang, 0, 2);
					if (isset($this->obs->webSetting['siteusage']['lang'])){
						$tgar = $this->obs->webSetting['siteusage']['lang'];
					}
					foreach ($tgar as $tgk=>$tgv){
						if (is_int($tgk)){
							$key = $tgv;
						}
						else{
							$key = $tgk;
						}
						if ($key[0] == "!") $key = substr($key, 1);
						if ($headerlang == $key){
							$setlang = $headerlang;
							break;
						}
					}
				}
				if (!$setlang){
					if (isset($this->obs->webSetting['default_lang'])){
						$setlang = $this->obs->webSetting['default_lang'];
					}
					else{
						$setlang = 'ja';
					}
				}
			}
			if ($setlang){
				$siteusage['lang'] = $setlang;
			}
		}

		if (count($ar) == 0 || (count($ar) == 1 && $ar[0] == "")){
			if (isset($root['default_path'])){
				$ar = $root['default_path'];
				if (!is_array($ar)){
					if ($ar[0] == "/") $ar = substr($ar, 1);
					$ar = explode("/", $ar);
				}
			}
		}

		$siteusage['activepath'] = join('/', $ar);

		if (isset($root['page'])){
			$forpage = false;
			$v = "";
			if (count($ar) > 0) $v = $ar[0];
			if(isset($root['page']['callback'])){
				$forpage = $root['page']['callback']($v);
			}
			else if (isset($root['page']['controls'])){
				$pagecontrols = $root['page']['controls'];
				if (!is_array($pagecontrols)){
					$pagecontrols = explode(",", $pagecontrols);
				}
				$forpage = !in_array($v, $pagecontrols);
			}
			if ($forpage){
				if (isset($root['page']['to'])){
					$toar = $root['page']['to'];
					if (!is_array($toar)){
						if ($toar[0] == "/") $toar = substr($toar, 1);
						$toar = explode("/", $toar);
					}
					$ar = array_merge($toar, $ar);
				}
			}
		}
		if (count($ar) > 0){
			$v = array_shift($ar);
			if ($v != ""){
				$flow['control'] = $v;
			}
		}
		if (count($ar) > 0){
			$v = array_shift($ar);
			if ($v != ""){
				$flow['action'] = $v;
			}
		}
		if (count($ar) > 0){
			$flow['params'] = $ar;
		}
		$this->obs->data['firstflow'] = $flow;
		$this->obs->data['siteusage'] = $siteusage;
		if (isset($this->obs->webSetting['ml'])){
			$loadlibs = null;
			if (isset($this->obs->webSetting['ml']['loadlibs'])){
				$loadlibs = $this->obs->webSetting['ml']['loadlibs'];
			}
			if (!$loadlibs) $loadlibs = array('main');
			$obj = \S9\MultiLang\MultiLang::object($siteusage['lang']);
			foreach ($loadlibs as $lib){
				$obj->load($lib);
			}
		}

		$cookie = $_COOKIE;
		$cookieagree = true;
		if (isset($root['agree_cookie_key']) && $root['agree_cookie_key']){
			if (!isset($cookie[$root['agree_cookie_key']]) || $cookie[$root['agree_cookie_key']] != 1){
				$cookie = array();
				$cookieagree = false;
			}
		}
		$this->obs->data['cookie'] = array('data'=>$cookie, 'agree'=>$cookieagree);
	
		return array('class'=>'auth');
	}
}

class AbstWebAuthU extends WebU{
	var $authindex = 0;
	var $authsetting = null;
	function process(){
		$res = $this->processDo();
		if ($res){
			if (!isset($this->obs->data['auth'])){
				$this->obs->data['auth'] = array();
			}
			$this->obs->data['auth'][$this->authsetting['name']] = $res;
		}
		return array('class'=>'auth', 'authindex'=>$this->authindex+1);
	}
	function processDo(){
		return null;
	}
}
class WebLastAuthU extends AbstWebAuthU{
	function process(){
		return array('class'=>'control', 'flow'=>$this->obs->data['firstflow']);
	}
}

class AbstWebControlU extends WebU{
	var $setdata = array();
	var $frame = "main";
	function process(){
		$res = $this->beforeAction();
		if ($res){
			return $res;
		}
		$action = $this->setting['flow']['action'];
		$method = "action_".$action;
		$response = null;
		if (method_exists($this, $method)){
			$response = call_user_func(array($this, $method));
		}
		if (!$response || is_string($response)){
			if (is_string($response)){
				$viewpath = $response;
			}
			else{
				$viewpath = $this->setting['flow']['control'].'/'.$this->setting['flow']['action'];
			}
			$response = array(
				'class'=>'view',
				'path'=>$viewpath,
				'data'=>$this->setdata,
				'frame'=>$this->frame,
			);
		}
		if ($response['class'] == "view"){
			$response['data']['_common'] = $this->viewCommonData();
		}
		return $response;
	}
	function beforeAction(){
		return null;
	}
	function viewCommonData(){
		$ret = array();
		$ret['_basecurrentpath'] = $this->obs->data['siteusage']['currentpath'];
		$ret['_basecurrentfull'] = $this->obs->fullWebCurrent($this->obs->data['siteusage']['currentpath']);
		$ret['_basecurrentfull_lang'] = $this->obs->fullWebCurrent($this->obs->data['siteusage']['currentpath'], array('lang'));
		$ret['_baseactivefull'] = $this->obs->fullWebCurrent($this->obs->data['siteusage']['activepath']);
		$ret['_baseactivefull_lang'] = $this->obs->fullWebCurrent($this->obs->data['siteusage']['activepath'], array('lang'));
		$ret['_basetop'] = $this->obs->fullWebHost().$this->obs->data['siteusage']['toppagepath'];
		$ret['_baseroot'] = $this->obs->webrootpath.'/';
		$ret['_basefull'] = $this->obs->fullWebRootPath().'/';
		$ret['_basefull_lang'] = $this->obs->fullWebRootPath().'/'.$this->obs->data['siteusage']['lang'];
		$ret['_lang'] = $this->obs->data['siteusage']['lang'];
		$ret['_lang_usedefault'] = isset($this->obs->data['siteusage']['lang_usedefault']) && $this->obs->data['siteusage']['lang_usedefault'];
		return $ret;
	}
}

class WebViewU extends WebU{
	function ml_convert($format, $vals, $attrs){
		var_dump($format, $vals, $attrs);
		exit;
	}
	function process(){
		$viewpath = $this->getTemplatePath();
		if (!$viewpath || !is_readable($viewpath)){
			return array('class'=>'error', 'nochain'=>true);
		}
		$opt = array(
			'ml_lang'=>$this->obs->data['siteusage']['lang'],
			'env'=>array('VIEW_ROOT'=>$this->getTemplateRoot()),
			'is_texted'=>true,
		);
		if ($this->obs->webSetting && isset($this->obs->webSetting['ml'])){
			if (isset($this->obs->webSetting['ml']['opt'])){
				$opt = array_merge($opt, $this->obs->webSetting['ml']['opt']);
			}
			if (!isset($opt['ml_convert'])){
				$opt['ml_convert'] = function($format, $vals, $attr, $lang, $thru=false){
					if (isset($attr['lang'])) $lang = $attr['lang']; 
					if ($lang){
						$obj = \S9\MultiLang\MultiLang::object($lang);
						$format = $obj->text($format);
					}
					if ($thru) return $format;
					return vsprintf($format, $vals);
				};
			}
		}
		if ($this->obs->webSetting && isset($this->obs->webSetting['mytmpl']) && isset($this->obs->webSetting['mytmpl']['namespaces'])){
			$opt['namespaces'] = $this->obs->webSetting['mytmpl']['namespaces'];
		}

		if ($this->obs->webSetting && isset($this->obs->webSetting['ml']) && isset($this->obs->webSetting['ml']['opt'])){
		}
		$tmpl = new \S9MyTmpl\S9MyTmpl($viewpath, "file", $opt);
		$tmpldata = $this->getTemplateData();
		$output = $tmpl->output($tmpldata);
		$senddata = $tmpl->applySendFrame($tmpldata);
		if (isset($this->setting['frame']) && $this->setting['frame']){
			$path = 'frames/'.$this->setting['frame'];
			$tmpldata = array_merge($tmpldata, array(
				'_output'=>$output,
				'_sendframe'=>$senddata,
			));
			return array(
				'class'=>'view',
				'path'=>$path,
				'data'=>$tmpldata
			);
		}
		else{
			return array('class'=>'output', 'output'=>$output, 'path_convert'=>true); 
		}
	}
	function getTemplateRoot(){
		$viewroot = APP_ROOT.'/views';
		$group = $this->obs->data['siteusage']['group'];
		return $viewroot."/".$group;
	}
	function getTemplatePath(){
		if (!isset($this->setting['path'])) return null;
		$viewroot = $this->getTemplateRoot();
		$viewpath = $viewroot.'/'.$this->setting['path'].'.tpl';
		return $viewpath;
	}

	function getTemplateData(){
		if (!isset($this->setting['data'])) return array();
		return  $this->setting['data'];
	}

}

class WebOutputU extends WebU{
	function process(){
		$scode = 200;
		if (isset($this->setting['statuscode'])){
			$scode = $this->setting['statuscode'];
		}
		http_response_code($scode);
		$type = "text/html";
		if (isset($this->setting['type'])){
			$type = $this->setting['type'];
		}
		header('Content-Type:'.$type);
		$this->nocacheHeader();

		if (preg_match('/\/json$/i', $type)){
			$jsondata = array();
			if (isset($this->setting['json_data'])){
				$jsondata = $this->setting['json_data'];
			}
			print json_encode($jsondata);
		}
		else if (preg_match('/image\/$/i', $type)){
			print $this->setting['output'];
		}
		else{
			$output = $this->setting['output'];
			if (isset($this->setting['path_convert']) && $this->setting['path_convert']){
				$output = $this->pathConvertDo($output);
			}
	
			print $output;
		}
		return null;
	}

	var $pathConvertSetting = array(
		'a'=>'href',
		'form'=>'action',
		'input'=>'formaction',
		'img'=>'src',
		'video'=>'src',
		'track'=>'src',
		'audio'=>'src',
		'link'=>'href',
		'script'=>'src',
	);

	function pathConvertDo($output){
		$noutput = "";
		$pos = 0;
		$npos = 0;
		$inscript = null;
		while($pos < strlen($output)){
			if ($output[$pos] == "<"){
				if ($inscript){
					if ($pos + strlen($inscript) + 3 < strlen($output)){
						$endtagv = substr($output, $pos, strlen($inscript) + 3);
						$endtagv = strtolower($endtagv);
						if ($endtagv == "</".$inscript.">"){
							$inscript = null;
							$pos += strlen($endtagv);
							continue;
						}
					}
					$pos++;
				}
				else{
					$outflg = false;
					if ($pos < strlen($output)-1 && $output[$pos+1] == "!"){
						$pos+=2;
					}
					else{
						$inq = false;
						$endpos = -1;
						for($i = $pos+1; $i < strlen($output); $i++){
							$c = $output[$i];
							if ($inq){
								if ($c == '"'){
									$inq = false;
								}
								continue;
							}
							if ($c == ">"){
								$endpos = $i;
								break;
							}
							if ($c == '"'){
								$inq = true;
							}
						}
						if ($endpos == -1){
							break;
						}
						$tagv = substr($output, $pos, $endpos-$pos+1);
						$endtag = ($tagv[1] == "/");

						if (!$endtag){
							$tagname = "";
							for($i = 1; $i < strlen($tagv)-1; $i++){
								if (ord($tagv[$i]) <= 0x20 || $tagv[$i] == ">"){
									$tagname = substr($tagv, 1, $i-1);
									break;
								}
							}
							if ($tagname == ""){
								$tagname = substr($tagv, 1, strlen($tagv)-2);
							}
							$tagname = strtolower($tagname);
							$repsetting = $this->pathConvertSetting;
							$repkeys = array_keys($repsetting);
							if (in_array($tagname, $repkeys)){
								$repv = $repsetting[$tagname];
								$repvlen = strlen($repv);
								$ntagv = "";
								$ipos = strlen($tagname)+2;
								$nipos = 0;
							$tryct = 0;
								while($ipos < strlen($tagv)-$repvlen-2){
						if ($tryct++ > 100){
							var_dump('error');
							exit;
						}
									$c = $tagv[$ipos];
									if (ord($c) > 0x20){
										$iendpos = -1;
										$attrname = "";
										$qst = 0;
										$qed = 0;
										for($i = $ipos+1; $i < strlen($tagv); $i++){
											$ic = $tagv[$i];
											if ($qst != 0 && $qed == 0){
												if ($ic == '"'){
													$qed = $i;
												}
												continue;
											}
											if (ord($ic) <= 0x20 || $ic == ">"){
												$iendpos = $i;
												break;
											}
											if ($ic == "="){
												$attrname = substr($tagv, $ipos, $i-$ipos);
											}
											else if ($ic == '"'){
												$qst = $i;
											}
										}
										if ($iendpos == -1) $iendpos = strlen($tagv);
										if (strtolower($attrname) == $repv && $qst != 0 && $qed != 0){
											$inqv = substr($tagv, $qst+1, $qed-$qst-1);
											$inqv = $this->pathConvertValue($tagname, $repv, $inqv);
											$ntagv .= substr($tagv, $nipos, $ipos-$nipos);
											$ntagv .= $attrname.'="'.$inqv.'"';
											$ipos = $iendpos;
											$nipos = $ipos;
										}
										else{
											$ipos = $iendpos;
										}
									}
									else{
										$ipos++;
									}
								}
								if ($nipos != 0){
									$ntagv .= substr($tagv, $nipos);
									$noutput .= substr($output, $npos, $pos-$npos);
									$noutput .= $ntagv;
									$npos = $endpos+1;
								}
							}
							if ($tagname == "script" || $tagname == "style"){
								$inscript = $tagname;
							}
						}
						$pos = $endpos+1;
					}
				}
			}
			else{
				$pos++;
			}
		}
		if ($npos != 0){
			$noutput .= substr($output, $npos);
			$output = $noutput;
		}
		return $output;

	}
	function pathConvertValue($tagname, $attrname, $v){
		$mode = null;
		if (isset($this->pathConvertSetting[$tagname])){
			$setting = $this->pathConvertSetting[$tagname];
			if ($setting == $attrname){
				$mode = "withroot";
			}
		}
		if ($mode == "withroot"){
			$gpath = $this->obs->data['siteusage']['basepath'];
			if ($v == "/"){
				return $this->obs->data['siteusage']['toppagepath'];
			}
			if (strlen($v) > 0 && $v[0] == "/"){
				return $this->obs->data['webrootpath'].$gpath.$v;
			}
			if (strlen($v) > 1 && $v[0] == "-" && $v[1] == "/"){
				return $this->obs->data['webrootpath'].substr($v, 1);
			}
		}
		return $v;
	}
}

class WebRedirectU extends WebU{
	function process(){
		$url = "";
		if (isset($this->setting['url'])){
			$url = $this->setting['url'];
		}
		if (!$url) $url = "/";
		if (!isset($this->setting['direct']) || !$this->setting['direct']){
			if ($url == "/"){
				$url = $this->obs->data['siteusage']['toppagepath'];
			}
			else{
				$url = WEB_ROOT_PATH.$this->obs->data['siteusage']['basepath'].$url;
			}
		}
		http_response_code(302);
		header('Location:'.$url);
		$this->nocacheHeader();
		return null;

	}
}
class WebErrorU extends WebU{
	function process(){
		if (!isset($this->setting['nochain']) || !$this->setting['nochain']){
			$group = "default";
			if (isset($this->obs->data['siteusage']['group'])){
				$group = $this->obs->data['siteusage']['group'];
			}
			$root = $this->obs->webSetting;
			if (isset($this->obs->webSetting['group']) && isset($this->obs->webSetting['group'][$group])){
				$root = $this->obs->webSetting['group'][$group];
			}
			if (isset($root['error'])){
				$errortarget = "default";
				if (isset($root['error'][$errortarget])){
					if (isset($setting['target'])){
						$errortarget = $setting['target'];
					}
					$nsetting = array();
					if (isset($this->setting['setting'])){
						$nsetting = $this->setting['setting'];
					}
					$setting = array_merge($root['error'][$errortarget], $nsetting);
					return $setting;
				}
			}
		}
		var_dump($this->setting);
		return null;
	}
}

class MailObs extends Obs{
	var $mailSetting = null;
	var $config = null;
	var $issuccess = false;
	function __construct($setting, $config){
		$this->mailSetting = $setting;
		$this->config = $config;
	}
	function firstSetting(){
		return array('class'=>'first');
	}
	function genUDo($u, $setting){
		if (!$setting) return null;
		if ($setting['class'] == "first"){
			return new MailFirstU();
		}
		else if ($setting['class'] == "send"){
			return new MailSendU();
		}
		else if ($setting['class'] == "error"){
			return null;
		}
	}

	function lg($txt){
		$logdir = APP_ROOT.'/.log';
		$logfile = $logdir."/.".date('Ymd').'.log';
		if (!is_writable($logfile)){
			if (!is_writable($logdir)){
				return;
			}
		}
		$fp = fopen($logfile, "a");
		fwrite($fp, date('[Y/m/d H:i:s]').$txt."\n");
		fclose($fp);
	}

}

class MailFirstU extends U{
	function process(){
		if (!isset($this->obs->config['template'])) return array('class'=>'error');
		$template = $this->obs->config['template'];
		$data = array();
		if (isset($this->obs->config['data'])){
			$data = $this->obs->config['data'];
		}
		$lang = 'ja';
		if (isset($this->obs->config['lang'])){
			$lang = $this->obs->config['lang'];
		}
		$root = APP_ROOT;
		if (isset($this->obs->mailSetting['mail_template_root'])){
			$root = $this->obs->mailSetting['mail_template_root'];
		}
		$filepath = $root.'/'.$template.'.tpl';
		if (!is_readable($filepath)){
			return array('class'=>'error');
		}
		$commondata = array();
		if (isset($this->obs->mailSetting['default_common'])){
			$commondata = $this->obs->mailSetting['default_common'];
		}
		$commondata['ua'] = getenv('HTTP_USER_AGENT');
		$commondata['ip'] = getenv('REMOTE_ADDR');
		$commondata['date'] = date('r');
		$commondata['lang'] = $lang;
		$data['_common'] = $commondata;

		$opt = array(
			'env'=>array('VIEW_ROOT'=>$root),
			'ml_lang'=>$lang,
			'ml_options'=>"!"
		);
		if ($this->obs->mailSetting && isset($this->obs->mailSetting['ml'])){
			if (isset($this->obs->mailSetting['ml']['opt'])){
				$opt = array_merge($opt, $this->obs->mailSetting['ml']['opt']);
			}
			if (!isset($opt['ml_convert'])){
				$opt['ml_convert'] = function($format, $vals, $attr, $lang, $thru=false){
					if (isset($attr['lang'])) $lang = $attr['lang']; 
					if ($lang){
						$obj = \S9\MultiLang\MultiLang::object($lang);
						$format = $obj->text($format);
					}
					if ($thru) return $format;
					return vsprintf($format, $vals);
				};
			}
		}
		$tmpl = new \S9MyTmpl\S9MyTmpl($filepath, "file", $opt);
		$out = $tmpl->output($data);

		$this->obs->lg($out);

		$headers = array();
		$pos = 0;
		for($i = 0; $i < strlen($out); $i++){
			$ln = null;
			$lnlen = 0;
			if ($out[$i] == "\r"){
				$lnlen = 1;
				if ($i < strlen($out)-1 && $out[$i+1] == "\n"){
					$lnlen = 2;
				}
			}
			else if ($out[$i] == "\n"){
				$lnlen = 1;
			}
			if ($lnlen  > 0){
				$line = substr($out, $pos, $i-$pos);
				$i += $lnlen-1;
				$pos = $i+1;
				if ($line == "") break;
				if (preg_match('/^(\w+)\s*\:\s*/', $line, $m)){
					$hname = strtolower($m[1]);
					$hval = substr($line, strlen($m[0]));
					$headers[$hname] = $hval;
				}
			}
		}
		$body = substr($out, $pos);
		$this->obs->data['headers'] = $headers;
		$this->obs->data['body'] = $body;
		return array('class'=>'send');
	}
}
class MailSendU extends U{

	function process(){
		$headers = array();
		$body = "";
		if (isset($this->obs->data['headers'])){
			$headers = $this->obs->data['headers'];
		}
		if (isset($this->obs->data['body'])){
			$body = $this->obs->data['body'];
		}

		$encheaders = array();
		$to = null;
		if (!isset($headers['content-transfer-encoding'])){
			$headers['content-transfer-encoding'] = '8bit';
		}
		if (!isset($headers['content-type'])){
			$headers['content-type'] = 'text/plain; charset=UTF-8';
		}
		$subject = null;
		foreach ($headers as $k=>$v){
			if (in_array($k, array('to','cc','bcc','from'))){
				$rawemail = null;
				if (preg_match('/^(.*)\<(.*)\>$/', $v, $m)){
					$v = $this->encbase64($m[1])."<".$m[2].">";
				}
				if ($k == "to") $to = $v;
			}
			else if ($k == "subject"){
				$v = $this->encbase64($v);
			}

			if ($k == "subject"){
				$subject = $v;
			}
			else if ($k != "to"){
				$nextset = true;
				for($i = 0; $i < strlen($k); $i++){
					if ($nextset){
						$k[$i] = strtoupper($k[$i]);
						$nextset = false;
					}
					else if ($k[$i] == "-"){
						$nextset = true;
					}
				}
				$encheaders[$k] = $v;
				if ($k == "From" && !isset($headers['sender'])){
					$encheaders["Sender"] = $v; 
				}
			}
		}
/*
		$encbody = "";
		$pos = 0;
		$mblen = mb_strlen($body);
		for($i = 0; $i < $mblen; $i++){
			$c = mb_substr($body, $i, 1);
			$lnlen = 0;
			if ($c == "\r"){
				$lnlen = 1;
				$c0 = null;
				if ($i < $mblen-1){
					$c0 = mb_substr($body, $i+1,1);
				}
				if ($c0 == "\n"){
					$lnlen = 2;
				}
			}
			else if ($c == "\n"){
				$lnlen = 1;
			}
			if ($lnlen > 0){
				$encbody .= mb_substr($body, $pos, $i-$pos);
				$encbody .= "\r\n";
				$i += $lnlen-1;
				$pos = $i+1;
			}
			else{
				if ($i-$pos >= 68){
					$encbody .= mb_substr($body, $pos, $i-$pos);
					$encbody .= "\r\n";
					$pos = $i;
				}
			}
		}
		if ($pos < $mblen){
			$encbody .= mb_substr($body, $pos);
		}
*/
		$encbody = $body;

		$this->obs->issuccess = mail($to, $subject, $encbody, $encheaders);
		return null;
	}

	function encbase64($v){
		return "=?UTF-8?B?".base64_encode($v)."?=";
	}
}
