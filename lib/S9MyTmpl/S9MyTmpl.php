<?php

namespace S9MyTmpl;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'plugins.php';

class S9MyTmpl{
	
	static $defaultConfig = array(
		'start_tag'=>'<@',
		'end_tag'=>'@>',
		'default_options'=>array('htmlescape'),
		'namespaces'=>array(),
		'env'=>array(),
	);

	var $tmplsrc;
	var $root = array();
	var $errorstr = "";
	var $sendframe = array();
	var $config = null;

	function __construct($tmplinfo, $mode="text", $config=array()){
		$this->config = array_merge(self::$defaultConfig, $config);
		if ($mode === "file"){
			$this->tmplsrc = file_get_contents($tmplinfo);
			$this->config['texted'] = $this->loadTexted($tmplinfo);
		}
		else{
			$this->tmplsrc = $tmplinfo;
		}
		$this->_analize();
	}

	function loadTexted($file){
		$txdata = array();
		if (isset($this->config['is_texted']) && $this->config['is_texted']){
			$txtfile = $file.'.texted.txt';
			if (is_readable($txtfile)){
				$flns = file($txtfile);
				foreach ($flns as $fln){
					$fln = preg_replace('/[\r\n]/', '', $fln);
					if (preg_match('/^(\S+)\s+/', $fln, $m)){
						$code = $m[1];
						$val = substr($fln, strlen($m[0]));
						$txdata[$code] = $val;
					}
				}
			}
		}
		return $txdata;
	}
	
	function hashval($name, $hash){
		if ($name == null) return null;
		if (!is_array($hash)) return null;
		if (!array_key_exists($name, $hash)){
			return null;
		}
		return $hash[$name];
	}
	function hashval_array($names, $hash){
		if (!is_array($names)){
			return $this->hashval($names, $hash);
		}
		$cur = $hash;
		foreach($names as $nm){
			if (!is_array($cur)){
				return null;
			}
			$cur = $this->hashval($nm, $cur);
		}
		return $cur;
	}

	function error($val){
		$this->errorstr = $val;
	}
	function isError(){
		return ($this->errorstr !== "");
	}
	function dumpError(){
		print $this->errorstr;
	}
	
	
	function _analize(){
		$this->sendframe_src = array();
		$res = $this->_analizeSrc($this->tmplsrc);
		if ($res === false){
			return false;
		}
		$this->root = $res;
	}
	
	function _configTagStart(){
		$val = $this->hashval('start_tag', $this->config);
		if ($val){
			return $val;
		}
		return "<@";
	}
	function _configTagEnd(){
		$val = $this->hashval('end_tag', $this->config);
		if ($val){
			return $val;
		}
		return "@>";
	}
	function _configDefaultOptions(){
		$val = $this->hashval('default_options', $this->config);
		if ($val){
			return $val;
		}
		return array();
	}
	
	function _analizeSrc($src){
		$ar = array();
		$st = $this->_configTagStart();
		$st = preg_replace('/(\W)/', "\\\\$1", $st);
		$ed = $this->_configTagEnd();
		while(preg_match("/^(.*?)${st}/sm", $src, $m)){
			$txt = $m[1];
			$src = substr($src, strlen($m[0]));
			if ($txt !== ""){
				$ar[] = array('text', $txt);
			}
			$pos = 0;
			$q = false;
			$endpos = -1;
			for($pos = 0; $pos < strlen($src); $pos++){
				if ($src[$pos] == "\""){
					$q = !$q;
					continue;
				}
				if ($q){
					continue;
				}
				if (substr($src, $pos, strlen($ed)) === $ed){
					$endpos = $pos;
					break;
				}
			}
			if ($endpos < 0){
				$this->error("error at '".substr($src, 0, 20)."'");
				return false;
			}
			$command = substr($src, 0, $endpos);
			$src = substr($src, $endpos + strlen($ed));
			$ar[] = array('command', $command);
		}
		if ((string)$src !== ""){
			$ar[] = array('text', $src);
		}
		
		$res = $this->_analizeDataList($ar);
		if ($res === false){
			return false;
		}
		return $res;
	}
	
