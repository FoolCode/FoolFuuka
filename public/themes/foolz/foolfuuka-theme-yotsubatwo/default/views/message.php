<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<div class="alert alert-<?= $level?>" style="margin:15%;">
	<h4 class="alert-heading"><?= __('Message') ?></h4>
	<?= $message ?>
</div>