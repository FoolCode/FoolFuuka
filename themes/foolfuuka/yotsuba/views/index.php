<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<div id="content">
	<h1><?= __('Welcome to {{FOOL_NAME}}'); ?></h1>
	<h2><?= __('Choose a Board:'); ?></h2>
	<p>
		<?php
		$board_urls = array();
		foreach (Radix::get_all() as $key => $item)
		{
			array_push($board_urls, '<a href="' . $item->href . '" title="' . $item->name . '">/' . $item->shortname . '/</a>');
		}
		echo implode(' ', $board_urls);
		?>
	</p>
</div>