	function _analizeDataList(&$ar, $type=null){
		$datalist = array(0=>array(), 1=>array());
		$target = 0;
		while($srcdataar = array_shift($ar)){
			if ($srcdataar[0] === "text"){
				$datalist[$target][] = array('type'=>'text', 'value'=>$srcdataar[1]);
				continue;
			}
			$command = $this->_analizeCommand($srcdataar[1]);
			if ($command === false){
				$this->error("command error ".substr($srcdataar[1], 0 ,20));
				return false;
			}
			if (isset($command['type']) && $command['type'] === "sendframe"){
				$paramsar = $command['params'];
				foreach($paramsar as $k=>$v){
					$this->sendframe[$k] = array('type'=>'value', 'value'=>$v);
				}
				continue;
			}
			if (isset($command['type']) && $command['type'] === "sendframe_tmpl"){
				$paramsar = $command['params'];
				foreach($paramsar as $k=>$v){
					$this->sendframe[$k] = array('type'=>'tmpl', 'value'=>$v);
				}
				continue;
			}
			if ($command === "else"){
				$target = ($target == 0)? 1: 0;
				continue;
			}
			if (isset($command['has_child']) && ($command['has_child'])){
				$res =  $this->_analizeDataList($ar, $command['type']);
				if ($res === false){
					return false;
				}
				$command['childlist'] = $res;
				
				if ($command['type'] == 'ml'){
					if (isset($this->config['ml_parsed']) && is_callable($this->config['ml_parsed'])){
						$attr = null;
						if (isset($this->config['ml_parsed_attr'])) $attr = $this->config['ml_parsed_attr'];
						call_user_func_array($this->config['ml_parsed'], array($command['childlist'], $attr));
					}
				}
				
			}
			if (isset($command['end_tag']) && ($command['end_tag'])){
				if ($type === $command['type']){
					return $datalist;
				}
			}
			$datalist[$target][] = $command;
		}
		if ($type){
			$this->error('in scope error type:'.$type);
			return false;
		}
		return $datalist;
	}
	
