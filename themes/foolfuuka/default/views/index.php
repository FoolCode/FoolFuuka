<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

	<nav class="index_nav clearfix">
	<h1><?= Preferences::get('fu.gen_index_title'); ?></h1>
	<?php

		$index_nav = array();

		if (Radix::get_archives())
		{
			$index_nav['archives'] = array(
				'title' => __('Archives'),
				'elements' => array()
			);

			foreach (Radix::get_archives() as $key => $item)
			{
				$index_nav['archives']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		if (Radix::get_boards())
		{
			$index_nav['boards'] = array(
				'title' => __('Boards'),
				'elements' => array()
			);

			foreach (Radix::get_boards() as $key => $item)
			{
				$index_nav['boards']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		$index_nav = Plugins::run_hook('fu.themes.generic.index_nav_elements', array($index_nav), 'simple');
		$index_nav = Plugins::run_hook('fu.themes.default.index_nav_elements', array($index_nav), 'simple');

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
