<?php
defined('T_VERSION') || die();
error_reporting(0);
error_reporting(E_ERROR);
ini_set("display_errors","Off");
date_default_timezone_set('Asia/Shanghai');
function ob_gzip_contents()
{
	$level = $_SERVER['cfg_gzip_num'];
	if (!$level || !extension_loaded('zlib')) {
		return;
	}
	if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && !ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler')) {
		$out = ob_get_contents();
		ob_clean();
		if (!function_exists('ob_gzhandler')) {
			ob_start('ob_gzhandler');
		} else if (function_exists('gzencode')) {
			$out = gzencode($out, $level);
			header('Content-Encoding: gzip');
			header('Content-Length: ' . strlen($out));
			header('Vary: Accept-Encoding');
		}
		echo $out;
	}
}

function HTTP_HOST($withPort = false)
{
	$_ = empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_ = explode(':', $_);
	if ($withPort) {
		if (!isset($_[1])) {
			$_[1] = $_SERVER['SERVER_PORT'];
		}
		if ($_[1] == '80') {
			return $_[0];
		} else {
			return implode(':', $_);
		}
	} else {
		return $_[0];
	}
}

function REQUEST_URI($withPort = false)
{
	$url = 'http://';
	$url .= HTTP_HOST($withPort);
	$url .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
	return $url;
}

function cache_write($file, $value)
{
	file_put_contents($file, '<?php exit;?>' . serialize($value));
}

function cache_writestr($file, $value)
{
	file_put_contents($file, $value);
}

function getdirname($str)
{
	return str_replace(':', '_', $str);
}

function cache_read($file)
{
	return @unserialize(substr(file_get_contents($file), 13));
}

function pics_file($file, $value)
{
	file_put_contents($file, "<?php\r\n#图片缓存 模版文件\r\ndefined('T_VERSION') || die();\r\n# 防止数据被非法拷贝\r\n?>\r\n" . $value);
}

function del_blank_line($str)
{
	$str = trim(str_replace("\r\n", "\n", $str));
	$str = preg_replace("|\n\n*|", "\n", $str);
	return $str;
}

function check_num($targetArr)
{
	$map = array('a', 'b', 'c', 'd');
	$num = count($targetArr);
	foreach ($targetArr as $key => $val) {
		foreach ($map as $k => $v) {
			if ($k == $key && is_numeric($val[strlen($val) - 1])) {
				$targetArr[$key] = $val . $v;
			}
		}
	}
	return $targetArr;
}

function get_lines_by_php($file)
{
	$txt = file_get_contents($file);
	$_ = strpos($txt, '?>');
	if ($_) {
		$txt = substr($txt, $_ + 2);
	}
	$lines = array();
	$txt = explode("\n", $txt);
	foreach ($txt as $_) {
		$_ = trim($_);
		if ($_ !== '') {
			$lines[] = $_;
		}
	}
	return $lines;
}

function loadPics()
{
	if (!isset($_SERVER['cfg_pics'])) {
		if (file_exists("cache/web/pics.php")) {
			$_SERVER['cfg_pics'] = get_lines_by_php("cache/web/pics.php");
		} else {
			$_SERVER['cfg_pics'] = glob('Moban/Pics/*.{jpg,gif,png,jpeg}', GLOB_BRACE);
			pics_file("cache/web/pics.php", implode("\r\n", $_SERVER['cfg_pics']));
		}
	}
}

function py($str, $ishead = 0, $isclose = 1)
{
	static $pinyins;
	$restr = '';
	$str = trim($str);
	$slen = strlen($str);
	if ($slen < 2) {
		return $str;
	}
	if (count($pinyins) == 0) {
		$pinyins = include "seodj/pysj.php";
	}
	for ($i = 0; $i < $slen; $i++) {
		if (ord($str[$i]) > 0x80) {
			$c = $str[$i] . $str[$i + 1];
			$i++;
			if (isset($pinyins[$c])) {
				if ($ishead == 0) {
					$restr .= $pinyins[$c];
				} else {
					$restr .= $pinyins[$c][0];
				}
			} else {
				$restr .= "";
			}
		} else if (preg_match("/[a-z0-9]/i", $str[$i])) {
			$restr .= $str[$i];
		} else {
			$restr .= "";
		}
	}
	if ($isclose == 0) {
		unset($pinyins);
	}
	return $restr;
}

function weburl($host, $type, $arg1 = false, $arg2 = false, $arg3 = false)
{
	if (!isset($_SERVER['url'][$type])) {
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 3600');
		die('<div style="text-align:center;"><h2><font color="red">URL伪静态错误，[' . $type . ']</font></h2></div>');
	}
	$url = $_SERVER['url'][$type];
	switch ($type) {
		case 'list':
			if (!$arg2) {
				$arg2 = 'list';
			}
			$url = str_replace('{tag}', $arg1 + 1, $url);
			$url = str_replace('{key}', $arg2, $url);
			break;
		case 'read':
			$url = str_replace('{id}', $arg1, $url);
			break;
		case 'maps':
			$url = str_replace('{page}', $arg1, $url);
			break;
		case 'temp':
			$url = rtrim($url, '/');
			$url = str_replace('{id}', $arg1, $url);
			break;
		case 'pags':
			$url = str_replace('{tag}', $arg1 + 1, $url);
			$url = str_replace('{key}', $arg2, $url);
			$url = str_replace('{page}', $arg3, $url);
			break;
		default:
			break;
	}
	$url = 'http://' . $host . $url;
	return $url;
}

function send_http_status($code)
{
	static $_status = array(200 => 'OK', 301 => 'Moved Permanently', 302 => 'Moved Temporarily', 400 => 'Bad Request', 403 => 'Forbidden', 404 => 'Not Found', 500 => 'Internal Server Error', 503 => 'Service Unavailable',);
	if (isset($_status[$code])) {
		header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
		header('Status:' . $code . ' ' . $_status[$code]);
	}
}

abstract class site_seo
{
	public $site;
	public $muban;
	public $moban;
	public $expires;
	public $moban_id;
	public $cache_file;

	final function __construct($site)
	{
		$this->site = $site;
		$file = T_PATH . 'cache/' . getdirname($this->site->host) . '/' . $this->site->_CLASS_;
		$this->site->id && $file .= '_' . $this->site->id;
		$this->site->tag && $file .= '_' . $this->site->tag;
		$file .= '.php';
		$this->cache_file = $file;
		if (empty($this->moban_id)) {
			$this->moban_id = $this->site->host_id % $_SERVER['cfg_Temp_num'] + 0;
		}
		if (!$_SERVER['cfg_mode_num'] && !empty($this->expires) && $this->expires) {
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + $this->expires));
			header("Cache-Control: max-age=" . $this->expires);
		}
	}

	final function _parse_cache(&$code)
	{
		$this->parse_cache($code);
		if (strpos($code, '流量侠_动态时间')) {
			$code = str_replace('{流量侠_动态时间0}', date('Y-m-d'), $code);
			$code = str_replace('{流量侠_动态时间1}', date('Y-m-d', strtotime('-1 day')), $code);
			$code = str_replace('{流量侠_动态时间2}', date('Y-m-d', strtotime('-2 day')), $code);
			$code = str_replace('{流量侠_动态时间3}', date('Y-m-d', strtotime('-3 day')), $code);
			$code = str_replace('{流量侠_动态时间4}', date('Y-m-d', strtotime('-4 day')), $code);
			$code = str_replace('{流量侠_动态时间5}', date('Y-m-d', strtotime('-5 day')), $code);
			$code = str_replace('{流量侠_动态时间6}', date('Y-m-d', strtotime('-6 day')), $code);
			$code = str_replace('{流量侠_动态时间7}', date('Y-m-d', strtotime('-7 day')), $code);
			$code = str_replace('{流量侠_动态时间8}', date('Y-m-d', strtotime('-8 day')), $code);
			$code = str_replace('{流量侠_动态时间9}', date('Y-m-d', strtotime('-9 day')), $code);
		}
	}

	final function _parse_moban(&$code)
	{
		$this->parse_moban($code);
		
		function _getRandwebstime($match)
		{
			return date("Y-m-d H:i:s", strtotime("-" . $match[1] . " day", time() + rand(0, 10000)));
		}

		function _getRandwebsnumb($match)
		{
			$left = str_pad('1', $match[1], '0');
			$right = str_pad('9', $match[1], '0');
			return rand($left, $right);
		}

		$code = preg_replace_callback('/{流量侠_发布时间(\d+)}/', '_getRandwebstime', $code);
		$code = preg_replace_callback('/{流量侠_字符数字(\d+)}/', '_getRandwebsnumb', $code);
	}

	function load()
	{
		$_start = microtime(true);
		//echo "begin to load moban...";
		if ($_SERVER['cfg_mode_num']) {
			$php = false;
		} else {
			$php = cache_read($this->cache_file);
		}
		//echo "read info " . $_SERVER['cfg_mode_num'] . " ..." .$php;
		//var_dump($php);
		if (!$php) {
			if ($this->muban) {
				$php = T_PATH . 'Moban/temp/' . $this->muban;
				$php = @php_strip_whitespace($php);
				if ($php == '') {
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 3600');
					die('<div style="text-align:center;"><h2><font color="red">模板文件丢失[/Moban/Temp/' . $this->muban . ']</font></h2></div>');
				}
				$php = trim($php);
				$php = str_replace('{流量侠_模板目录}', 'http://' . $this->site->host . '/Moban/Temp/404.php', $php);
				$this->_parse_moban($php);
				cache_write($this->cache_file, $php);
				// echo "\n got the first muban";
			} else {
				$php = '';
				// echo "\n failed to get the first muban";
			}
		}
		if (!$php) {
			if ($this->moban) {
				$php = T_PATH . 'Moban/' . $this->moban_id . '/' . $this->moban;
				$php = @php_strip_whitespace($php);	
				if ($php == '') {
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 3600');
					die('<div style="text-align:center;"><h2><font color="red">模板文件丢失[/Moban/' . $this->moban_id . '/' . $this->moban . ']</font></h2></div>');
				}
				$php = trim($php);
				$php = str_replace('{流量侠_模板目录}', weburl($this->site->host, 'temp', $this->moban_id), $php);
				// echo "\n ,, cache file: " .$this->cache_file;
				// echo "\n...begin to parse moban";
				$this->_parse_moban($php);
				// echo "\n...end to parse moban";
				cache_write($this->cache_file, $php);
				// echo "\n got the second muban";
			} else {
				$php = '';
				// echo "\n failed to get the second muban";
			}
		}
		$this->_parse_cache($php);
		if (strtolower(substr($php, 0, 5)) == '<?php') {
			$php = substr($php, 5);
		} else if (substr($php, 0, 2) == '<?') {
			$php = substr($php, 2);
		} else {
			$php = '?>' . $php;
		}
		if (substr($php, -2) == '?>') {
			$php = substr($php, 0, -2);
		}
		$php = trim($php);
		$_time = microtime(true) - $_start;
		$_time = sprintf('%.2f', $_time * 1000) . 'ms';
		$php = str_replace('{流量侠_运行时间}', $_time, $php);
		eval($php);
		ob_gzip_contents();
		exit;
	}

	function parse_cache(&$code)
	{
	}

	function parse_moban(&$code)
	{
	}
}

