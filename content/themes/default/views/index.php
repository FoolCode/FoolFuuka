<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

	<nav class="index_nav clearfix">
	<h1><?= get_setting('fs_gen_index_title', FOOL_PREF_GEN_INDEX_TITLE); ?></h1>
	<?php

		$index_nav = array();

		if ($this->radix->get_archives())
		{
			$index_nav['archives'] = array(
				'title' => __('Archives'),
				'elements' => array()
			);

			foreach ($this->radix->get_archives() as $key => $item)
			{
				$index_nav['archives']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		if ($this->radix->get_boards())
		{
			$index_nav['boards'] = array(
				'title' => __('Boards'),
				'elements' => array()
			);

			foreach ($this->radix->get_boards() as $key => $item)
			{
				$index_nav['boards']['elements'][] = array(
					'href' => $item->href,
					'text' => '/' . $item->shortname . '/ <span class="help">' . $item->name . '</span>'
				);
			}
		}

		$index_nav = $this->plugins->run_hook('fu_themes_generic_index_nav_elements', array($index_nav), 'simple');
		$index_nav = $this->plugins->run_hook('fu_themes_default_index_nav_elements', array($index_nav), 'simple');

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