	function _analizeCommand($com){
		$com = preg_replace('/^\s*|\s*$/s', "", $com);
		if ($com[0] === "*"){
			return array('type'=>'comment', 'value'=>$com);
		}
		if (($com[0] === "@")||($com[0] === '/')){
			if (preg_match('/^(\@|\/)(\w+)(\s+|$)/s', $com, $m)){
				$tagtype = $m[2];
				$values = substr($com, strlen($m[0]));
				if ($tagtype === "else"){
					if ($com[0] === "/"){
						return false;
					}
					return "else";
				}
				if ($tagtype === "sendframe" || $tagtype === "sendframe_tmpl"){
					if ($com[0] === "/"){
						return false;
					}
					$values = preg_replace('/^\s+/', "", $values);
					$nm  = null;
					$q = false;
					$pos = 0;
					$valar = array();
					for($i = 0; $i < strlen($values); $i++){
						if ($q){
							if ($values[$i] == '"'){
								$q = false;
								continue;
							}
						}
						else{
							if (ord($values[$i]) <= 0x20){
								$val = substr($values, $pos, $i - $pos);
								$val = preg_replace('/^\s+/', '', $val);
								if (strlen($val) > 0){
									if ($val[0] == '"'){
										$val = substr($val, 1);
									}
								}
								if (strlen($val) > 0){
									if ($val[strlen($val)-1] == '"'){
										$val = substr($val, 0, -1);
									}
								}
								if ($nm === null){
									$nm = substr($values, $pos, $i - $pos);
								}
								else{
									$valar[$nm] = $val;
									$nm = null;
								}
								$pos = $i+1;
								continue;
							}
							elseif ($values[$i] =='"'){
								$q = true;
								continue;
							}
						}
					}
					if ($nm !== null){
						$val = substr($values, $pos, strlen($values)-$pos);
						$val = preg_replace('/^\s+/', '', $val);
						if (strlen($val) > 0){
							if ($val[0] == '"'){
								$val = substr($val, 1);
							}
						}
						if (strlen($val) > 0){
							if ($val[strlen($val)-1] == '"'){
								$val = substr($val, 0, -1);
							}
						}
						$valar[$nm] = $val;
					}
					return array('type'=>$tagtype, 'params'=>$valar);
				}
				if ($tagtype === "lp"){
					if ($com[0] === "/"){
						return array('type'=>'lp', 'end_tag'=>true);
					}
					$attr = $this->_textToAttr($values);
					$name = $this->hashval('name', $attr);
					if ($name[0] == '$'){
						$name = substr($name, 1);
					}
					return array('type'=>'lp', 'value'=>$name, 'has_child'=>true, 'num'=>$this->hashval('num', $attr));
				}
				if ($tagtype === "if"){
					if ($com[0] === "/"){
						return array('type'=>'if', 'end_tag'=>true);
					}
					return array('type'=>'if', 'value'=>$values, 'has_child'=>true);
				}
				if ($tagtype === "include"){
					if ($com[0] === "/"){
						return false;
					}
					$attr = $this->_textToAttr($values);
					$dataval = $this->hashval("data", $attr);
					if ($dataval && $dataval[0] == '$'){
						$dataval = substr($dataval, 1);
					}
					return array('type'=>'include', 'file'=>$this->hashval("file", $attr), 'data'=>$dataval, 'extdata'=>$this->hashval("extdata", $attr), 'option'=>$this->hashval("option", $attr));
				}
				if ($tagtype === "fillin"){
					if ($com[0] === "/"){
						return array('type'=>'fillin', 'end_tag'=>true);
					}
					$attr = $this->_textToAttr($values);
					$dataval = $this->hashval("data", $attr);
					if ($dataval[0] == '$'){
						$dataval = substr($dataval, 1);
					}
					return array('type'=>'fillin', 'data'=>$dataval, 'has_child'=>true);
				}
				if ($tagtype === "ml"){
					if ($com[0] === "/"){
						return array('type'=>'ml', 'end_tag'=>true);
					}
					$attr = $this->_textToAttr($values);
					return array('type'=>'ml', 'attr'=>$attr, 'has_child'=>true);
				}
				if ($tagtype === "g"){
					if ($com[0] === "/"){
						return array('type'=>'g', 'end_tag'=>true);
					}
					return array('type'=>'g', 'has_child'=>true);
				}
				return false;
			}
			return false;
		}
		if (preg_match('/^\$([\/\?\=]?[\w\.]+|:\S*)(?:\s+(.*))?$/sD', $com, $m)){
			$mar = array('type'=>'var', 'value'=>$m[1]);
			if (isset($m[2])) $mar['options'] = $m[2];
			return $mar;
		}
		return false;
	}
	
	function _textToAttr($text){
		$attr = array();
		$q = false;
		$nm = "";
		$lastpos = 0;
		$valarea = false;
		for ($pos = 0; $pos < strlen($text); $pos++){
			if (($q) && ($text[$pos] === "\\")){
				$pos++;
				continue;
			}
		
			if ($text[$pos] === "\""){
				$q = !$q;
				continue;
			}
			if ($q){
				continue;
			}
			if (ord($text[$pos]) <= 0x20){
				if ($valarea){
					$value = $this->_trim(substr($text, $lastpos, $pos - $lastpos));
					$valarea = false;
				}
				else{
					$nm = $this->_trim(substr($text, $lastpos, $pos - $lastpos));
					$value = null;
				}
				if ($nm){
					$nm = preg_replace('/\\\\(.)/', "\\1", $nm);
					if (!is_null($value)){
						$value = preg_replace('/\\\\(.)/', "\\1", $value);
					}
					$attr[strtolower($nm)] = $value;
					$nm = "";
				}
				$lastpos = $pos + 1;
				continue;
			}
			if (($text[$pos] === "=") &&(!$valarea)){
				$nm = $this->_trim(substr($text, $lastpos, $pos - $lastpos));
				$lastpos = $pos + 1;
				$valarea = true;
			}
		}
		if ($valarea){
			$value = $this->_trim(substr($text, $lastpos, $pos - $lastpos));
		}
		else{
			$nm = $this->_trim(substr($text, $lastpos, $pos - $lastpos));
			$value = null;
		}
		if ($nm){
			$nm = preg_replace('/\\\\(.)/', "\\1", $nm);
			if (!is_null($value)){
				$value = preg_replace('/\\\\(.)/', "\\1", $value);
			}
			$attr[strtolower($nm)] = $value;
		}
		return $attr;
	}

