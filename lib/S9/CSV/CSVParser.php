<?php
namespace S9\CSV;


class CSVParser{

	var $separator = ",";
	var $quote = "\"";
	var $result = array();
	var $labeling = null;

	var $callback = array();
	var $limit = 0;


	function __construct(){
	}

	function parseSrc($src){
		$result = array();
		$pos = 0;
		$len = strlen($src);
		$qflg = false;
		$v = "";
		$curd = array();
		$callbackbef = null;
		$callbackaft = null;
		if (isset($this->callback['before'])){
			$callbackbef = $this->callback['before'];
		}
		if (isset($this->callback['after'])){
			$callbackaft = $this->callback['after'];
		}
		for($i = 0; $i < $len+1; $i++){
			$c = null;
			if ($i < $len){
				$c = $src[$i];
			}
			if ($qflg){
				if ($c == $this->quote){
					if ($i < $len-1){
						if ($src[$i+1] == $this->quote){
							$v .= substr($src, $pos, $i-$pos);
							$i++;
							$pos = $i;
							continue;
						}
						else{
							$v .= substr($src, $pos, $i-$pos);
							$pos = $i+1;
							$qflg = false;
						}
					}
				}
				continue;
			}
			if ($c == $this->quote){
				$qflg = true;
				$v .= substr($src, $pos, $i-$pos);
				$pos = $i+1;
				continue;
			}
			if ($c === null || $c == $this->separator || $c == "\n" || $c == "\r"){
				$v .= substr($src, $pos, $i-$pos);
				$curd[] = $v;
				if ($c == "\r"){
					if ($i < $len-1){
						if ($src[$i+1] == "\n"){
							$i++;
						}
					}
				}
				$pos = $i+1;
				$v = "";
				if ($c === null || $c == "\n" || $c == "\r" ){
					if ($c !== null || count($curd) != 0){
						$insflg = true;
						if ($callbackbef){
							$insflg = $this->callf($callbackbef, array($curd));
						}
						if ($insflg){
							$insdata = $this->dataline($curd);
							if ($callbackaft){
								$insflg = $this->callf($callbackaft, array($insdata));
							}
						}
						if ($insflg){
							$result[] = $this->dataline($curd);
							if ($this->limit && (count($result) >= $this->limit)) break;
						}
					}
					$curd = array();
				}
			}
		}
		$this->result = $result;
		return true;
	}
	function parseFile($file){
		if (!is_readable($file)) return false;
		return $this->parseSrc(file_get_contents($file));
	}

	function dataline($data){
		if ($this->labeling === null) return $data;
		$ndata = array();
		for($i = 0; $i < count($data); $i++){
			if ($i < count($this->labeling)){
				$colname = $this->labeling[$i];
			}
			else{
				$colname = $i;
			}
			$ndata[$colname] = $data[$i];
		}
		return $ndata;
	}

	function callf($callback, $attr){
		return call_user_func_array($callback, $attr);
	}

	

}