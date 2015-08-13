<?php
# 网站栏目 模板文件
defined('T_VERSION') || die();
# 防止模板被非法拷贝
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <!--title>{流量侠_当前标题}({流量侠_栏目页数})_{流量侠_网站标题}</title-->
    <title>{流量侠_网站随机标题}</title>
		<meta name="robots" content="noarchive">
		<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
		<link rel="stylesheet" type="text/css" href="/Moban/style_0.css" />
		<script language="javascript" src="/Moban/Cz/cz.js" /></script>
    </head>
	<!--
                   _ooOoo_
                  o8888888o
                  88" . "88
                  (| -_- |)
                  O\  =  /O
               ____/`---'\____
             .'  \\|     |//  `.
            /  \\|||  :  |||//  \
           /  _||||| -:- |||||-  \
           |   | \\\  -  /// |   |
           | \_|  ''\---/''  |   |
           \  .-\__  `-`  ___/-. /
         ___`. .'  /--.--\  `. . __
      ."" '<  `.___\_<|>_/___.'  >'"".
     | | :  `- \`.;`\ _ /`;.`/ - ` : | |
     \  \ `-.   \_ __\ /__ _/   .-` /  /
======`-.____`-.___\_____/___.-`____.-'======
                   `=---='
  ... 原创模板 盗模板者 佛祖诅咒 你没好报 ...
	-->
	<body>
	<div id="Tb">
		<h1><a href="{流量侠_网站域名}" title="{流量侠_网站标题}" target="_blank">{流量侠_网站标题}</a></h1>
	</div>
	<div id="lm">
		<fieldset>
			<ul>
				<!--li><a href="{流量侠_列表栏目网址1}" target="_blank">{流量侠_列表栏目标题1}</a></li>
				<li><a href="{流量侠_列表栏目网址2}" target="_blank">{流量侠_列表栏目标题2}</a></li>
				<li><a href="{流量侠_列表栏目网址3}" target="_blank">{流量侠_列表栏目标题3}</a></li>
				<li><a href="{流量侠_列表栏目网址4}" target="_blank">{流量侠_列表栏目标题4}</a></li-->
				
				<li>{流量侠_列表动态标题1}</li>
				<li>{流量侠_列表动态标题2}</li>
				<li>{流量侠_列表动态标题3}</li>
				<li>{流量侠_列表动态标题4}</li>
			</ul>
		</fieldset>
	</div>
	<div id="menu">
	<!--h2>&nbsp;您现在的位置：<a href="{流量侠_网站域名}" target="_blank">{流量侠_网站标题}</a> >> <a href="{流量侠_当前网址}" target="_blank">{流量侠_当前标题}</a> >> <a href="{流量侠_栏目网址}" target="_blank">{流量侠_当前标题}{流量侠_栏目页数}</a> >> 列表内容</h2-->
	<h2>&nbsp;您现在的位置：<a href="{流量侠_网站域名}" target="_blank">{流量侠_网站随机标题}</a> >> <a href="{流量侠_当前网址}" target="_blank">{流量侠_上级标题}</a> >> <a href="{流量侠_当前网址}" target="_blank">{流量侠_当前标题}</a></h2>
	<ul>
		{流量侠_栏目列表}
		<div id="page">
		{流量侠_栏目分页}
		</div>
	</ul>
	</div>
	<div id="side">
		<h3>{流量侠_当前标题}</h3>
		<ul>
			{流量侠_栏目阅读}
		</ul>
	</div>
	<div id="Db">
		BY {流量侠_网站域名} <strong><a href="{流量侠_网站域名}" target="_blank">{流量侠_网站标题}</a></strong> <strong><a href="{流量侠_当前网址}" target="_blank">{流量侠_当前标题}</a></strong> 版权所有 <a href="{流量侠_网站域名}sitemap.html" target="_blank">网站地图</a>
	</div>
	<!-- Baidu Button BEGIN -->
		<script type="text/javascript" id="bdshare_js" data="type=slide&amp;img=7&amp;pos=right&amp;uid=6713514" ></script>
		<script type="text/javascript" id="bdshell_js"></script>
		<script type="text/javascript">
		document.getElementById("bdshell_js").src = "http://bdimg.share.baidu.com/static/js/shell_v2.js?cdnversion=" + Math.ceil(new Date()/3600000);
		</script>
	<!-- Baidu Button END -->
	</body>
</html>
<!-- 脚本运行时间 : {流量侠_运行时间} -->
<!-- 当前模板目录 : {流量侠_模板目录} -->