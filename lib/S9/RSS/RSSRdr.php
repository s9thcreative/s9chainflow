<?php
namespace S9\RSS;

class RSSRdr{


	function __construct(){
	}

	function getRSS($target, $url, $cachesetting){
		$cacheroot = $cachesetting['root'];
		$cacheexp = $cachesetting['expire'];
		$usecache = false;
		$src = null;
		if (!$cacheexp) $cacheexp = 60*60;
		$cachefile = null;
		if (is_dir($cacheroot)){
			$cachefile = $cacheroot.'/'.$target.'.rss';
			if (is_readable($cachefile)){
				$stat = stat($cachefile);
				if ($stat['mtime'] > time()-$cacheexp){
					$src = file_get_contents($cachefile);
					$usecache = true;
				}
			}
		}

		if (!$usecache){
			$res = $this->_wget($url);
			if (!$res){
				return false;
			}
			$src = $res;
			if ($cachefile){
				file_put_contents($cachefile, $src);
			}
		}


		$xml = simplexml_load_string($src);
		$chn = $xml->channel;
		$resdata = array();
		$resdata['title'] = (string)($chn->title);
		$resdata['description'] = (string)($chn->description);
		$resdata['link'] = (string)($chn->link);
		$resdata['items'] = array();
		$ilist = $chn->item;
		foreach($ilist as $itm){
			$idata = array(
				'title'=>(string)($itm->title),
				'link'=>(string)($itm->link),
				'pubDate'=>(string)($itm->pubDate),
				'publish_date'=>date('YmdHis', strtotime((string)($itm->pubDate))),
				'description'=>(string)($itm->description),
			);
			$resdata['items'][] = $idata;
		}
		return $resdata;
	}

	function _wget($url){
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($curl);
		return $res;
	}

}