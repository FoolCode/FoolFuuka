<?php

namespace Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeoipRegionLock extends Model
{
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->preferences = $context->getService('preferences');
    }

    public function blockCountryComment($result)
    {
        $obj = $result->getObject();

        // globally allowed and disallowed
        $allow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.allow_comment');
        $disallow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.disallow_comment');

        $board_allow = trim($obj->board->getValue('plugin_geo_ip_region_lock_allow_comment'), " ,");
        $board_disallow = trim($obj->board->getValue('plugin_geo_ip_region_lock_disallow_comment'), " ,");

        // allow board settings to override global
        if ($board_allow || $board_disallow) {
            $allow = $board_allow;
            $disallow = $board_disallow;
        }

        if ($allow || $disallow) {
            // @todo get client ip
            $country = strtolower(\geoip_country_code_by_name($request->getClientIp()));

            if ($allow) {
                $allow = array_filter(explode(',', $allow));

                foreach($allow as $al) {
                    if (strtolower(trim($al)) == $country)
                        return;
                }

                $result->set([
                    'error' => _i('Your nation has been blocked from posting.') .
                        '<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
                ]);
            }

            if ($disallow) {
                $disallow = array_filter(explode(',', $disallow));

                foreach ($disallow as $disal) {
                    if (strtolower(trim($disal)) == $country) {
                        $result->set([
                            'error' => _i('Your nation has been blocked from posting.') .
                                '<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
                        ]);
                    }
                }
            }
        }
    }

    public function blockCountryView(Request $request, $result)
    {
        $allow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.allow_view');
        $disallow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.disallow_view');

        if ($allow || $disallow) {
            $country = strtolower(\geoip_country_code_by_name($request->getClientIp()));
        }

        if ($allow) {
            $allow = explode(',', $allow);

            foreach($allow as $al) {
                if (strtolower(trim($al)) == $country)
                    return null;
            }

            $result->set(new Response(_i('Not available in your country.'), 403));
            return;
        }

        if ($disallow) {
            $disallow = explode(',', $disallow);

            foreach ($disallow as $disal) {
                if (strtolower(trim($disal)) == $country) {
                    $result->set(new Response(_i('Not available in your country.'), 403));
                    throw new NotFoundHttpException;
                }
            }
        }

        return null;
    }
}