class site_list extends site_seo
{
	public $moban = 'list.php';

	function load()
	{
		parent:: load();
	}

	function parse_cache(&$code)
	{
		$data = $this->getPage();
		$code = str_replace('{流量侠_栏目列表}', $data['list'], $code);
		$code = str_replace('{流量侠_栏目阅读}', $data['read'], $code);
		$code = str_replace('{流量侠_栏目分页}', $data['page'], $code);
		$code = str_replace('{流量侠_栏目页数}', $data['paxe'], $code);
		$code = str_replace('{流量侠_栏目网址}', $data['pawz'], $code);
		
		$code = str_replace('{流量侠_列表动态标题1}', $this->getRandomList(1), $code);
		$code = str_replace('{流量侠_列表动态标题2}', $this->getRandomList(2), $code);
		$code = str_replace('{流量侠_列表动态标题3}', $this->getRandomList(3), $code);
		$code = str_replace('{流量侠_列表动态标题4}', $this->getRandomList(4), $code);		

		$code = preg_replace_callback('/{流量侠_当前网址}/', array($this, 'getRandomUrl'), $code);			
		$ca_title = $this->getRandomTitle();
		// $code = str_replace('{流量侠_当前标题}', $this->title, $code);
		$code = str_replace('{流量侠_当前标题}', $ca_title, $code);
		$code = str_replace('{流量侠_网站随机标题}', $this->getMyRandTitle(), $code);		
	}	

	function parse_moban(&$code)   //site_list
	{
		$code = str_replace('{流量侠_网站标题}', $this->site->title, $code);
		$code = str_replace('{流量侠_网站域名}', 'http://' . $this->site->host . '/', $code);
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$listwebs = $this->site->getListWord();
		if (!isset($webs['link'][$this->site->tag])) {
			$this->site->call_404();
		}
		$web['link'] = check_num($webs['link']);
		$webs['pysj'] = check_num($webs['pysj']);
		$title = $web['link'][$this->site->tag];
		
		/*
		// disable this check
		if (!empty($_GET['key']) && py($title, 1) != $_GET['key']) {
			echo "site_list 111111111 404";
			$this->site->call_404();
		}
		*/
		$url = weburl($this->site->host, 'list', $this->site->tag, $webs['pysj'][$this->site->tag]);
		$title = $webs['link'][$this->site->tag];
	
	  $code = str_replace('{流量侠_上级标题}', $title, $code);
	  $keys = $this->site->getSiteKeys($this->site->host);	
	  $j = mt_rand(0, count($keys) - 1);
		$title = $keys[$j];
		$cur_url = 'http://' . $this->site->host . '/' . py($title, 1) . '_1/';
		
		$link = $webs['link'];
		$pysj = $webs['pysj'];
		$listwebs = $this->site->getListWord();
		$listlink = $listwebs['link'];   
		$listpysj = $listwebs['pysj'];
		if (strpos($code, '流量侠_栏目标题')) {
			$code = str_replace('{流量侠_栏目标题1}', $link[0], $code);
			$code = str_replace('{流量侠_栏目标题2}', $link[1], $code);
			$code = str_replace('{流量侠_栏目标题3}', $link[2], $code);
			$code = str_replace('{流量侠_栏目标题4}', $link[3], $code);
		}
		if (strpos($code, '流量侠_栏目网址')) {
			$code = str_replace('{流量侠_栏目网址1}', weburl($urls, 'list', 0, $pysj[0]), $code);
			$code = str_replace('{流量侠_栏目网址2}', weburl($urls, 'list', 1, $pysj[1]), $code);
			$code = str_replace('{流量侠_栏目网址3}', weburl($urls, 'list', 2, $pysj[2]), $code);
			$code = str_replace('{流量侠_栏目网址4}', weburl($urls, 'list', 3, $pysj[3]), $code);
		}
		
		if (strpos($code, '流量侠_列表栏目标题')) {
			$code = str_replace('{流量侠_列表栏目标题1}', $listlink[0], $code);
			$code = str_replace('{流量侠_列表栏目标题2}', $listlink[1], $code);
			$code = str_replace('{流量侠_列表栏目标题3}', $listlink[2], $code);
			$code = str_replace('{流量侠_列表栏目标题4}', $listlink[3], $code);
		}
		if (strpos($code, '流量侠_列表栏目网址')) {
			$code = str_replace('{流量侠_列表栏目网址1}', weburl($urls, 'list', 0, $listpysj[0]), $code);
			$code = str_replace('{流量侠_列表栏目网址2}', weburl($urls, 'list', 1, $listpysj[1]), $code);
			$code = str_replace('{流量侠_列表栏目网址3}', weburl($urls, 'list', 2, $listpysj[2]), $code);
			$code = str_replace('{流量侠_列表栏目网址4}', weburl($urls, 'list', 3, $listpysj[3]), $code);
		}
		$code = preg_replace_callback('/{流量侠_网站副词}/', array($this, 'getRandomWord'), $code);
	}

	function getPage()
	{
		$page = isset($_GET['page']) ? $_GET['page'] : 0;
		$paxe = isset($_GET['page']) ? $_GET['page'] : 0;
		$pawz = isset($_GET['page']) ? $_GET['page'] : 0;
		$keys = $this->site->getSiteKeys($this->site->host);
		$all_nums = count($keys);
		$a_page_nums = $_SERVER['cfg_list_num'];
		$b_page_nums = $_SERVER['cfg_read_num'];
		$page = $this->_get_page($page, $all_nums, $a_page_nums);
		$paxe = $this->_get_page($paxe, $all_nums, $a_page_nums);
		$pawz = $this->_get_page($pawz, $all_nums, $a_page_nums);
		$data = array();
		$data['page'] = $page['string'];
		$data['paxe'] = $paxe['string1'];
		$data['pawz'] = $pawz['string2'];
		$list = array();
		foreach ($keys as $i => $v) {
			// $url = weburl($this->site->host, 'read', $i);
			if (10 >= mb_strlen($v, 'GBK')) {				
				$j = mt_rand(0, count($keys) - 1);				
				$v .= mb_substr($keys[$j], 0, 5, 'GBK');
			} 
			else if (16 < mb_strlen($v, 'GBK')) {
				$v = mb_substr($v, 0, 16, 'GBK');
			}
			// $rtmp = mt_rand(0, 98);
			$url = $url = 'http://' . $this->site->host . '/' . py($v, 1) . '_' . $i . '.html';
			$list[] = '<li><a href="' . $url . '" target="_blank">' . $v . '</a></li>';
		}
		$max = $page['first'] + $a_page_nums;
		if ($max > $all_nums) {
			$max = $all_nums;
		}
		$list = array_reverse($list);
		$data['list'] = array();
		for ($i = $page['first']; $i < $max; $i++) {
			$data['list'][] = $list[$i];
		}
		$data['list'] = implode("\r\n", $data['list']);
		$read = array();
		$list = substr($data['paxe'], 2, 1);
		if ($list == 1) {
			$Ad_list = array_slice($keys, 1, 22, true);
		}
		if ($list > 1) {
			$Ad_list = array_slice($keys, -($list * 22), 22, true);
		}
		$time = date('d', time());
		$time = substr($time, -2);
		foreach ($keys as $i => $v) {
			// $url = weburl($this->site->host, 'read', $i);			
			$url = $url = 'http://' . $this->site->host . '/' . py($v, 1) . '_' . $i . '.html';
			
			if (10 >= mb_strlen($v, 'GBK')) {				
				$jj = mt_rand(0, count($keys) - 1);				
				$v .= mb_substr($keys[$jj], 0, 5, 'GBK');
			} 
			else if (16 < mb_strlen($v, 'GBK')) {
				$v = mb_substr($v, 0, 16, 'GBK');
			}
			
			$j = mt_rand(0, 9);
			$time = "<?php  echo date('Y-m-d', strtotime('- $j day')); ?>";
			$read[] = '<li><span>' . $time . '</span><a href="' . $url . '" target="_blank">' . $v . '</a></li>';
		}
		$max = $b_page_nums;
		if ($max > $all_nums) {
			$max = $all_nums;
		}
		$data['read'] = array();
		for ($i = 0; $i < $max; $i++) {
			@$data['read'][] = $read[$i];
		}
		$data['read'] = implode("\r\n", array_reverse($data['read']));
		return $data;
	}

