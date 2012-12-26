<?php if (!defined('DOCROOT')) exit('No direct script access allowed');
header('X-UA-Compatible: IE=edge,chrome=1');
header('imagetoolbar: false');?>
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