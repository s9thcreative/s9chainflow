<?php

spl_autoload_register(function($class){
	$ns = 'MyApp\\';
	$nslen = strlen($ns);
	if (strncmp($ns, $class, $nslen) != 0) return;
	$class = substr($class, $nslen);
	$classpath = str_replace('\\', '/', $class);
	$filepath = APP_ROOT.'/myapp/'.$classpath.".php";
	require_once $filepath;
});

require_once APP_ROOT.'/setup/myapp/myappboot.php';
