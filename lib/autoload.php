<?php
require_once dirname(__FILE__).'/S9ChainU/chainu.php';

spl_autoload_register(function($classname){
	static $loadersetting = array(
		'S9MyTmpl'=>array('./S9MyTmpl', 0),
		'S9'=>array('./S9', 0)
	);
	foreach ($loadersetting as $k=>$path){
		$len = $path[1];
		if ($len == 0){
			$len = strlen($k);
			$loadersetting[$k][1] = $len;
		}
		$clen = strlen($classname);
		if ($clen < $len+1) continue;
		$substr = substr($classname, 0, $len+1);
		if ($k.'\\' != $substr) continue;
		
		$cpath = substr($classname, $len);
		$cpath = str_replace('\\', '/', $cpath);
		$file = dirname(__FILE__).'/'.$path[0].'/'.$cpath.'.php';
		require_once $file;
	}
});