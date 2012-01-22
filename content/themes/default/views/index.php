<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>
<img src="<?php echo site_url() . 'content/themes/default/images/dat_cat.png' ?>" style="max-width:50%;position:absolute; top:-20px; right:30px;"/>

<article style="padding:50px;">
	<h1><?php echo _('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<h2><?php
				$board_urls = array();
				$parenthesis_open = FALSE;
				foreach ($boards as $key => $item)
				{
					if($item->archive == 0)
						continue;
						
					if(!$parenthesis_open)
					{
						echo 'Archives: [ ';
						$parenthesis_open = TRUE;
					}
					
					$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a> <a href="' . $item->href() . 'gallery/">+</a>';

				}
				echo implode(' / ', $board_urls);
				if($parenthesis_open)
				{
					echo ' ]';
					$parenthesis_open = FALSE;
				}
				?><br/>
				<?php
				$board_urls = array();
				$parenthesis_open = FALSE;
				foreach ($boards as $key => $item)
				{
					if($item->archive == 1)
						continue;
						
					if(!$parenthesis_open)
					{
						echo 'Boards: [ ';
						$parenthesis_open = TRUE;
					}
					if($item->archive == 1)
						continue;
					
					if ($item->thumbnails)
					{
						$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a> <a href="' . $item->href() . 'gallery/">+</a>';
					}
					else
					{
						$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a>';
					}
				}
				echo implode(' / ', $board_urls);
				if($parenthesis_open)
				{
					echo ' ]';
					$parenthesis_open = FALSE;
				}
				?>
	</h2>
</article>
