<?php $title = fuuka_title() ?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $title ?></title>
		<style type="text/css">
			.outer { text-align: center }
			.inner { margin: auto; display: table; display: inline-block; text-decoration: none; text-align: left; padding: 1em; border: thin dotted }
			.text { font-family: Mono, 'MS PGothic' !important }
			h1 { font-family: Georgia, serif; margin: 0 0 0.4em 0; font-size: 4em; text-align: center }
			p { margin-top: 2em; text-align: center; font-size: small }
			a { color: #34345C }
			a:visited { color: #34345C }
			a:hover { color: #DD0000 }
		</style>
		<meta http-equiv="Refresh" content="2; url=<?php echo $url ?>" />
	</head>
	<body>
		<h1><?php echo $title ?></h1>
		<div class="outer">
			<div class="inner">
				<span class="text"><?php echo nl2br(fuuka_message()) ?></span>
			</div>
		</div>
		<p><a href="<?php echo $url ?>" rel="noreferrer"><?php echo $url ?></a><br/>All characters <acronym title="DO NOT STEAL MY ART">&#169;</acronym> Darkpa's party</p>
	</body>
</html>