	function _attrToText($attr, $quotes=true){
		$txt = "";
		foreach ($attr as $k=>$v){
			if ($quotes){
				$k = htmlspecialchars($k, ENT_QUOTES);
				if (!is_null($v)){
					$v = htmlspecialchars($v, ENT_QUOTES);
				}
			}
			$txt .= $k;
			if (!is_null($v)){
				$txt .= '="'.$v.'"';
			}
			$txt .= " ";
		}
		return $txt;
	}

	function _trim($val){
		$val = preg_replace('/^\s*|\s*$/', "", $val);
		if (preg_match('/\"(.*)\"/s', $val, $m)){
			$val = $m[1];
		}
		return $val;
	}
	
	function applySendFrame($data, $rootdata=null){
		$nar = array();
		foreach ($this->sendframe as $k=>$v){
			if ($v['type'] == 'value'){
				$nar[$k] = $this->_applyEvalParam($v['value'], $data, $rootdata);
			}
			else if ($v['type'] == 'tmpl'){
				$tmpl = new S9MyTmpl($v['value']);
				$tmpl->config = $this->config;
				$nar[$k] = $tmpl->output($data);
			}
		}
		return $nar;
	}
	
	function output($data, $rootdata=null){
		if ($this->isError()){
			return false;
		}
		if (is_null($rootdata)){
			$rootdata = $data;
		}
		return $this->_apply($this->root[0], $data, $data);
	}
	
