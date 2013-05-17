<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Config\Config;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class Framework
{
	public function __construct(\Foolz\Foolframe\Model\Framework $framework)
	{
		class_alias('Foolz\\Foolfuuka\\Model\\Ban', 'Ban');
		class_alias('Foolz\\Foolfuuka\\Model\\Board', 'Board');
		class_alias('Foolz\\Foolfuuka\\Model\\Comment', 'Comment');
		class_alias('Foolz\\Foolfuuka\\Model\\CommentInsert', 'CommentInsert');
		class_alias('Foolz\\Foolfuuka\\Model\\Extra', 'Extra');
		class_alias('Foolz\\Foolfuuka\\Model\\Media', 'Media');
		class_alias('Foolz\\Foolfuuka\\Model\\Radix', 'Radix');
		class_alias('Foolz\\Foolfuuka\\Model\\Report', 'Report');
		class_alias('Foolz\\Foolfuuka\\Model\\Search', 'Search');

		\Autoloader::add_classes([
			'StringParser_BBCode' => __DIR__.'/../../../../packages/stringparser-bbcode/library/stringparser_bbcode.class.php',
		]);

		$framework->getRouteCollection()->add('foolfuuka.root', new Route(
			'/',
			['_controller' => '\Foolz\Foolfuuka\Controller\Chan::index']
		));

		$radix_all = \Foolz\Foolfuuka\Model\Radix::getAll();

		foreach ($radix_all as $radix)
		{
			$framework->getRouteCollection()->add(
				'foolfuuka.chan.radix.'.$radix->shortname, new Route(
				'/'.$radix->shortname.'/{_suffix}',
				[
					'_suffix' => '',
					'_controller' => '\Foolz\Foolfuuka\Controller\Chan::page',
					'radix_shortname' => $radix->shortname
				],
				[
					'_suffix' => '.*'
				]
			));
		}

		$framework->getRouteCollection()->add(
			'foolfuuka.chan.api', new Route(
			'/_/api/chan/{_suffix}',
			[
				'_suffix' => '',
				'_controller' => '\Foolz\Foolfuuka\Controller\Api\Chan::*',
			],
			[
				'_suffix' => '.*'
			]
		));

		$framework->getRouteCollection()->add(
			'foolfuuka.chan._', new Route(
			'/_/{_suffix}',
			[
				'_suffix' => '',
				'_controller' => '\Foolz\Foolfuuka\Controller\Chan::page',
			],
			[
				'_suffix' => '.*'
			]
		));


		if (\Auth::has_access('comment.reports'))
		{
			\Foolz\Foolfuuka\Model\Report::preload();
		}

		$theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');
		$theme_instance->addDir('foolz', VENDPATH.'foolz/foolfuuka/'.\Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'directories.themes'));
		$theme_instance->setBaseUrl(\Uri::base().'foolfuuka/');
		$theme_instance->setPublicDir(DOCROOT.'foolfuuka/');

		// set an ->enabled on the themes we want to use
		if (\Auth::has_access('maccess.admin'))
		{
			\Foolz\Plugin\Event::forge('Foolz\Foolframe\Model\System::environment.result')
				->setCall(function($result) {
					$environment = $result->getParam('environment');

					foreach (\Foolz\Config\Config::get('foolz/foolfuuka', 'environment') as $section => $data)
					{
						foreach ($data as $k => $i)
						{
							array_push($environment[$section]['data'], $i);
						}
					}

					$result->setParam('environment', $environment)->set($environment);
				})->setPriority(0);

			foreach ($theme_instance->getAll('foolz') as $theme)
			{
				$theme->enabled = true;
			}
		}
		else
		{
			if ($themes_enabled = \Foolz\Foolframe\Model\Preferences::get('foolfuuka.theme.active_themes'))
			{
				$themes_enabled = unserialize($themes_enabled);
			}
			else
			{
				$themes_enabled = ['foolz/foolfuuka-theme-foolfuuka' => 1];
			}

			foreach ($themes_enabled as $key => $item)
			{
				if ( ! $item && ! \Auth::has_access('maccess.admin'))
				{
					continue;
				}

				try
				{
					$theme = $theme_instance->get('foolz', $key);
					$theme->enabled = true;
				}
				catch (\OutOfBoundsException $e)
				{

				}
			}
		}

		try
		{
			$theme_name = \Input::get('theme', \Cookie::get('theme')) ? : \Preferences::get('foolfuuka.theme.default');
			$theme = $theme_instance->get('foolz', $theme_name);
			if ( ! isset($theme->enabled) || ! $theme->enabled)
			{
				throw new \OutOfBoundsException;
			}
		}
		catch (\OutOfBoundsException $e)
		{
			$theme_name = 'foolz/foolfuuka-theme-foolfuuka';
			$theme = $theme_instance->get('foolz', 'foolz/foolfuuka-theme-foolfuuka');
		}

		$theme->bootstrap();

	}
}