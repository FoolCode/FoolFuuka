<?php

namespace Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolfuuka\Model\Comment;
use Foolz\Foolfuuka\Model\CommentSendingException;
use Foolz\Inet\Inet;
use Foolz\Plugin\Result;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeoipRegionLock extends Model
{
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->preferences = $context->getService('preferences');
    }

    public function blockCountryComment(Result $result)
    {
        /** @var Comment $obj */
        $obj = $result->getObject();

        // globally allowed and disallowed
        $allow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.allow_comment');
        $disallow = $this->preferences->get('foolfuuka.plugins.geoip_region_lock.disallow_comment');

        $board_allow = trim($obj->radix->getValue('plugin_geo_ip_region_lock_allow_comment'), " ,");
        $board_disallow = trim($obj->radix->getValue('plugin_geo_ip_region_lock_disallow_comment'), " ,");

        // allow board settings to override global
        if ($board_allow || $board_disallow) {
            $allow = $board_allow;
            $disallow = $board_disallow;
        }

        if ($allow || $disallow) {
            $ip = Inet::dtop($obj->comment->poster_ip);

            $reader = new Reader($this->preferences->get('foolframe.maxmind.geoip2_db_path'));

            $country = null;

            try {
                $record = $reader->country($ip);
                $country = strtolower($record->country->isoCode);
            } catch(AddressNotFoundException $e) {
                $country = 'xx';
            }

            if ($allow) {
                $allow = array_filter(explode(',', $allow));

                foreach($allow as $al) {
                    if (strtolower(trim($al)) === $country)
                        return;
                }

                throw new CommentSendingException(_i('Your nation has been blocked from posting.').
                    '<br/><br/>This product includes GeoLite2 data created by MaxMind, available from http://www.maxmind.com/');
            }

            if ($disallow) {
                $disallow = array_filter(explode(',', $disallow));

                foreach ($disallow as $disal) {
                    if (strtolower(trim($disal)) == $country) {
                        throw new CommentSendingException(_i('Your nation has been blocked from posting.').
                            '<br/><br/>This product includes GeoLite2 data created by MaxMind, available from http://www.maxmind.com/');
                    }
                }
            }
        }
    }

    public function blockCountryView(Result $result)
    {
        $result->getParam('request');

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