	function _get_page(&$thepage, $all_nums, $a_page_nums)
	{
		$arr = array();
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$arr['number'] = ceil($all_nums / $a_page_nums);
		if ($thepage == -1 || $thepage > $arr['number']) {
			if (!$arr['number']) {
				$arr['number'] = 1;
			}
			$thepage = $arr['number'];
		} else if ($thepage <= 0) {
			$thepage = 1;
		} else {
		}
		$arr['page'] = $thepage;
		$arr['first'] = ($thepage - 1) * $a_page_nums;
		if (!$arr['number']) {
			$arr['number'] = 1;
		}
		$arr['top'] = '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], 1) . '">首页</a>' . PHP_EOL;
		$arr['foot'] = '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $arr['number']) . '">尾页</a>' . PHP_EOL;
		if ($arr['number'] > 1 && $thepage + 1 <= $arr['number']) {
			$arr['down'] = '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $thepage + 1) . '">下页</a>' . PHP_EOL;
		} else {
			$arr['down'] = null;
		}
		if ($arr['number'] > 1 && $thepage - 1 >= 1) {
			$arr['up'] = '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $thepage - 1) . '">上页</a>' . PHP_EOL;
		} else {
			$arr['up'] = null;
		}
		if ($arr['number'] == 1) {
			$arr['string'] = '';
		} else if ($arr['number'] == 2) {
			if ($thepage == 1) {
				$arr['string'] = $arr['down'] . '';
			} elseif ($thepage == 2) {
				$arr['string'] = $arr['up'] . '';
			}
		} else if ($arr['number'] > 2) {
			if ($arr['up'] && $arr['down']) {
				$arr['string'] = $arr['down'] . '' . $arr['up'] . '' . $arr['top'] . '' . $arr['foot'] . '';
			} elseif ($arr['up']) {
				$arr['string'] = $arr['up'] . '' . $arr['top'] . '';
			} else {
				$arr['string'] = $arr['down'] . '' . $arr['foot'] . '';
			}
		} else {
			$arr['string'] = '';
		}
		$str = '';
		if ($thepage <= 5) {
			for ($i = 1; $i <= 9 AND $i <= $arr['number']; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i < 9 AND $i < $arr['number']) {
					$str .= '';
				}
			}
		} else if ($thepage > $arr['number'] - 4) {
			$i = $arr['number'] - 9;
			if ($i < 1) {
				$i = 1;
			}
			for (; $i <= $arr['number']; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i <> $arr['number']) {
					$str .= '';
				}
			}
		} else if ($thepage > 4) {
			for ($i = $thepage - 4; $i <= $thepage + 4; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i <> $thepage + 4) {
					$str .= '';
				}
			}
		}
		if ($arr['number'] >= 4) {
			$arr['string'] .= $str . '';
		}
		$arr['string'] .= '<a>第' . $thepage . '页</a><a>共' . $arr['number'] . '页</a><a>共' . $all_nums . '条</a><a>当前有(1/' . $a_page_nums . ')条信息</a>';
		$arr['string1'] = '第' . $thepage . '页';
		$arr['string2'] = '' . weburl($this->site->host, 'pags', $this->site->tag, $webs['pysj'][$this->site->tag], $thepage) . '' . PHP_EOL;
		return $arr;
	}
	
		
	private function getMyRandTitle()
	{
		$titles = $this->site->title;
		$ssaa = explode('_', $titles);
		$title = $ssaa[array_rand($ssaa)];		
		return $title;
	}
	
	private function getRandomWord()
	{
		$webs = $this->site->getSiteWord();
		return '' . $webs['link'][0] . '';
	}
	
	private function getRandomList($type)
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = mt_rand(0, count($keys) - 1);
		$url = 'http://' . $this->site->host . '/' . py($keys[$i], 1) . '_' . $type . '/';
		return '<a href="' . $url . '" target="_blank">' . $keys[$i] . '</a>';
	}
	
	private function getRandomTitle()
	{
	  $keys = $this->site->getSiteKeys($this->site->host);	
	  $j = mt_rand(0, count($keys) - 1);
		$title = $keys[$j];
		return $title;
	}	
	
	private function getRandomUrl()
	{
	  $keys = $this->site->getSiteKeys($this->site->host);	
	  $j = mt_rand(0, count($keys) - 1);
		$title = $keys[$j];
		$url = 'http://' . $this->site->host . '/' . py($title, 1) . '_1/';
		return $url;
	}	
}

class site_read extends site_seo
{
	public $moban = 'read.php';
	private $title;

	function load()
	{
		parent:: load();
	}

