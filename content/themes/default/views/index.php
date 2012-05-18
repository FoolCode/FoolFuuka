<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<article style="padding:50px;">
	<h1><?php echo __('Welcome on the 4chan Archiver "FoOlFuuka"'); ?></h1>
	<h2><?php
				$board_urls = array();
				$parenthesis_open = FALSE;
				foreach ($this->radix->get_archives() as $key => $item)
				{

					if(!$parenthesis_open)
					{
						echo 'Archives: [ ';
						$parenthesis_open = TRUE;
					}

					if (!$item->hide_thumbnails || $this->tank_auth->is_allowed())
					{
						$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a> <a href="' . $item->href . 'gallery/">+</a>';
					}
					else
					{
						$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
					}

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
				foreach ($this->radix->get_boards() as $key => $item)
				{
					if(!$parenthesis_open)
					{
						echo 'Boards: [ ';
						$parenthesis_open = TRUE;
					}

					if (!$item->hide_thumbnails || $this->tank_auth->is_allowed())
					{
						$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a> <a href="' . $item->href . 'gallery/">+</a>';
					}
					else
					{
						$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
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
