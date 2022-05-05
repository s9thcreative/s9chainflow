<?php
define('APP_ROOT', dirname(__FILE__));

require_once APP_ROOT.'/lib/autoload.php';

require_once APP_ROOT.'/setup/boot.php';
require_once APP_ROOT.'/env/env.php';


$obs = new \S9ChainU\ObsWeb();
$obs->webrootpath = WEB_ROOT_PATH;
$setting = \S9\Cf::g('myapp.web_setting');
if ($setting){
	$obs->webSetting = $setting;
}
$obs->start(function($o){
});
