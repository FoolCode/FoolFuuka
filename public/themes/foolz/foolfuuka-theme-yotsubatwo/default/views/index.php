<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

	<nav class="index_nav clearfix">
	<h1><?= Preferences::get('fu.gen_index_title'); ?></h1>
	<?php

		$index_nav = array();

		if (\Radix::getArchives())
		{
			$index_nav['archives'] = array(
				'title' => __('Archives'),
				'elements' => array()
			);

			foreach (\Radix::getArchives() as $key => $item)
			{
				$index_nav['archives']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		if (\Radix::getBoards())
		{
			$index_nav['boards'] = array(
				'title' => __('Boards'),
				'elements' => array()
			);

			foreach (\Radix::getBoards() as $key => $item)
			{
				$index_nav['boards']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		$index_nav = \Foolz\Plugin\Hook::forge('ff.themes.generic.index_nav_elements')->setParam('nav', $index_nav)->execute()->get($index_nav);
		$index_nav = \Foolz\Plugin\Hook::forge('fu.themes.default.index_nav_elements')->setParam('nav', $index_nav)->execute()->get($index_nav);

		foreach($index_nav as $item) : ?>
			<ul class="pull-left clearfix">
				<li><h2><?= $item['title'] ?></h2></li>
				<li>
					<ul>
						<?php foreach($item['elements'] as $i) : ?>
							<li><h3><a href="<?= $i['href'] ?>"><?= $i['text'] ?></a></h3></li>
						<?php endforeach; ?>
					</ul>
				</li>
			</ul>
		<?php endforeach; ?>
	</nav>
