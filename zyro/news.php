<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>News</title>
	<base href="{{base_url}}" />
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<meta name="description" content="" />
	<meta name="keywords" content="News" />
		<meta name="generator" content="Zyro - Website Builder" />
	
	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" />
	<script src="js/jquery-1.8.3.min.js" type="text/javascript"></script>
	<script src="js/bootstrap.min.js" type="text/javascript"></script>
	<script src="js/main.js" type="text/javascript"></script>

	<link href="css/site.css?v=1.0.13" rel="stylesheet" type="text/css" />
	<link href="css/common.css?ts=1439496166" rel="stylesheet" type="text/css" />
	<link href="css/news.css?ts=1439496166" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">var currLang = '';</script>		
	<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>


<body>{{ga_code}}<div class="root"><div class="vbox wb_container" id="wb_main" style="height: 210px;">
	
<div id="wb_element_instance13" class="wb_element"><a class="btn btn-default btn-collapser"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a><ul class="hmenu"><li><a href="Home/" target="_self" title="Home">Home</a></li><li><a href="http://theosrepo.ikilledappl3.netau.net" target="_self" title="Theos Repo">Theos Repo</a></li><li><a href="http://toxicappl3inc.github.io" target="_self" title="ToxicAppl3 Inc. Website">ToxicAppl3 Inc. Website</a></li><li><a href="http://j.gs/10554431/opalbeta" target="_self" title="Opal Beta Repo">Opal Beta Repo</a></li><li><a href="http://toxicappl3inc.github.io/repo/" target="_self" title="Main Repo">Main Repo</a></li></ul><script type="text/javascript"> (function() { var isOpen = false, elem = $('#wb_element_instance13'), btn = elem.children('.btn-collapser').eq(0); btn.on('click', function() { if (elem.hasClass('collapse-expanded')) { isOpen = false; elem.removeClass('collapse-expanded'); } else { isOpen = true; elem.addClass('collapse-expanded'); } }); elem.find('ul').each(function() { var ul = $(this); if (ul.parent('li').length > 0) { ul.parent('li').eq(0).children('a').on('click', function() { if (!isOpen) return true; if (ul.css('display') !== 'block') ul.css({display: 'block'}); else ul.css({display: ''}); return false; }); } }); })(); </script></div><div id="wb_element_instance14" class="wb_element" style=" line-height: normal;"><p> </p>

<h2 class="wb-stl-heading2" style="text-align: center;">iKilledAppl3</h2>

<p>               iOS Developer</p>
</div><div id="wb_element_instance15" class="wb_element" style=" line-height: normal;"><h5 class="wb-stl-subtitle"><span style="color:#ffffff;">© 2015 i</span><span style="color:#ffffff;">KilledAppl3 All Rights Reserved.</span></h5>
</div><div id="wb_element_instance16" class="wb_element" style="width: 100%;">
			<?php
				global $show_comments;
				if (isset($show_comments) && $show_comments) {
					renderComments(news);
			?>
			<script type="text/javascript">
				$(function() {
					var block = $("#wb_element_instance16");
					var comments = block.children(".wb_comments").eq(0);
					var contentBlock = $("#wb_main");
					contentBlock.height(contentBlock.height() + comments.height());
				});
			</script>
			<?php
				} else {
			?>
			<script type="text/javascript">
				$(function() {
					$("#wb_element_instance16").hide();
				});
			</script>
			<?php
				}
			?>
			</div><div id="wb_element_instance17" class="wb_element" style="text-align: center; width: 100%;"><div class="wb_footer"></div><script type="text/javascript">
			$(function() {
				var footer = $(".wb_footer");
				var html = (footer.html() + "").replace(/^\s+|\s+$/g, "");
				if (!html) {
					footer.parent().remove();
					footer = $("#wb_footer");
					footer.height(0);
				}
			});
			</script></div></div><div class="wb_sbg"></div></div></body>
</html>