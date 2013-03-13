<?php

require __DIR__ . '/functions.php';

\Autoloader::add_classes([
	'Foolz\Foolfuuka\Themes\Fuuka\Controller\Chan' => __DIR__.'/controller.php',
]);

\Foolz\Plugin\Event::forge('Fuel\Core\Router.parse_match.intercept')
	->setCall(function($result)
	{
		if ($result->getParam('controller') === 'Foolz\Foolfuuka\Controller\Chan')
		{
			// reroute everything that goes to Chan through the custom Chan controller
			$result->setParam('controller', 'Foolz\Foolfuuka\Themes\Fuuka\Controller\Chan');
			$result->set(true);
		}
	});

// use hooks for manipulating comments
\Foolz\Plugin\Event::forge('foolfuuka.comment_model.processComment.greentext_result')
	->setCall(function($result)
	{
		$html= '\\1<span class="greentext">\\2</span>\\3';;
		$result->setParam('html', $html)->set($html);
	})
	->setPriority(8);

\Foolz\Plugin\Event::forge('foolfuuka.comment_model.processInternalLinks.html_result')
	->setCall(function($result)
	{
		$data = $result->getParam('data');
		$html = [
			'tags' => ['<span class="unkfunc">', '</span>'],
			'hash' => '',
			'attr' => 'class="backlink" onclick="replyHighlight(' . $data->num . ');"',
			'attr_op' => 'class="backlink"',
			'attr_backlink' => 'class="backlink"',
		];

		$result->setParam('build_url', $html)->set($html);
	})
	->setPriority(8);

\Foolz\Plugin\Event::forge('foolfuuka.comment_model.processExternalLinks.html_result')
	->setCall(function($result)
	{
		$data = $result->getParam('data');
		$html = [
			'tags' => ['open' =>'<span class="unkfunc">', 'close' => '</span>'],
			'short_link' => '//boards.4chan.org/'.$data->shortname.'/',
			'query_link' => '//boards.4chan.org/'.$data->shortname.'/res/'.$data->query,
			'backlink_attr' => 'class="backlink"'
		];

		$result->setParam('build_url', $html)->set($html);
	})
	->setPriority(8);