	function parse_cache(&$code)
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$max = count($keys) - 1;
		if ($this->site->id <= $max + 1) {
			$next = $this->site->id + 1 > $max ? 0 : $this->site->id + 1;
			$last = $this->site->id - 1 < 0 ? $max : $this->site->id - 1;
		} else {
			$next = 0;
			$last = $max;
		}
		$last_url =  'http://' . $this->site->host . '/' . py($keys[$last], 1) . '_' . $last . '.html';
		$last = '<a href="' . $last_url . '"  target="_blank">' . $keys[$last] . '</a>';
		$next_url =  'http://' . $this->site->host . '/' . py($keys[$next], 1) . '_' . $next . '.html';	
		$next = '<a href="' . $next_url . '"  target="_blank">' . $keys[$next] . '</a>';
		$code = str_replace('{流量侠_上一条}', $last, $code);
		$code = str_replace('{流量侠_下一条}', $next, $code);
	}

	function parse_moban(&$code)
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		if (!isset($keys[$this->site->id])) {
			$this->site->call_404();
		}		
		$code = str_replace('{流量侠_发布时间}', date('Y-m-d H:i:s'), $code);
		$code = str_replace('{流量侠_浏览次数}', rand(100000, 999999), $code);
		$code = str_replace('{流量侠_网站标题}', $this->site->title, $code);		
		$code = str_replace('{流量侠_网站随机标题}', $this->getMyRandTitle(), $code);		
		$code = str_replace('{流量侠_网站域名}', 'http://' . $this->site->host . '/', $code);
		$this->title = $keys[$this->site->id];
		$url = weburl($this->site->host, 'read', $this->site->id);
		$cur_url =  'http://' . $this->site->host . '/' . py($this->title, 1) . '_' . $this->site->id . '.html';
		$code = str_replace('{流量侠_当前网址}', $cur_url, $code);
		$code = str_replace('{流量侠_当前标题}', $this->title, $code);	
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$link = $webs['link'];
		$pysj = $webs['pysj'];
		$listwebs = $this->site->getListWord();
		$listlink = $listwebs['link'];
		$listpysj = $listwebs['pysj'];
		
		if (strpos($code, '流量侠_栏目标题')) {
			$code = str_replace('{流量侠_栏目标题1}', $link[0], $code);
			$code = str_replace('{流量侠_栏目标题2}', $link[1], $code);
			$code = str_replace('{流量侠_栏目标题3}', $link[2], $code);
			$code = str_replace('{流量侠_栏目标题4}', $link[3], $code);
		}
		if (strpos($code, '流量侠_栏目网址')) {
			$code = str_replace('{流量侠_栏目网址1}', weburl($urls, 'list', 0, $pysj[0]), $code);
			$code = str_replace('{流量侠_栏目网址2}', weburl($urls, 'list', 1, $pysj[1]), $code);
			$code = str_replace('{流量侠_栏目网址3}', weburl($urls, 'list', 2, $pysj[2]), $code);
			$code = str_replace('{流量侠_栏目网址4}', weburl($urls, 'list', 3, $pysj[3]), $code);
		}
		if (strpos($code, '流量侠_列表栏目标题')) {
			$code = str_replace('{流量侠_列表栏目标题1}', $listlink[0], $code);
			$code = str_replace('{流量侠_列表栏目标题2}', $listlink[1], $code);
			$code = str_replace('{流量侠_列表栏目标题3}', $listlink[2], $code);
			$code = str_replace('{流量侠_列表栏目标题4}', $listlink[3], $code);
		}
		if (strpos($code, '流量侠_列表栏目网址')) {
			$code = str_replace('{流量侠_列表栏目网址1}', weburl($urls, 'list', 0, $listpysj[0]), $code);
			$code = str_replace('{流量侠_列表栏目网址2}', weburl($urls, 'list', 1, $listpysj[1]), $code);
			$code = str_replace('{流量侠_列表栏目网址3}', weburl($urls, 'list', 2, $listpysj[2]), $code);
			$code = str_replace('{流量侠_列表栏目网址4}', weburl($urls, 'list', 3, $listpysj[3]), $code);
		}
		$code = preg_replace_callback('/{流量侠_当前栏目}/', array($this, 'getRandomPdao'), $code);
		$code = preg_replace_callback('/{流量侠_网站栏目}/', array($this, 'getRandomVaue'), $code);
		$code = preg_replace_callback('/{流量侠_文章内容}/', array($this, 'getRandomText'), $code);
		$code = preg_replace_callback('/{流量侠_网站内页}/', array($this, 'getRandomList'), $code);
		$code = preg_replace_callback('/{流量侠_网站图片}/', array($this, 'getRandomPic0'), $code);
		$code = preg_replace_callback('/{流量侠_网站图文}/', array($this, 'getRandomPic1'), $code);
		$code = preg_replace_callback('/{流量侠_站内轮链}/', array($this, 'getRandomLin1'), $code);
		$code = preg_replace_callback('/{流量侠_站外轮链}/', array($this, 'getRandomLin2'), $code);
	}		
		
	private function getMyRandTitle()
	{
		$titles = $this->site->title;
		$ssaa = explode('_', $titles);
		$title = $ssaa[array_rand($ssaa)];		
		return $title;
	}

	private function getRandomPdao()
	{
		$page = $this->site->id % 4;
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$keys = array('' . $webs['link'][0] . '', '' . $webs['link'][1] . '', '' . $webs['link'][2] . '', '' . $webs['link'][3] . '',);
		return '' . $keys[$page] . '';
	}

	private function getRandomVaue()
	{
		$page = $this->site->id % 4;
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$keys = array('' . $webs['link'][0] . '', '' . $webs['link'][1] . '', '' . $webs['link'][2] . '', '' . $webs['link'][3] . '',);
		$host = array('' . weburl($urls, 'list', 0, $webs['pysj'][0]) . '', '' . weburl($urls, 'list', 1, $webs['pysj'][1]) . '', '' . weburl($urls, 'list', 2, $webs['pysj'][2]) . '', '' . weburl($urls, 'list', 3, $webs['pysj'][3]) . '',);
		return '<a href="' . $host[$page] . '" target="_blank">' . $keys[$page] . '</a>';
	}

	private function getRandomText()
	{
		$news = array();
		if (!$news) {
			$num = sizeof(scandir("./text"));
			$num = ($num > 2) ? $num - 2 : 0;
			$suzi = mt_rand(1, $num);
			$news = get_lines_by_php('Text/Text_' . $suzi . '.php');
		}
		$webs_news = array();
		while (count($webs_news) < 6) {
			$i = array_rand($news);
			$v = $news[$i];
			unset($news[$i]);
			$webs_news[] = $v;
		}
		if (mt_rand(1, 2) == 1) {
			$text = '' . $webs_news[0] . ',' . $webs_news[1] . ',' . $webs_news[2] . ',' . $webs_news[3] . ',' . $webs_news[4] . ',' . $webs_news[5] . '。';
		} else {
			$text = '' . $webs_news[0] . ',' . $webs_news[1] . ',' . $webs_news[2] . ',' . $webs_news[3] . ',' . $webs_news[4] . ',' . $webs_news[5] . '！';
		}
		return $text;
	}

	private function getRandomList()
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = mt_rand(0, count($keys) - 1);
		$url = 'http://' . $this->site->host . '/' . py($keys[$i], 1) . "_" .$i . ".html";
		return '<a href="' . $url . '" target="_blank">' . $keys[$i] . '</a>';
	}

	private function getRandomPic0()
	{
		$keys = $this->site->getSiteKeys($this->site->host);		
		$this->title = $keys[$this->site->id];		
		
		loadPics();
		$url = 'http://' . $this->site->host . '/';
		$i = rand(0, count($_SERVER['cfg_pics']) - 1);
		if (!isset($_SERVER['cfg_pics'][$i])) {
			return '网站图片库为空，请检测！';
		}
		$keys = $this->site->getSiteKeys($this->site->host);
		$images = $_SERVER['cfg_pics'][$i];
		$j = mt_rand(0, count($keys) - 1);
		$num = sizeof(scandir("./text"));
		$num = ($num > 2) ? $num - 2 : 0;
		$suzi = mt_rand(1, $num);
		$text = get_lines_by_php('Text/Text_' . $suzi . '.php');
		$i = mt_rand(0, count($text) - 1);
		$wzbt = $text[$i];
		if (mt_rand(1, 2) == 1) {
			// return '<img src="' . $url . '' . $images . '" alt="' . $wzbt . '' . $keys[$j] . '" />';
			return '<img src="' . $url . '' . $images . '" alt="' . $this->title . '" />';
		} else {
			// return '<img src="' . $url . '' . $images . '" alt="' . $keys[$j] . '' . $wzbt . '" />';
			return '<img src="' . $url . '' . $images . '" alt="' . $this->title . '" />';
		}
	}

	private function getRandomPic1()
	{
		loadPics();
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = rand(0, count($_SERVER['cfg_pics']) - 1);
		if (!isset($_SERVER['cfg_pics'][$i])) {
			return '网站图片库为空，请检测！';
		}
		$images = $_SERVER['cfg_pics'][$i];
		$j = mt_rand(0, count($keys) - 1);
		$url1 = 'http://' . $this->site->host . '/';
		$url2 = weburl($this->site->host, 'read', $j);
		$num = sizeof(scandir("./text"));
		$num = ($num > 2) ? $num - 2 : 0;
		$suzi = mt_rand(1, $num);
		$text = get_lines_by_php('Text/Text_' . $suzi . '.php');
		$i = mt_rand(0, count($text) - 1);
		$wzbt = $text[$i];
		if (mt_rand(1, 2) == 1) {
			$title = $keys[$j];
			$url2 = 'http://' . $this->site->host . '/' . py($title, 1) . '_' .$j . '.html';
			return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $wzbt . '' . $keys[$j] . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $wzbt . '' . $keys[$j] . '</a>';
		} else {
			$title = $keys[$j];
			$url2 = 'http://' . $this->site->host . '/' . py($title, 1) . '_' .$j . '.html';
			return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $keys[$j] . '' . $wzbt . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $keys[$j] . '' . $wzbt . '</a>';
		}
	}

	private function getRandomLin1()
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$page = $this->site->id % 4;
		$i = mt_rand(0, count($keys) - 1);
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$url = weburl($this->site->host, 'read', $i);
		$url = "http://" . $this->site->host . "/" . py($keys[$i], 1) . "_" . $i . ".html";
		$list = array('' . $webs['link'][0] . '', '' . $webs['link'][1] . '', '' . $webs['link'][2] . '', '' . $webs['link'][3] . '',);
		$urls = $this->site->host;
		$urls = array('' . weburl($urls, 'list', 0, $webs['pysj'][0]) . '', '' . weburl($urls, 'list', 1, $webs['pysj'][1]) . '', '' . weburl($urls, 'list', 2, $webs['pysj'][2]) . '', '' . weburl($urls, 'list', 3, $webs['pysj'][3]) . '',);
		if (mt_rand(0, 10) <= 1) {
			return '<a href="' . $urls[$page] . '" target="_blank">' . $list[$page] . '</a>';
		}
		/*
		if (mt_rand(0, 10) <= 3) {
			$keys = $this->site->title;
			return '<a href="http://' . $this->site->host . '/" target="_blank">' . $keys . '</a>';
		}
		*/
		return '<a href="' . $url . '" target="_blank">' . $keys[$i] . '</a>';
	}

	private function getRandomLin2()
	{
		$sites = $this->site->getSites();
		$host = array_rand($sites);
		$title = $sites[$host];
		$webs = $this->site->getSiteWord();
		$word = $webs['link'][0];
		/*
		if (mt_rand(0, 10) <= 1) {
			return '<a href="http://' . $this->site->host . '/" target="_blank">' . $word . '</a>';
		}
		*/
		if (mt_rand(0, 10) <= 3) {
			return '<a href="http://' . $host . '/" target="_blank">' . $title . '</a>';
		}
		$keys = $this->site->getSiteKeys($host);
		$j = mt_rand(0, count($keys) - 1);
		$url = weburl($host, 'read', $j);
		$url = "http://" . $host . "/" . py($keys[$j], 1) .  "_" . $j . ".html";
		return '<a href="' . $url . '" target="_blank">' . $keys[$j] . '</a>';
	}
}

class site_maps extends site_seo
{
	public $moban = 'map.php';

	function load()
	{
		if (isset($_GET['type']) && $_GET['type'] == 'xml') {
			$this->parse_xml();
		} else {
			parent:: load();
		}
	}

	function parse_xml()
	{
		 $file = T_PATH .'/' . $this->site->host . 'sitemap.xml';
		 echo "sitemap file: " . $file;
     $data = cache_read($file);
		 if (!$data) {
			echo "data is null".
			$xml = '';
			$xml .= '<?xml version="1.0" encoding="UTF-8"?>';
		  $xml .= "\r\n";
		  $xml .= '<urlset>';
		  $xml .= $this->parse_xml_url('http://' . $this->site->host);
		  $keys = $this->site->getSiteKeys($this->site->host);
		  // $keys = array_rand($keys, 99);  
		  foreach ($keys as $i => $v) {			
		  	$url = 'http://' . $this->site->host . '/' . py($v, 1) . '_' . $i . '.html';
		  	$xml .= $this->parse_xml_url($url);
		  	// $xml .= $this->parse_xml_url(weburl($this->site->host, 'read', $v));
		  }
		  $xml .= $this->parse_xml_url('http://' . $this->site->host . '/sitemap.html');		
		  $xml .= "\r\n";
		  $xml .= '</urlset>';
		  $xml = str_replace("\t", '', $xml);
		  $data = $xml;	
			
			cache_writestr($file, $xml);
		 }
		 
		 echo $data;
		 exit;
	}

	function parse_xml_url($loc, $lastmod = false, $changefreq = 'daily', $priority = '1.0')
	{
		if ($lastmod === false) {
			$lastmod = date('Y-m-d');
		}
		return '
		<url>
		<loc>' . $loc . '</loc>
		<lastmod>' . $lastmod . '</lastmod>
		<changefreq>' . $changefreq . '</changefreq>
		<priority>' . $priority . '</priority>
		</url>';
	}

	function parse_cache(&$code)
	{
		$data = $this->getPage();
		$code = str_replace('{流量侠_地图列表}', $data['list'], $code);
		$code = str_replace('{流量侠_地图分页}', $data['page'], $code);
		$code = str_replace('{流量侠_地图页数}', $data['paxe'], $code);
		$code = str_replace('{流量侠_地图网址}', $data['pawz'], $code);
	}

