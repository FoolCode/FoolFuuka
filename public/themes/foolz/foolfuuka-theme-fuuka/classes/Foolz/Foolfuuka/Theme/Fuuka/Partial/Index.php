<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Partial;

class Index extends \Foolz\Theme\View
{
	public function toString()
	{
		?>
		<div id="content">
			<h1><?= \Preferences::get('foolframe.gen.website_title'); ?></h1>
			<h2><?= _i('Choose a Board:'); ?></h2>
			<p>
				<?php
				$board_urls = array();
				foreach (\Radix::getAll() as $key => $item)
				{
					array_push($board_urls, '<a href="' . $item->getValue('href') . '" title="' . $item->name . '">/' . $item->shortname . '/</a>');
				}
				echo implode(' ', $board_urls);
				?>
			</p>
		</div>
	<?php
	}
}
