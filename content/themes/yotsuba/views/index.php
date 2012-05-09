<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<article>
	<h1><?php echo __('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<p><?php echo __('We are hosting these boards:'); ?></p>
	<h2>[
		<?php
		$board_urls = array();
		foreach ($this->radix->get_all() as $key => $board)
		{
			$board_urls[] = '<a href="' . $board->href . '">'.$board->shortname.'</a>';
		}
		echo implode(' / ', $board_urls)
		?>
		]
	</h2>
</article>