	function _apply($list, $data, $rootdata){
		$output = "";
		foreach ($list as $listdata){
			if ($listdata['type'] === "text"){
				$output .= $listdata['value'];
				continue;
			}
			if ($listdata['type'] === "var"){
				$res = $this->_applyVariable($listdata, $data, $rootdata);
				if ($res === false){
					return false;
				}
				$output .= $res;
				continue;
			}
			if ($listdata['type'] === "lp"){
				$res = $this->_applyLoopList($listdata, $data, $rootdata);
				if ($res === false){
					return false;
				}
				$lplist = $res;
				if (count($lplist) == 0){
					$res = $this->_apply($listdata['childlist'][1], $data, $rootdata);
					if ($res === false){
						return false;
					}
					$output .= $res;
				}
				else{
					$noadd = false;
					if ($listdata['num'] > 0){
						$lpnum = $listdata['num'];
						$lpmax = ((int)((count($lplist)-1) / $lpnum)+1) * $lpnum;
					}
					else{
						$lpnum = 1;
						$lpmax = count($lplist);
					}
					for ($i = 0; $i < $lpmax; $i++){
						if ($i < count($lplist)){
							$lpdata = $lplist[$i];
							if (is_array($lpdata)){
								$lpdata['__set'] = true;
							}
						}
						else{
							$lpdata = array();
							$lpdata['__set'] = false;
						}
						if (is_array($lpdata)){
							$lpdata['__lpnum'] = $i;
							if ($lpnum > 1){
								$lpdata['__devidenum'] = $i % $lpnum;
								$lpdata['__first'] = ($lpdata['__devidenum'] == 0);
								$lpdata['__last'] = ($lpdata['__devidenum'] == $lpnum-1);
							}
						}
						$res = $this->_apply($listdata['childlist'][0], $lpdata, $rootdata);
						if ($res === false){
							return false;
						}
						$output .= $res;
					}
				}
				continue;
			}
			if ($listdata['type'] === "if"){
				$res = $this->_applyIfTarget($listdata, $data, $rootdata);
				if ($res === false){
					return false;
				}
				$iftarget = $res;
				$res = $this->_apply($listdata['childlist'][$iftarget], $data, $rootdata);
				if ($res === false){
					return false;
				}
				$output .= $res;
				continue;
			}
			if ($listdata['type'] === "include"){
				$file = $listdata['file'];
				$file = $this->_applyValueData($data, $rootdata, $file);
				if (!file_exists($file)){
//					$this->error("include file not exists ".$file);
//					return false;
					continue;
				}
				$position = "";
				if (array_key_exists("data", $listdata)){
					$position = $listdata['data']; 
				}
				if (!$position){
					$position = ".";
				}
				$senddata = $this->_applyValue($data, $rootdata, $position);
				if ($listdata['extdata']){
					$extdata = json_decode($listdata['extdata'], true);
					$senddata['_extdata'] = $extdata;
				}
				
				$t = new S9MyTmpl($file, "file");
				$t->config = $this->config;
				$t->config['texted'] = $this->loadTexted($file);
				$incout = $t->output($senddata, $rootdata);
				if ($incout === false){
					$this->error("include file ".$file." output error ".$t->errorstr);
					return false;
				}
				if ($listdata['option']){
					$incout = $this->_valueWithOption($incout, $listdata['option']);
				}
				$output .= $incout;
			}
			if ($listdata['type'] === "fillin"){
				$res = $this->_apply($listdata['childlist'][0], $data, $rootdata);
				if ($res === false){
					return false;
				}
				$position = $listdata['data']; 
				if (!$position){
					$position = ".";
				}
				$filldata = $this->_applyValue($data, $rootdata, $position);
				$fillout = $this->_applyFillin($res, $filldata);
				$output .= $fillout;
			}
			if ($listdata['type'] === "ml"){
				if (isset($this->config['ml_convert'])){
					$format = "";
					$vals = array();
					if (isset($listdata['childlist'][0])){
						foreach ($listdata['childlist'][0] as $tagset){
							if ($tagset['type'] == 'text'){
								$format .= $tagset['value'];
							}
							else{
								$format .= '%s';
								$res = $this->_apply(array($tagset), $data, $rootdata);
								if ($res === false){
									return false;
								}
								$vals[] = $res;
							}
						}
					}
					$lang = null;
					if (isset($this->config['ml_lang'])) $lang = $this->config['ml_lang'];
					$res = call_user_func_array($this->config['ml_convert'], array($format, $vals, $listdata['attr'], $lang));
				}
				else{
					$res = $this->_apply($listdata['childlist'][0], $data, $rootdata);
					if ($res === false){
						return false;
					}
				}
				$output .= $res;
			}
			if ($listdata['type'] === "g"){
				$res = $this->_apply($listdata['childlist'][0], $data, $rootdata);
				if ($res === false){
					return false;
				}
				$output .= $res;
			}

		}
		return $output;
	}
	
	function _applyDefault($val){
		return $val;
	}

	function _applyValueData($data, $rootdata, $value){
		$nv = "";
		while(preg_match('/^(.*?)\$\{?([\/\?]?[\w+.]+|\:.*)\}?/', $value, $m)){
			$nv .= $m[1];
			$v = $this->_applyValue($data, $rootdata, $m[2]);
			$nv .= $v;
			$value = substr($value, strlen($m[0]));
			if (strlen($value) > 0 && $value[0] =='$'){
				$value = substr($value, 1);
			}
		}
		$nv .= $value;
		return $nv;
	}

	
	function _applyValue($data, $rootdata, $position){
		if ($position == ".") {
			return $data;
		}
		if ($position[0] == ":"){
			return substr($position, 1);
		}
		else if ($position[0] == "?"){
			$nm = substr($position, 1);
			if (isset($this->config['env'][$nm])){
				return $this->config['env'][$nm];
			}
			return "";
		}
		else if ($position[0] == "="){
			$nm = substr($position, 1);
			if (isset($this->config['texted'][$nm])){
				return $this->config['texted'][$nm];
			}
			return "";
		}
		else if ($position[0] == "/"){
			$position = substr($position, 1);
			$val = $rootdata;
		}
		else{
			$val = $data;
		}
		$position_ar = preg_split('/\./', $position);
		foreach ($position_ar as $pos){
			if ($pos === ""){
				continue;
			}
			$val = isset($val[$pos])? $val[$pos]: "";
		}
		return $val;
	}
	
