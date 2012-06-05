<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= $template['title'] ?></title>
		<meta http-equiv="Refresh" content="0; url=<?= $url ?>" />
	</head>
	<body>
		<!-- If the meta-refresh does not work, we will attempt to redirect with JavaScript. -->
		<script type="text/javascript">
			window.location.href = '<?= $url ?>';
		</script>
		<?= sprintf(__('Attempting to redirect to %s.'), '<a href="' . $url . '" rel="noreferrer">' . $url . '</a>') ?>
	</body>
</html>