	function parse_moban(&$code)
	{
		$code = str_replace('{流量侠_发布时间}', date('Y-m-d H:i:s'), $code);
		$code = str_replace('{流量侠_浏览次数}', rand(100000, 999999), $code);
		$code = str_replace('{流量侠_网站标题}', $this->site->title, $code);
		$code = str_replace('{流量侠_网站域名}', 'http://' . $this->site->host . '/', $code);
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$link = $webs['link'];
		$pysj = $webs['pysj'];
		$listwebs = $this->site->getListWord();
		$listlink = $listwebs['link'];
		$listpysj = $listwebs['pysj'];
		if (strpos($code, '流量侠_栏目标题')) {
			$code = str_replace('{流量侠_栏目标题1}', $link[0], $code);
			$code = str_replace('{流量侠_栏目标题2}', $link[1], $code);
			$code = str_replace('{流量侠_栏目标题3}', $link[2], $code);
			$code = str_replace('{流量侠_栏目标题4}', $link[3], $code);
		}
		if (strpos($code, '流量侠_栏目网址')) {
			$code = str_replace('{流量侠_栏目网址1}', weburl($urls, 'list', 0, $pysj[0]), $code);
			$code = str_replace('{流量侠_栏目网址2}', weburl($urls, 'list', 1, $pysj[1]), $code);
			$code = str_replace('{流量侠_栏目网址3}', weburl($urls, 'list', 2, $pysj[2]), $code);
			$code = str_replace('{流量侠_栏目网址4}', weburl($urls, 'list', 3, $pysj[3]), $code);
		}
		
		if (strpos($code, '流量侠_列表栏目标题')) {
			$code = str_replace('{流量侠_列表栏目标题1}', $listlink[0], $code);
			$code = str_replace('{流量侠_列表栏目标题2}', $listlink[1], $code);
			$code = str_replace('{流量侠_列表栏目标题3}', $listlink[2], $code);
			$code = str_replace('{流量侠_列表栏目标题4}', $listlink[3], $code);
		}
		if (strpos($code, '流量侠_列表栏目网址')) {
			$code = str_replace('{流量侠_列表栏目网址1}', weburl($urls, 'list', 0, $listpysj[0]), $code);
			$code = str_replace('{流量侠_列表栏目网址2}', weburl($urls, 'list', 1, $listpysj[1]), $code);
			$code = str_replace('{流量侠_列表栏目网址3}', weburl($urls, 'list', 2, $listpysj[2]), $code);
			$code = str_replace('{流量侠_列表栏目网址4}', weburl($urls, 'list', 3, $listpysj[3]), $code);
		}
		$code = preg_replace_callback('/{流量侠_网站副词}/', array($this, 'getRandomWord'), $code);
	}

	function getPage()
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$page = isset($_GET['page']) ? $_GET['page'] : 0;
		$paxe = isset($_GET['page']) ? $_GET['page'] : 0;
		$pawz = isset($_GET['page']) ? $_GET['page'] : 0;
		$all_nums = count($keys);
		$a_page_nums = $_SERVER['cfg_page_num'];
		$page = $this->_get_page($page, $all_nums, $a_page_nums);
		$paxe = $this->_get_page($paxe, $all_nums, $a_page_nums);
		$pawz = $this->_get_page($pawz, $all_nums, $a_page_nums);
		$data = array();
		$data['page'] = $page['string'];
		$data['paxe'] = $paxe['string1'];
		$data['pawz'] = $pawz['string2'];
		$list = array();
		foreach ($keys as $i => $v) {
			// $url = weburl($this->site->host, 'read', $i);
			$url = 'http://' . $this->site->host . '/' . py($v, 1) . "_" . $i . ".html";
			$list[] = '<li><a href="' . $url . '" target="_blank">' . $v . '</a></li>';
		}
		$max = $page['first'] + $a_page_nums;
		if ($max > $all_nums) {
			$max = $all_nums;
		}
		$list = array_reverse($list);
		$data['list'] = array();
		for ($i = $page['first']; $i < $max; $i++) {
			$data['list'][] = $list[$i];
		}
		$data['list'] = implode("\r\n", ($data['list']));
		return $data;
	}

	function _get_page(&$thepage, $all_nums, $a_page_nums)
	{
		$arr = array();
		$arr['number'] = ceil($all_nums / $a_page_nums);
		if ($thepage == -1 || $thepage > $arr['number']) {
			if (!$arr['number']) {
				$arr['number'] = 1;
			}
			$thepage = $arr['number'];
		} elseif ($thepage <= 0) {
			$thepage = 1;
		} else {
		}
		$arr['page'] = $thepage;
		$arr['first'] = ($thepage - 1) * $a_page_nums;
		if (!$arr['number']) {
			$arr['number'] = 1;
		}
		$arr['top'] = '<a href="' . weburl($this->site->host, 'maps', 1) . '">首页</a>' . PHP_EOL;
		$arr['foot'] = '<a href="' . weburl($this->site->host, 'maps', $arr['number']) . '">尾页</a>' . PHP_EOL;
		if ($arr['number'] > 1 && $thepage + 1 <= $arr['number']) {
			$arr['down'] = '<a href="' . weburl($this->site->host, 'maps', $thepage + 1) . '">下页</a>' . PHP_EOL;
		} else {
			$arr['down'] = null;
		}
		if ($arr['number'] > 1 && $thepage - 1 >= 1) {
			$arr['up'] = '<a href="' . weburl($this->site->host, 'maps', $thepage - 1) . '">上页</a>' . PHP_EOL;
		} else {
			$arr['up'] = null;
		}
		if ($arr['number'] == 1) {
			$arr['string'] = '';
		} else if ($arr['number'] == 2) {
			if ($thepage == 1) {
				$arr['string'] = $arr['down'] . '';
			} elseif ($thepage == 2) {
				$arr['string'] = $arr['up'] . '';
			}
		} else if ($arr['number'] > 2) {
			if ($arr['up'] && $arr['down']) {
				$arr['string'] = $arr['down'] . '' . $arr['up'] . '' . $arr['top'] . '' . $arr['foot'] . '';
			} else if ($arr['up']) {
				$arr['string'] = $arr['up'] . '' . $arr['top'] . '';
			} else {
				$arr['string'] = $arr['down'] . '' . $arr['foot'] . '';
			}
		} else {
			$arr['string'] = '';
		}
		$str = '';
		if ($thepage <= 5) {
			for ($i = 1; $i <= 9 AND $i <= $arr['number']; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'maps', $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i < 9 AND $i < $arr['number']) {
					$str .= '';
				}
			}
		} else if ($thepage > $arr['number'] - 4) {
			$i = $arr['number'] - 9;
			if ($i < 1) {
				$i = 1;
			}
			for (; $i <= $arr['number']; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'maps', $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i <> $arr['number']) {
					$str .= '';
				}
			}
		} else if ($thepage > 4) {
			for ($i = $thepage - 4; $i <= $thepage + 4; $i++) {
				if ($i == $thepage) {
					$str .= '<span class="page_now">' . $i . '</span>' . PHP_EOL;
				} else {
					$str .= '<a href="' . weburl($this->site->host, 'maps', $i) . '">' . $i . '</a>' . PHP_EOL;
				}
				if ($i <> $thepage + 4) {
					$str .= '';
				}
			}
		}
		if ($arr['number'] >= 4) {
			$arr['string'] .= $str . '';
		}
		$arr['string'] .= '<a>第' . $thepage . '页</a><a>共' . $arr['number'] . '页</a><a>共' . $all_nums . '条</a><a>当前有(1/' . $a_page_nums . ')条信息</a>';
		$arr['string1'] = '第' . $thepage . '页';
		$arr['string2'] = '' . weburl($this->site->host, 'maps', $thepage) . '' . PHP_EOL;
		return $arr;
	}

	private function getRandomWord()
	{
		$webs = $this->site->getSiteWord();
		return '' . $webs['link'][0] . '';
	}
}

abstract class site_seos
{
	public $site;
	public $moban;
	public $expires;
	public $moban_id;
	public $cache_file;

