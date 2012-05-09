<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div id="content">
	<h1><?php echo __('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<h2><?php echo __('Choose a 4chan board:'); ?></h2>
	<p>
		<?php
		$board_urls = array();
		foreach ($this->radix->get_all() as $key => $item)
		{
			array_push($board_urls, '<a href="' . $item->href . '" title="' . $item->name . '">/' . $item->shortname . '/</a>');
		}
		echo implode(' ', $board_urls);
		?>
	</p>
</div>
