<?php
namespace S9MyTmpl;

function mytmpl_plugin_htmlescape($val){
	return htmlspecialchars((string)$val, ENT_QUOTES);
}
function mytmpl_plugin_urlescape($val){
	return urlencode((string)$val);
}

function mytmpl_plugin_jsescape($val){
	$val = str_replace(array("\'", "\"", "\r", "\n"), array("\\\'", "\\\"", "\\r", "\\n"), (string)$val);
	return $val;
}
function mytmpl_plugin_json($val){
	return json_encode($val);
}

function mytmpl_plugin_nl2br($val){
	return nl2br((string)$val);
}

function mytmpl_plugin_date_y($val){
	return (string)substr((string)$val, 0, 4);
}
function mytmpl_plugin_date_m($val){
	return (string)substr((string)$val, 4, 2);
}
function mytmpl_plugin_date_d($val){
	return (string)substr((string)$val, 6, 2);
}
function mytmpl_plugin_ml($val, $config=array()){
	if (!isset($config['ml_convert'])) return $val;
	$lang = null;
	if (isset($config['ml_lang'])) $lang = $config['ml_lang'];
	return call_user_func_array($config['ml_convert'], array($val, array(), array(), $lang));
}

?>