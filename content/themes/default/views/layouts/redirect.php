<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $template['title'] ?></title>
		<meta http-equiv="Refresh" content="0; url=<?php echo $url ?>" />
	</head>
	<body>
		<!-- If the meta-refresh does not work, we will attempt to redirect with JavaScript. -->
		<script type="text/javascript">
			window.location.href = '<?php echo $url ?>';
		</script>
		Attempting to redirect to <a href="<?php echo $url ?>" rel="noreferrer"><?php echo $url ?></a>.
	</body>
</html>