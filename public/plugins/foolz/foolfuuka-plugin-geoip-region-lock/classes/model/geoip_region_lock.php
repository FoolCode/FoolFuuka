<?php

namespace Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeoipRegionLock
{

	public static function blockCountryComment($result)
	{
		$obj = $result->getObject();

		// globally allowed and disallowed
		$allow = \Preferences::get('foolfuuka.plugins.geoip_region_lock.allow_comment');
		$disallow = \Preferences::get('foolfuuka.plugins.geoip_region_lock.disallow_comment');

		$board_allow = trim($obj->board->getValue('plugin_geo_ip_region_lock_allow_comment'), " ,");
		$board_disallow = trim($obj->board->getValue('plugin_geo_ip_region_lock_disallow_comment'), " ,");

		// allow board settings to override global
		if ($board_allow || $board_disallow)
		{
			$allow = $board_allow;
			$disallow = $board_disallow;
		}

		if ($allow || $disallow)
		{
			$country = strtolower(\geoip_country_code_by_name(\Input::ip_decimal()));

			if ($allow)
			{
				$allow = array_filter(explode(',', $allow));

				foreach($allow as $al)
				{
					if (strtolower(trim($al)) == $country)
						return;
				}

				$result->set([
					'error' => __('Your nation has been blocked from posting.') .
						'<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
				]);
			}

			if ($disallow)
			{
				$disallow = array_filter(explode(',', $disallow));

				foreach ($disallow as $disal)
				{
					if (strtolower(trim($disal)) == $country)
					{
						$result->set([
							'error' => __('Your nation has been blocked from posting.') .
								'<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
						]);
					}
				}
			}
		}
	}

	public static function blockCountryView()
	{
		$allow = \Preferences::get('foolfuuka.plugins.geoip_region_lock.allow_view');
		$disallow = \Preferences::get('foolfuuka.plugins.geoip_region_lock.disallow_view');

		if ($allow || $disallow)
		{
			$country = strtolower(\geoip_country_code_by_name(\Input::ip_decimal()));
		}

		if ($allow)
		{
			$allow = explode(',', $allow);

			foreach($allow as $al)
			{
				if (strtolower(trim($al)) == $country)
					return null;
			}

			throw new NotFoundHttpException;
		}

		if ($disallow)
		{
			$disallow = explode(',', $disallow);

			foreach ($disallow as $disal)
			{
				if (strtolower(trim($disal)) == $country)
				{
					throw new NotFoundHttpException;
				}
			}
		}

		return null;
	}
}