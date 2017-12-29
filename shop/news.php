<!DOCTYPE html>
<html lang="zh">
<head>
	<meta charset="UTF-8">
	<style>
		hr{
			color: coral;
			clear: both;
			margin: 5px 20px;
		}
		.title{
			font-size: 22px;
			float: left;
			margin: 5px 20px;
			clear: both;
		}
		.time{
			font-size: 14px;
			float: right;
			margin: 0 10px 10px 10px;
			clear: both;
		}
		.content{
			text-indent:2em;
			margin: 10px 10px 10px 10px;
		}
	</style>
	<title>新闻资讯</title>
</head>
<body>
<div class="title">
	<?php echo '<b>'.$info['article_title'].'</b>'?>
</div>
<div class="time">
	<?php echo date('Y-m-d H:i:s',$info['article_time'])?>
</div>
<hr />
<div class="content">
	<?php echo $info['article_content']?>
</div>
</body>
</html>