	final function __construct($site)
	{
		$this->site = $site;
		$file = T_PATH . 'cache/' . getdirname($this->site->host) . '/' . $this->site->_CLASS_;
		$this->site->id && $file .= '_' . $this->site->id;
		$this->site->tag && $file .= '_' . $this->site->tag;
		$file .= '.php';
		$this->cache_file = $file;
		if (empty($this->moban_id)) {
			$this->moban_id = $this->site->host_id % $_SERVER['cfg_Temp_num'] + 0;
		}
		$file = T_PATH . 'cache/' . getdirname($this->site->host) . '/time.php';
		$data = cache_read($file);
		if (!$data) {
			$data = array();
			$data['time'] = time();
			cache_write($file, $data);
		} else if (time() - $data['time'] >= 12 * 60 * 60) {
			sleep(1);
			$data['time'] = time();
			$_SERVER['cfg_mode_num'] = true;
			cache_write($file, $data);
		}
		if (!$_SERVER['cfg_mode_num'] && !empty($this->expires) && $this->expires) {
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + $this->expires));
			header("Cache-Control: max-age=" . $this->expires);
		}
	}

	final function _parse_cache(&$code)
	{
		$this->parse_cache($code);
		if (strpos($code, '流量侠_动态时间')) {
			$code = str_replace('{流量侠_动态时间0}', date('Y-m-d'), $code);
			$code = str_replace('{流量侠_动态时间1}', date('Y-m-d', strtotime('-1 day')), $code);
			$code = str_replace('{流量侠_动态时间2}', date('Y-m-d', strtotime('-2 day')), $code);
			$code = str_replace('{流量侠_动态时间3}', date('Y-m-d', strtotime('-3 day')), $code);
			$code = str_replace('{流量侠_动态时间4}', date('Y-m-d', strtotime('-4 day')), $code);
			$code = str_replace('{流量侠_动态时间5}', date('Y-m-d', strtotime('-5 day')), $code);
			$code = str_replace('{流量侠_动态时间6}', date('Y-m-d', strtotime('-6 day')), $code);
			$code = str_replace('{流量侠_动态时间7}', date('Y-m-d', strtotime('-7 day')), $code);
			$code = str_replace('{流量侠_动态时间8}', date('Y-m-d', strtotime('-8 day')), $code);
			$code = str_replace('{流量侠_动态时间9}', date('Y-m-d', strtotime('-9 day')), $code);
		}
	}

	final function _parse_moban(&$code)
	{
		$this->parse_moban($code);
		function _getRandwebstime($match)
		{
			return date("Y-m-d H:i:s", strtotime("-" . $match[1] . " day", time() + rand(0, 10000)));
		}

		function _getRandwebsnumb($match)
		{
			$left = str_pad('1', $match[1], '0');
			$right = str_pad('9', $match[1], '0');
			return rand($left, $right);
		}

		$code = preg_replace_callback('/{流量侠_发布时间(\d+)}/', '_getRandwebstime', $code);
		$code = preg_replace_callback('/{流量侠_字符数字(\d+)}/', '_getRandwebsnumb', $code);
	}

	function load()
	{
		$_start = microtime(true);
		if ($_SERVER['cfg_mode_num']) {
			$php = false;
		} else {
			$php = cache_read($this->cache_file);
		}
		if (!$php) {
			if ($this->moban) {
				$php = T_PATH . 'Moban/' . $this->moban_id . '/' . $this->moban;
				$php = @php_strip_whitespace($php);
				if ($php == '') {
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 3600');
					die('<div style="text-align:center;"><h2><font color="red">模板文件丢失[/Moban/' . $this->moban_id . '/' . $this->moban . ']</font></h2></div>');
				}
				$php = trim($php);
				$php = str_replace('{流量侠_模板目录}', weburl($this->site->host, 'temp', $this->moban_id), $php);
				$this->_parse_moban($php);
				cache_write($this->cache_file, $php);
			} else {
				$php = '';
			}
		}
		$this->_parse_cache($php);
		if (strtolower(substr($php, 0, 5)) == '<?php') {
			$php = substr($php, 5);
		} else if (substr($php, 0, 2) == '<?') {
			$php = substr($php, 2);
		} else {
			$php = '?>' . $php;
		}
		if (substr($php, -2) == '?>') {
			$php = substr($php, 0, -2);
		}
		$php = trim($php);
		$_time = microtime(true) - $_start;
		$_time = sprintf('%.2f', $_time * 1000) . 'ms';
		$php = str_replace('{流量侠_运行时间}', $_time, $php);
		eval($php);
		ob_gzip_contents();
		exit;
	}

	function parse_cache(&$code)
	{
	}

	function parse_moban(&$code)
	{
	}
}

class site_index extends site_seos
{
	public $moban = 'index.php';

	function load()
	{
		parent:: load();
	}

	function parse_cache(&$code)
	{
		$code = preg_replace_callback('/{流量侠_动态内页}/', array($this, 'getRandomList'), $code);
		$code = preg_replace_callback('/{流量侠_网站轮链}/', array($this, 'getRandomLink'), $code);
	}

	function parse_moban(&$code)
	{		
		$code = str_replace('{流量侠_发布时间}', date('Y-m-d H:i:s'), $code);
		$code = str_replace('{流量侠_浏览次数}', rand(100000, 999999), $code);
		$code = str_replace('{流量侠_网站标题}', $this->site->title, $code);
		$code = str_replace('{流量侠_网站随机标题}', $this->getMyRandTitle(), $code);		
		$code = str_replace('{流量侠_网站域名}', 'http://' . $this->site->host . '/', $code);
		$urls = $this->site->host;
		$webs = $this->site->getSiteWord();
		$webs['pysj'] = check_num($webs['pysj']);
		$link = $webs['link'];
		$pysj = $webs['pysj'];
		$listwebs = $this->site->getListWord();
		$listlink = $listwebs['link'];
		$listpysj = $listwebs['pysj'];
		
		if (strpos($code, '流量侠_首页导航栏目')) {
			$arrlength=count($link);
			$sss = '';
			for($ii=0;$ii<$arrlength;$ii++) {
				$sss .= '<li><a href="' . weburl($urls, 'list', $ii, $pysj[$ii]) . '" target="_blank">' . $link[$ii] . '</a></li>';
			}
			
			$code = str_replace('{流量侠_首页导航栏目}', $sss, $code);
		}
		
		if (strpos($code, '流量侠_栏目标题')) {
			$code = str_replace('{流量侠_栏目标题1}', $link[0], $code);
			$code = str_replace('{流量侠_栏目标题2}', $link[1], $code);
			$code = str_replace('{流量侠_栏目标题3}', $link[2], $code);
			$code = str_replace('{流量侠_栏目标题4}', $link[3], $code);
		}
		if (strpos($code, '流量侠_栏目网址')) {
			$code = str_replace('{流量侠_栏目网址1}', weburl($urls, 'list', 0, $pysj[0]), $code);
			$code = str_replace('{流量侠_栏目网址2}', weburl($urls, 'list', 1, $pysj[1]), $code);
			$code = str_replace('{流量侠_栏目网址3}', weburl($urls, 'list', 2, $pysj[2]), $code);
			$code = str_replace('{流量侠_栏目网址4}', weburl($urls, 'list', 3, $pysj[3]), $code);
		}
		if (strpos($code, '流量侠_列表栏目网址')) {
			$code = str_replace('{流量侠_列表栏目网址1}', weburl($urls, 'list', 0, $listpysj[0]), $code);
			$code = str_replace('{流量侠_列表栏目网址2}', weburl($urls, 'list', 1, $listpysj[1]), $code);
			$code = str_replace('{流量侠_列表栏目网址3}', weburl($urls, 'list', 2, $listpysj[2]), $code);
			$code = str_replace('{流量侠_列表栏目网址4}', weburl($urls, 'list', 3, $listpysj[3]), $code);
		}
		if (strpos($code, '流量侠_列表栏目标题')) {			
			$code = str_replace('{流量侠_列表栏目标题1}', $listlink[0], $code);
			$code = str_replace('{流量侠_列表栏目标题2}', $listlink[1], $code);
			$code = str_replace('{流量侠_列表栏目标题3}', $listlink[2], $code);
			$code = str_replace('{流量侠_列表栏目标题4}', $listlink[3], $code);
		}
		$code = preg_replace_callback('/{流量侠_网站副词}/', array($this, 'getRandomWord'), $code);
		$code = preg_replace_callback('/{流量侠_随机词语}/', array($this, 'getRandomKeys'), $code);
		$code = preg_replace_callback('/{流量侠_随机内页}/', array($this, 'getRandomUrls'), $code);
		$code = preg_replace_callback('/{流量侠_随机图片}/', array($this, 'getRandomPics'), $code);
		$code = preg_replace_callback('/{流量侠_文章句子}/', array($this, 'getRandomText'), $code);
		$code = preg_replace_callback('/{流量侠_网站内页}/', array($this, 'getRandomList'), $code);
		$code = preg_replace_callback('/{流量侠_网站图片}/', array($this, 'getRandomPic0'), $code);
		$code = preg_replace_callback('/{流量侠_网站图文}/', array($this, 'getRandomPic1'), $code);
	}	
	
	private function getMyRandTitle()
	{
		$titles = $this->site->title;
		$ssaa = explode('_', $titles);
		$title = $ssaa[array_rand($ssaa)];		
		return $title;
	}

	private function getRandomWord()
	{
		$webs = $this->site->getSiteWord();
		return '' . $webs['link'][0] . '';
	}

