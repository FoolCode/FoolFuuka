<?php

namespace Foolfuuka\Plugins\NginxCachePurge;

if ( ! defined('DOCROOT'))
	exit('No direct script access allowed');

class NginxCachePurge extends \Plugins
{

	public static function beforeDeleteMedia($result)
	{
		$post = $result->getObject();
		$params = $result->getParams();
		$media = isset($params[0]) ? $params[0] : true;
		$thumb = isset($params[1]) ? $params[1] : true;

		$dir = [];

		if ($media)
		{
			$dir['full'] = $post->getLink(false, true);
		}

		if ($thumb)
		{
			$dir['thumb'] = $post->getLink(true, true);
		}

		$url_user_password = static::parseUrls();

		foreach ($url_user_password as $item)
		{
			foreach ($dir as $d)
			{
				// getLink gives null on failure
				if ($d === null)
				{
					continue;
				}

				$options = ['driver' => 'curl'];
				if (isset($item['password']))
				{
					$options['user'] = $item['user'];
					$options['pass'] = $item['pass'];
					$options['auth'] = 'any';
				}

				\Request::forge($item['url'] . parse_url($d, PHP_URL_PATH), $options)->execute();
			}
		}

		return;
	}

	public static function parseUrls()
	{
		$text = \Preferences::get('fu.plugins.nginx_cache_purge.urls');

		if ( ! $text)
		{
			return [];
		}

		$lines = preg_split('/\r\n|\r|\n/', $text);

		$lines_exploded = [];

		foreach($lines as $key => $line)
		{
			$explode = explode(':', $line);

			if (count($explode) == 0)
			{
				continue;
			}

			if (count($explode) > 1)
				$lines_exploded[$key]['url'] = rtrim(array_shift($explode) . ':' . array_shift($explode), '/');

			if (count($explode) > 1)
			{
				$lines_exploded[$key]['user'] = array_shift($explode);
				$lines_exploded[$key]['pass'] = implode(':', $explode);
			}
		}

		return $lines_exploded;
	}
}