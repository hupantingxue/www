<?php
define('T_VERSION', '20131111');
define('T_PATH', dirname(__FILE__) . '/');

require 'config.php';

if ( file_exists('site_core.php') ) {
	require 'site_core.php';
} else {
	require 'core.php';
}

date_default_timezone_set('PRC');
ob_start();
header('Content-type: text/html; charset=gb2312');
header('PHP-Developer-By: langzi');

new SiteCore();