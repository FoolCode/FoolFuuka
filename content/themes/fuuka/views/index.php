<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<article style="padding:50px;">
	<h1><?php echo _('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<p><?php echo _('We are hosting the archives of the following boards:'); ?></p>
	<h2>[
		<?php
		$board_urls = array();
		foreach ($boards as $key => $board)
		{
			$board_urls[] = '<a href="' . $board->href() . '">'.$board->shortname.'</a>';
		}
		echo implode(' / ', $board_urls)
		?>
		]
	</h2>
</article>