	function _valueWithOption($val, $options){
		$useroot = false;
		$usedefault = true;
		$optar = preg_split('/\s+/', $options);
		$opts = array();
		foreach ($optar as $opt){
			if (preg_match('/^\s*$/', $opt)){
				continue;
			}
			elseif ($opt === "!"){
				$usedefault = false;
			}
			elseif ($opt === "+"){
				$opts[] = "htmlescape";
			}
			elseif ($opt === "+u"){
				$opts[] = "urlescape";
			}
			elseif ($opt === "+j"){
				$opts[] = "jsescape";
			}
			elseif ($opt === "|"){
				$opts[] = "nl2br";
			}
			else{
				if (preg_match('/\W/', $opt)){
					$this->error('option error '.$opt);
					return false;
				}
				$opts[] = $opt;
			}
		}
		if ($usedefault && (count($opts) == 0)){
			$opts = $this->_configDefaultOptions();
		}
		$nopts = array();
		foreach ($opts as $o){
			if (!in_array($o, $nopts)){
				$nopts[] = $o;
			}
		}
		foreach ($nopts as $optin){
			$nss = $this->config['namespaces'];
			array_unshift($nss, 'S9MyTmpl');
			$func = null;
			foreach ($nss as $ns){
				$func = $ns."\\mytmpl_plugin_".$optin;
				if (function_exists($func)){
					break;
				}
				$func = null;
			}
			if (!$func){
				$this->error('option not exists '.$optin);
				return false;
			}
			$val = call_user_func_array($func, array($val, $this->config));
		}
		return (string)$val;
	}
	
	function _applyVariable($listdata, $data, $rootdata){
		$val = $this->_applyValue($data, $rootdata, $listdata['value']);
		$options = isset($listdata['options'])? $listdata['options']: "";
		$val = $this->_valueWithOption($val, $options);
		return $val;

	}


	function _applyLoopList($listdata, $data, $rootdata){
		$val = $this->_applyValue($data, $rootdata, $listdata['value']);
		if (is_array($val)){
			return $val;
		}
		else{
			return array();
		}
	}
	
	function _applyIfTarget($listdata, $data, $rootdata){
		$ifvalue = $listdata['value'];
		$nvalue = "";
		$ndata = array();
		while(preg_match('/^(.*?)\$(\/?[\w.]+)/s', $ifvalue, $m)){
			if ($m[2] !== ""){
				$nvalue .= $m[1];
				$ifvalue = substr($ifvalue, strlen($m[0]));
				$nval = $this->_applyValue($data, $rootdata, $m[2]);
				$ndata[] = $nval;
				$nvalue .= '$ndata['.(count($ndata)-1).']';
			}
		}
		$nvalue .= $ifvalue;
		eval('$flag = '.$nvalue.';');
		return ($flag)? 0: 1;
	}
	