	private function getRandomKeys()
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = mt_rand(0, count($keys) - 1);
		return '' . $keys[$i] . '';
	}

	private function getRandomUrls()
	{
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = mt_rand(0, count($keys) - 1);
		// $url = weburl($this->site->host, 'read', $i);
		$url = 'http://' . $this->site->host . '/' . py($key[$i], 1) . "_" .$i . ".html";
		return '' . $url . '';
	}

	private function getRandomPics()
	{
		loadPics();
		$url = 'http://' . $this->site->host . '/';
		$i = rand(0, count($_SERVER['cfg_pics']) - 1);
		if (!isset($_SERVER['cfg_pics'][$i])) {
			return '网站图片库为空，请检测！';
		}
		return '' . $url . '' . $_SERVER['cfg_pics'][$i] . '';
	}

	private function getRandomText()
	{
		$num = sizeof(scandir("./text"));
		$num = ($num > 2) ? $num - 2 : 0;
		$suzi = mt_rand(1, $num);
		$text = get_lines_by_php('Text/Text_' . $suzi . '.php');
		$i = mt_rand(0, count($text) - 1);
		return '' . $text[$i] . '';
	}

	private function getRandomList()
	{
		$keys = $this->site->getSiteKeys($this->site->host);		
		// echo "count keys: " . count($keys);
		$i = mt_rand(0, count($keys) - 1);		
		$v = $keys[$i];
		if (10 >= mb_strlen($v, 'GBK')) {				
			$j = mt_rand(0, count($keys) - 1);				
			$v .= mb_substr($keys[$j], 0, 5, 'GBK');
		} 
		else if (16 < mb_strlen($v, 'GBK')) {
			$v = mb_substr($v, 0, 16, 'GBK');
		}
		// $url = weburl($this->site->host, 'read', $i);
		$url = $url = 'http://' . $this->site->host . '/' . py($v, 1) . '_' . $i . '.html';
		return '<a href="' . $url . '" target="_blank">' . $v . '</a>';
	}

	private function getRandomPic0()
	{
		loadPics();
		$url = 'http://' . $this->site->host . '/';
		$i = rand(0, count($_SERVER['cfg_pics']) - 1);
		if (!isset($_SERVER['cfg_pics'][$i])) {
			return '网站图片库为空，请检测！';
		}
		$keys = $this->site->getSiteKeys($this->site->host);
		$images = $_SERVER['cfg_pics'][$i];
		$j = mt_rand(0, count($keys) - 1);
		$num = sizeof(scandir("./text"));
		$num = ($num > 2) ? $num - 2 : 0;
		$suzi = mt_rand(1, $num);
		$text = get_lines_by_php('Text/Text_' . $suzi . '.php');
		$i = mt_rand(0, count($text) - 1);
		$wzbt = $text[$i];
		if (mt_rand(1, 2) == 1) {
			return '<img src="' . $url . '' . $images . '" alt="' . $wzbt . '' . $keys[$j] . '" />';
		} else {
			return '<img src="' . $url . '' . $images . '" alt="' . $keys[$j] . '' . $wzbt . '" />';
		}
	}

	private function getRandomPic1()
	{
		loadPics();
		$keys = $this->site->getSiteKeys($this->site->host);
		$i = rand(0, count($_SERVER['cfg_pics']) - 1);
		if (!isset($_SERVER['cfg_pics'][$i])) {
			return '网站图片库为空，请检测！';
		}
		$images = $_SERVER['cfg_pics'][$i];
		$j = mt_rand(0, count($keys) - 1);
		$url1 = 'http://' . $this->site->host . '/';
		$url2 = weburl($this->site->host, 'read', $j);
		$num = sizeof(scandir("./text"));
		$num = ($num > 2) ? $num - 2 : 0;
		$suzi = mt_rand(1, $num);
		$text = get_lines_by_php('Text/Text_' . $suzi . '.php');
		$i = mt_rand(0, count($text) - 1);
		$wzbt = $text[$i];
		if (mt_rand(1, 2) == 1) {
			$title = $keys[$j];
			$url2 = 'http://' . $this->site->host . '/' . py($title, 1) . '_' .$j . '.html';
			//return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $wzbt . '' . $keys[$j] . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $wzbt . '' . $keys[$j] . '</a>';
			return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $keys[$j] . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $keys[$j] . '</a>';
		} else {
			$title = $wzbt;
			$url2 = 'http://' . $this->site->host . '/' . py($title, 1) . '_' .$j . '.html';
			// return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $keys[$j] . '' . $wzbt . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $keys[$j] . '' . $wzbt . '</a>';
			return '<a href="' . $url2 . '" target="_blank"><img src="' . $url1 . '' . $images . '" title="' . $wzbt . '" /></a><br><a href="' . $url2 . '" target="_blank">' . $wzbt . '</a>';
		}
	}

	private function getRandomLink()
	{
		$sites = $this->site->getSites();
		$keys = array_keys($sites);
		$list = $this->site->host;
		$nums = array_search($list, $keys);
		$webs = get_lines_by_php('Links/' . $nums . '.php');
		foreach ($webs as $v) {
			$link = split("\|", $v);
			$links[] = "<li><a href=\"http://" . $link[0] . "\">" . $link[1] . "</a></li>";
		}
		$links = implode("\r\n", $links);
		return $links;
	}
}

class site_robots extends site_seo
{
	function load()
	{
		$txt = '
		User-agent: *
		Disallow: /cache/
		Disallow: /seodj/
		Disallow: /seoej/
		Sitemap: http://' . $this->site->host . '/sitemap.xml
		Sitemap: http://' . $this->site->host . '/sitemap.html
		User-agent: Ezooms
		Disallow: /
		User-agent: dotbot
		Disallow: /
		User-agent: exabot
		Disallow: /
		User-agent: MSNBot
		Disallow: /
		User-agent: BLEXBot
		Disallow: /
		User-agent: MJ12bot
		Disallow: /
		User-agent: gigabot
		Disallow: /
		User-agent: Bingbot
		Disallow: /
		User-Agent: YodaoBot
		Disallow: /
		User-agent: rogerbot
		Disallow: /
		User-agent: AhrefsBot
		Disallow: /
		User-agent: Googlebot
		Disallow: /
		User-agent: webspider
		Disallow: /
		User-agent: SemrushBot
		Disallow: /
		User-agent: JikeSpider
		Disallow: /
		User-agent: EasouSpide
		Disallow: /
		User-Agent: Yahoo Slurp
		Disallow: /
		User-agent: YisouSpider
		Disallow: /
		User-agent: AhrefsBot/5.0
		Disallow: /
		User-agent: googlebot-image
		Disallow: /
		User-agent: googlebot-mobile
		Disallow: /
		';
		$txt = trim($txt);
		$txt = str_replace("\t", '', $txt);
		echo $txt;
		exit;
	}
}

class site_custom_pc
{
	function load()
	{
		if (isset($_GET['flag1'])) {
			set_time_limit(0);
			$begin = microtime(true);
			function get_text_php($file)
			{
				$str1 = file_get_contents($file);
				$str1 = del_blank_line($str1);
				$text = str_replace('"', "'", $str1);
				$strs = array("！", "!", "，", "。", "？", "?", "”", "，");
				$text = str_replace($strs, ',', $text);
				$text = explode(",", $text);
				$text = array_unique(array_filter($text));
				foreach ($text as & $v) {
					$v = str_replace(array("　", ' '), '', $v);
					$v = trim($v);
					$v = trim($v, "\.");
				}
				return $text;
			}

			function write_file($file, $value)
			{
				file_put_contents($file, "<?php\r\n#混淆文章 模版文件\r\ndefined('T_VERSION') || die();\r\n# 防止数据被非法拷贝\r\n?>\r\n" . $value);
			}

			// $filename = dirname(__FILE__) . "\Moban\Text\\text.txt";
			$filename = "./Moban/Text/text.txt";
			if (!file_exists($filename)) {
				// echo $filename;
				die('<div style="text-align:center;"><h2><font color="red">[木有找到：/Moban/Text/Text.txt]</font></h2></div>');
			} else {
				// $text = get_text_php("./Moban/Text/Text.txt");
				$text = get_text_php($filename);
			}
			$filesize = 180 * 1024;
			$start = 0;
			$tmp = "";
			$kstart = 1;
			foreach ($text as & $v) {
				$start = $start + strlen($v) + 2;
				$tmp = $tmp . $v . "\r\n";
				if ($start > $filesize) {
					write_file("./Moban/Text/Text_" . $kstart . ".php", trim($tmp));
					$start = 0;
					$tmp = "";
					$kstart++;
				}
			}
			write_file("./Moban/Text/Text_" . $kstart . ".php", trim($tmp));
			$end = microtime(true);
			$time = $end - $begin;
			echo "<div style=text-align:center;><h3>文章分割成功.耗时{$time}秒</h3></div>";
		}
		echo '
		<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
		<html>
			<head>
				<title>文章分割 - 简单客站群系统</title>
				<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
			</head>
			<script>
				function doSomeThing1(){
				  window.location.href="index.php?custom=pc&flag1=1";
				}
			</script>
			<style type="text/css">
				body {
					font-size: 12px;
					background-color: #CCC;
				}
				table {
					width: 960px;
					margin: 0 auto;
					padding: 10px;
					background-color: #FFF;
				}
				.button {
					width: 99px;
					height: 32px;
					border: 0;
					font-size: 14px;
					margin: 0 2px 2px 0;
					background: #ddd url(Moban/Images/kj.png) no-repeat;
					cursor: pointer;
				}
			</style>
			<body>
			<div style="text-align:center;"><h2><font color="red">流量侠站群文章分割功能</font></h2></div>
			<table align="center">
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;请确保[/Moban/Text/]里包含有 Text.txt</td>
					<td>生成后的混淆文章请手动复制到文章库</td>
				</tr>
				<td colspan="4" align="center">
				<input class="button" type="button" value="文章分割功能" onclick="doSomeThing1()" >
				<input class="button" type="button" value="文章分割功能" onclick="doSomeThing1()" >
				<input class="button" type="button" value="文章分割功能" onclick="doSomeThing1()" >
				<input class="button" type="button" value="文章分割功能" onclick="doSomeThing1()" >
				</td>
				</tr>
			</table>
		  <!-- 流量侠泛站群 QQ:1795873837 -->
		  </body>
	  </html>';
		exit;
	}
}

class SiteCore
{
	public $host_id;
	public $url;
	public $host;
	public $title;
	public $id;
	public $tag;
	public $_CLASS_;

