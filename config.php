<?php
	/** 获取开启网站Gzip压缩等级 **/
	$_SERVER['cfg_gzip_num'] = 5;

	/** 获取网站更新文章控制数量 **/
	$_SERVER['cfg_data_num'] = 8;

	/** 获取网站模板控制循环数量 **/
	$_SERVER['cfg_Temp_num'] = 1;

	/** 获取网站栏目阅读条数控制 **/
	$_SERVER['cfg_read_num'] = 19;

	/** 获取网站更新文章循环时间 **/
	$_SERVER['cfg_Time_num'] = 24;

	/** 获取网站栏目条数显示控制 **/
	$_SERVER['cfg_list_num'] = 38;

	/** 获取网站地图显示条数控制 **/
	$_SERVER['cfg_page_num'] = 88;

	/** 获取网站内词初始数量控制 **/
	$_SERVER['cfg_keys_num'] = 99;

	/** 正式模式false 调试模式true **/
	$_SERVER['cfg_mode_num'] = false;
	/** 此处伪静态必须与htaccess等伪静态规则配对 **/
	$_SERVER['url']['read'] = '/{id}/';
	$_SERVER['url']['list'] = '/{key}_{tag}/';
	$_SERVER['url']['temp'] = '/template/{id}/';
	$_SERVER['url']['pags'] = '/{key}_{tag}/{page}/';
	$_SERVER['url']['maps'] = '/sitemap_{page}.html';