	function _applyEvalParam($evalstr, $data, $rootdata){
		$nvalue = "";
		$ndata = array();
		$repflag = false;
		while(preg_match('/^(.*?)\$(\/?[\w.]+)/s', $evalstr, $m)){
			if ($m[2] !== ""){
				$nvalue .= $m[1];
				$evalstr = substr($evalstr, strlen($m[0]));
				$nval = $this->_applyValue($data, $rootdata, $m[2]);
				$ndata[] = $nval;
				$nvalue .= '$ndata['.(count($ndata)-1).']';
				$repflag = true;
			}
		}
		
		if (!$repflag){
			return $evalstr;
		}
		$nvalue .= $evalstr;
		$val = "";
		eval('$val = '.$nvalue.';');
		return $val;
	}

	
	function _applyFillin($src, $data){
		$newsrc = "";
/*
		while(preg_match('/^(.*?)<(input|select|textarea|script)([^>]*)>/is', $src, $m)){
			$src = substr($src, strlen($m[0]));
			$newsrc .= $m[1];
			$tagtype = strtolower($m[2]);
			$tagattrtxt = $m[3];
*/
		while(1){
			$toend = false;
			$m = null;
			for($i = 0; $i < strlen($src); $i++){
				if ($src[$i] == '<'){
					$alltgs = array('input','select','textarea','script');
					$sttag = null;
					foreach ($alltgs as $tgv){
						if (strtolower(substr($src, $i+1, strlen($tgv))) == $tgv){
							$sttag = $tgv;
							break;
						}
					}
					if (!$sttag) continue;
					$pos = strlen($tgv)+1;
					$inq = false;
					while($src[$i+$pos] != ">" || $inq){
						if ($src[$i+$pos] == '"') $inq = !$inq;
						$pos++;
						if ($i+$pos >= strlen($src)){
							$toend = true;
							break;
						}
					}
					if ($toend){
						break;
					}
					
					$m = array(
						substr($src, 0, $i+$pos+1),
						substr($src, 0, $i),
						$sttag,
						substr($src, $i+1+strlen($sttag), $pos-1-strlen($sttag))
					);
					break;
				}
			}
			if (!$m) break;

			$src = substr($src, strlen($m[0]));
			$newsrc .= $m[1];
			$tagtype = strtolower($m[2]);
			$tagattrtxt = $m[3];

			$attr = $this->_textToAttr($tagattrtxt);
			if($tagtype == "script"){
				if (preg_match('/(.*?)<\/script>/si', $src, $m)){
					$src = substr($src, strlen($m[0]));
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">".$m[1].'</script>';
					continue;
				}
				else{
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">";
					continue;
				}
			}
			if ($this->hashval("name", $attr) == ""){
				$newsrc .= "<".$tagtype." ".$tagattrtxt.">";
				continue;
			}
			if ($tagtype == "input"){
				if (($this->hashval("type", $attr) == "radio")||($this->hashval("type", $attr) == "checkbox")){
					$attrval = $this->hashval("value", $attr);
					$datakey = $this->hashval("name", $attr);
					$dataval = $this->hashval($datakey, $data);
					if ($attrval == $dataval){
						$attr['checked'] = null;
					}
					else{
						unset($attr['checked']);
					}
				}
				elseif(($this->hashval("type", $attr) == "text")||($this->hashval("type", $attr) == "password")||($this->hashval("type", $attr) == "email"||($this->hashval("type", $attr) == "tel")||($this->hashval("type", $attr) == "number"))){
					$attr['value'] = $this->hashval($this->hashval("name", $attr), $data);
				}
				else{
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">";
					continue;
				}
				$attrtxt = $this->_attrToText($attr);
				$newsrc .= "<".$tagtype." ".$attrtxt.">";
				continue;
			}
			elseif($tagtype == "select"){
				if (preg_match('/(.*?)<\/select>/si', $src, $m)){
					$optstxt = $m[1];
					$src = substr($src, strlen($m[0]));
					$noptstxt = "";
					while(preg_match('/^(.*?)<option([^>]+)>/si', $optstxt, $m)){
						$noptstxt .= $m[1];
						$optstxt = substr($optstxt, strlen($m[0]));
						$optsattrtxt = $m[2];
						$optsattr = $this->_textToAttr($optsattrtxt);
						if(is_null($optsattr['value'])){
							$noptstxt .= "<option".$optsattrtxt.">";
							continue;
						}
						$optval = $this->hashval("value", $optsattr);
						$optdata = $this->hashval($this->hashval("name", $attr), $data);
						
						if ($optval == $optdata){
							$optsattr['selected'] = null;
						}
						else{
							unset($optsattr['selected']);
						}
						$noptstxt .= "<option ".$this->_attrToText($optsattr).">";
						continue;
					}
					$noptstxt .= $optstxt;
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">".$noptstxt.'</select>';
					continue;
				}
				else{
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">";
					continue;
				}
			}
			elseif($tagtype == "textarea"){
				if (preg_match('/(.*?)<\/textarea>/si', $src, $m)){
					$src = substr($src, strlen($m[0]));
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">".htmlspecialchars($this->hashval($this->hashval("name", $attr), $data), ENT_QUOTES).'</textarea>';
					continue;
				}
				else{
					$newsrc .= "<".$tagtype." ".$tagattrtxt.">";
					continue;
				}
			}
		}
		$newsrc .= $src;
		return $newsrc;	
	}


}



?>