	function __construct()
	{
		$list = headers_list();
		foreach ($list as $_) {
			if (strpos($_, 'PHP-Developer-By') === 0) {
				$list = true;
				break;
			}
		}
		if ($list === true) {
			$list = get_included_files();
			if (count($list) >= 3) {
				if (substr($list[0], -9) == 'index.php' && substr($list[1], -10) == 'config.php' && (substr($list[2], -13) != 'site_core.php' || substr($list[2], -12) != '~core.php')) {
					$list = true;
				}
			}
		}
		if ($list !== true) {
			eval('return;');
			exit;
		}
		$this->_custom_init();
		$sites = $this->getSites();
		$host = HTTP_HOST(true);
		if (!isset($sites[$host])) {
			$host = get_lines_by_php('seodj/host.php');
			$keys = get_lines_by_php('seodj/keys.php');
			$i = mt_rand(0, count($host) - 1);
			$langzi = '<a href="http://' . trim($host[$i]) . '/" target="_blank">' . trim($keys[$i]) . '</a>' . PHP_EOL;
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 3600');
			echo '
			<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
			<html>
				<head>
					<title>域名未绑定 - 简单客站群系统</title>
					<script language="javascript" src="/Moban/Temp/Js/cz.js" /></script>
				</head>
				<body>
					<div style="text-align:center;"><h2><font color="red">推荐浏览网址∶' . $langzi . '</font></h2></div>
					<div style="text-align:center;"><h1><font color="red">简单客提示∶当前域名未进行绑定，请检测 HOST文本内容！</font></h1></div>
					</div>
				<!-- 简单客站群系统 QQ:1795873837 -->
				</body>
			</html>';
			exit;
		} else {
			$i = 0;
			$this->host_id = 0;
			foreach ($sites as $key => $title) {
				if ($key == $host) {
					$this->host_id = $i;
					break;
				}
				$i++;
			}
		}
		$title = $sites[$host];
		$this->title = $title;
		$this->host = $host;
		$this->url = REQUEST_URI();
		$this->_route_init();
	}

	public function getSiteKeys($host)
	{
		if ($host === false) {
		}
		static $list = array();
		if (isset($list[$host])) {
			return $list[$host];
		}
		static $data = array();
		if ($data) {
			return $data['list'];
		}
		$file = T_PATH . 'cache/' . getdirname($host) . '/list.php';
		$data = cache_read($file);
		if (!$data) {
			$data = array();
			$data['time'] = time();
			$keyword = get_lines_by_php(T_PATH . 'seodj/list.php');
			if (!$keyword) {
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				header('Retry-After: 3600');
				echo '
				<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
				<html>
					<head>
						<title>简单客站群系统</title>
					</head>
					<body>
						<div style="text-align:center;"><h2><font color="red">seodj目录下的 list.php 的行数为空，请检查！</font></h2></div>
					<!-- 简单客站群系统 QQ:1795873837 -->
					</body>
				</html>';
				exit;
			}
			$keyword = array_unique($keyword);
			$keyword = array_filter($keyword);			
			$keys = array_search($this->title, $keyword);
			if ($keys !== false) array_splice($keyword, $keys, 1);
			// shuffle($keyword);
			$max = count($keyword);
			if ($max < $_SERVER['cfg_keys_num']) {
				$_SERVER['cfg_keys_num'] = $max;
			}
			for ($i = 0, $max -= 1; $i < $_SERVER['cfg_keys_num']; $i++) {
				do {
					// $___ = mt_rand(0, $max);
					$___ = $i;
					if (!isset($keyword[$___])) {
						continue;
					}
					$_ = $keyword[$___];
				} while (in_array($_, $data));
				$data['list'][] = $_;
			}
			// disable for different content with different list number
			// cache_write($file, $data);
		} else if (time() - $data['time'] >= $_SERVER['cfg_Time_num'] * 60 * 60) {
			$keys = get_lines_by_php(T_PATH . 'seodj/list.php');
			for ($i = 0; $i < $_SERVER['cfg_data_num']; $i++) {
				$k = mt_rand(0, count($keys) - 1);
				$new[] = $keys[$k];
			}
			$data['time'] = time();
			$data['list'] = array_merge($data['list'], $new);
			cache_write($file, $data);
		}
		$list[$host] = $data['list'];
		return $data['list'];
	}

	public function getSiteWord()
	{
		static $data = array();
		if ($data) {
			return $data;
		}
		$file = T_PATH . 'cache/' . getdirname($this->host) . '/word.php';
		$data = cache_read($file);
		if (!$data) {
			$data = array();
			$sites = $this->getSites();
			$keys = array_keys($sites);
			$list = $this->host;
			$nums = array_search($list, $keys);
			$webs = get_lines_by_php('seodj/word.php');
			$webs['link'] = explode('|', $webs[$nums]);
			$webs_link = array();
			$webs_pysj = array();
			foreach ($webs['link'] as $i => $v) {
				if ($v) {
					$webs_link[] = $v;
					$webs_pysj[] = py($v, 1);
				}
			}
			$data['link'] = $webs_link;
			$data['pysj'] = $webs_pysj;
			cache_write($file, $data);
		}
		return $data;
	}
	
	public function getListWord()
	{
		static $data = array();
		if ($data) {
			return $data;
		}
		$file = T_PATH . 'cache/' . getdirname($this->host) . '/listword.php';
		$data = cache_read($file);
		if (!$data) {
			$data = array();
			$sites = $this->getSites();
			$keys = array_keys($sites);
			$list = $this->host;
			$nums = array_search($list, $keys);
			//$webs = get_lines_by_php('seodj/listword.php');
			//$webs['link'] = explode('|', $webs[$nums]);	
					
			$keyword = get_lines_by_php('seodj/listword.php');      
      $tmp = array_rand($keyword, 4);
      for ($i = 0; $i <4; $i++) {		
      	$k = mt_rand(0, count($keyword) - 1);		
				$webs['link'][] = $keyword[$k];
			}
			/*
      foreach ($tmp as $i => $v) {
				if ($v) {
					$webs['link'][] = $v;					
				}
			}   */   
      
			$webs_link = array();
			$webs_pysj = array();
			foreach ($webs['link'] as $i => $v) {
				if ($v) {
					$webs_link[] = $v;
					$webs_pysj[] = py($v, 1);
				}
			}
			$data['test'] = $tmp;
			$data['link'] = $webs_link;
			$data['pysj'] = $webs_pysj;
			//cache_write($file, $data);
		}
		return $data;
	}

	public function getSites()
	{
		set_time_limit(0);
		static $sites = false;
		if ($sites) {
			return $sites;
		}
		$file = T_PATH . 'cache/sites.php';
		$sites = cache_read($file);
		if (!$sites) {
			$host = get_lines_by_php(T_PATH . 'seodj/host.php');
			$sitetitle = get_lines_by_php(T_PATH . 'seodj/keys.php');
			if (!$host) {
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				header('Retry-After: 3600');
				echo '
				<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
				<html>
					<head>
						<title>简单客站群系统</title>
					</head>
					<body>
						<div style="text-align:center;"><h2><font color="red">seodj目录下的 host.php 的行数为空，请检查！</font></h2></div>
					<!-- 简单客站群系统 QQ:1795873837 -->
					</body>
				</html>';
				exit;
			} else if (!$sitetitle || count($host) > count($sitetitle)) {
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				header('Retry-After: 3600');
				echo '
				<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
				<html>
				    <head>
					    <title>简单客站群系统</title>
				    </head>
					<body>
						<div style="text-align:center;"><h2><font color="red">seodj目录下 host.php 的行数大于 keys.php 的行数，请检查！</font></h2></div>
					<!-- 简单客站群系统 QQ:1795873837 -->
					</body>
				</html>';
				exit;
			}
			@mkdir(T_PATH . 'cache/');
			@mkdir(T_PATH . 'cache/web/');
			$sites = array();
			foreach ($host as $_) {
				@mkdir(T_PATH . 'cache/' . getdirname($_) . '/');
				// $sites[$_] = array_shift($sitetitle);
				$sss = array_shift($sitetitle);
				$ssaa = explode('|', $sss);
				$tttt = '';
				foreach ($ssaa as $i => $v) {
					if ($v) {
						 if ($tttt) {
					      $tttt .= '_' . $v;
					   } else {
					   	  $tttt .= $v;
					   }
					}
				}
				$sites[$_] =  $tttt;
			}
			cache_write($file, $sites);
		}
		$_SERVER['sites'] = $sites;
		return $sites;
	}

	function _custom_init()
	{
		if (!isset($_GET['custom'])) {
			return;
		}
		$class = 'site_custom_' . $_GET['custom'];
		if (!class_exists($class)) {
			$_GET['route'] = '404';
			return;
		}
		$class = new $class($this);
		$class->load();
		exit;
	}

	function _route_init()
	{
		isset($_GET['route']) || $_GET['route'] = 'index';
		if ($_GET['route'] == 'read' && isset($_GET['id'])) {
			$this->id = $_GET['id'];
		} else if ($_GET['route'] == 'list' && isset($_GET['tag'])) {
			$this->tag = $_GET['tag'] - 1;
		} else if ($_GET['route'] == 'index') {
		} else if ($_GET['route'] == 'maps' && isset($_GET['tag'])) {
			$this->tag = $_GET['tag'];
		} else if ($_GET['route'] == 'maps') {
		} else if ($_GET['route'] == 'robots') {
		} else {
			$_GET['route'] = '404';
		}
		$class = 'site_' . $_GET['route'];
		$this->_CLASS_ = $class;
		$class = new $class($this);
		$class->load();
	}

	function call_404()
	{
		/*
		$class = new site_404($this);
		$class->load();
		exit;
		*/				
		 $file = T_PATH .'/' . $this->site->host . '404.html';
     $data = cache_read($file);
		 if (!$data) {			
			$html = '';
			
			$html .= '<!DOCTYPE HTML><html>	<head><title>' . $this->site->title . '</title>' ;
			$html .= '<link href="/Moban/style.css" rel="stylesheet" type="text/css"  media="all" />
	</head>	<body>		<div class="wrap">				<div class="header">					<div class="logo">						<h1><a href="#">Ohh</a></h1>					</div>				</div>
			<div class="content">
				<img src="/Moban/Images/error-img.png" title="error" />
				<p><span><label>O</label>hh.....</span>您请求的页面不存在</p>';
			$html .= '<a href="/">返回首页</a>   			</div></div></body></html>';
			$data = $html;
			cache_writestr($file, $html);
		 }
		 
		 echo $data;
		 exit;
	}
}
