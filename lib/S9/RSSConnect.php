<?php

namespace S9;

use \S9\RSS\RSSRdr;

class RSSConnect{
	static $connectkey = "rss.setting";
	static $instance = null;
	
	static function getRss($target){
		$setting = Cf::g(self::$connectkey);
		if (!$setting || !isset($setting['cache']) || !isset($setting['sites']) || !isset($setting['sites'][$target])) return null;
		$cachesetting = $setting['cache'];
		$url = $setting['sites'][$target];
		if (is_array($url)){
			$url = $url['url'];
		}

		if (!self::$instance){
			self::$instance = new RSSRdr();
		}
		return self::$instance->getRss($target, $url, $cachesetting);
	}
}