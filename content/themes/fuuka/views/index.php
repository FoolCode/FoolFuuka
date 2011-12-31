<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<img src="<?php echo site_url(array('content', 'themes', 'fuuka', 'images')) ?>home.png" style="position: fixed; bottom: 0; left: 0; z-index: -1; opacity: 0.2; height: 100%">
<div id="content">
	<h1><?php echo _('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<h2><?php echo _('Choose a 4chan board:'); ?></h2>
	<p>
	<?php
		$board_urls = array();
		foreach ($boards as $key => $board)
		{
			$board_urls[] = '<a href="' . $board->href() . '">/'.$board->shortname.'/</a>';
		}
		echo implode(' &nbsp; ', $board_urls)
	?>
	</p>
</div>