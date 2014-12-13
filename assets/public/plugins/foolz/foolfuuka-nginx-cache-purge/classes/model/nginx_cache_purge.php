<?php

namespace Foolz\Foolfuuka\Plugins\NginxCachePurge\Model;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Preferences;
use Foolz\Foolfuuka\Model\Media;

class NginxCachePurge extends Model
{
    /**
     * @var Preferences
     */
    protected $preferences;

    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->preferences = $context->getService('preferences');
    }

    public function beforeDeleteMedia($result)
    {
        /** @var Media $post */
        $post = $result->getObject();
        $dir = [];

        // purge full image
        try {
            $dir['full'] = $post->getDir(false, true, true);
        } catch (\Foolz\Foolfuuka\Model\MediaException $e) {

        }

        // purge thumbnail
        try {
            $post->op = 0;
            $dir['thumb-0'] = $post->getDir(true, true, true);
            $post->op = 1;
            $dir['thumb-1'] = $post->getDir(true, true, true);
        } catch (\Foolz\Foolfuuka\Model\MediaException $e) {

        }

        $urls = $this->parseUrls();

        foreach ($urls as $item) {
            foreach ($dir as $d) {
                // getLink gives null on failure
                if ($d === null) {
                    continue;
                }

                $ch = curl_init();
                $options = [
                    CURLOPT_URL => $item['url'].$d,
                    CURLOPT_RETURNTRANSFER => true
                ];

                if (isset($item['pass'])) {
                    $options = $options + [
                        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                        CURLOPT_USERPWD => $item['user'].':'.$item['pass']
                    ];
                }

                curl_setopt_array($ch, $options);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        return;
    }

    public function parseUrls()
    {
        $text = $this->preferences->get('foolfuuka.plugins.nginx_cache_purge.urls');

        if (!$text) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);

        $lines_exploded = [];

        foreach($lines as $key => $line) {
            $explode = explode('::', $line);

            if (count($explode) == 0) {
                continue;
            }

            if (count($explode) >= 1) {
                $lines_exploded[$key]['url'] = rtrim(array_shift($explode), '/');
            }

            if (count($explode) >= 1) {
                $lines_exploded[$key]['user'] = array_shift($explode);
                $lines_exploded[$key]['pass'] = array_shift($explode);
            }
        }

        return $lines_exploded;
    }
}
