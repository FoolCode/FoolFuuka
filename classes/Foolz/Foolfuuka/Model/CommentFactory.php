<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Context;

class CommentFactory extends Model
{
    /**
     * Array of post numbers found in the database
     *
     * @var  array
     */
    public $_posts = [];

    /**
     * Array of backlinks found in the posts
     *
     * @var  array
     */
    public $_backlinks_arr = [];

    public function fromArrayDeep(&$posts, $radix, $options = [])
    {
        $array = new \SplFixedArray(count($posts));
        foreach ($posts as $key => $post) {
            $array[$key] = new Comment($this->getContext(), $post, $radix, $options);
            unset($posts[$key]);
        }

        return $array;
    }


    public function fromArrayDeepApi(&$posts, $radix, $api, $options = [])
    {
        $array = new \SplFixedArray(count($posts));
        foreach ($posts as $key => $post) {
            $array[$key] = $this->forgeForApi($post, $radix, $api, $options);
            unset($posts[$key]);
        }

        return $array;
    }

    public function forgeForApi($post, $radix, $api, $options = [])
    {
        $comment = new Comment($this->getContext(), $post, $radix, $options);

        $fields = $comment->_forced_entries;

        if (isset($api['theme']) && $api['theme'] !== null) {
            $comment->_theme = $api['theme'];
            $fields[] = 'getFormatted';
        }

        foreach ($fields as $var => $method) {
            $comment->$method();
        }

        // also spawn media variables
        if ($comment->media !== null) {
            // if we come across a banned image we set all the data to null. Normal users must not see this data.
            if (($comment->media->banned && !\Auth::has_access('media.see_banned'))
                || ($comment->media->radix->hide_thumbnails && !\Auth::has_access('media.see_hidden')))
            {
                $banned = [
                    'media_id' => 0,
                    'spoiler' => false,
                    'preview_orig' => null,
                    'preview_w' => 0,
                    'preview_h' => 0,
                    'media_filename' => null,
                    'media_w' => 0,
                    'media_h' => 0,
                    'media_size' => 0,
                    'media_hash' => null,
                    'media_orig' => null,
                    'exif' => null,
                    'total' => 0,
                    'banned' => 0,
                    'media' => null,
                    'preview_op' => null,
                    'preview_reply' => null,

                    // optionals
                    'safe_media_hash' => null,
                    'remote_media_link' => null,
                    'media_link' => null,
                    'thumb_link' => null,
                ];

                foreach ($banned as $key => $item) {
                    $comment->media->$key = $item;
                }
            }

            // startup variables and put them also in the lower level for compatibility with older 4chan X
            foreach ([
                 'safe_media_hash' => 'getSafeMediaHash',
                 'media_filename_processed' => 'getMediaFilenameProcessed',
                 'media_link' => 'getMediaLink',
                 'remote_media_link' => 'getRemoteMediaLink',
                 'thumb_link' => 'getThumbLink'
             ] as $var => $method)
            {
                $comment->media->$method($api['request']);
            }


            unset($comment->media->radix);
        }

        if (isset($api['board']) && !$api['board']) {
            unset($comment->radix);
        }

        unset($comment->_theme);
        unset($comment->_forced_entries);

        // remove controller method
        unset($comment->_controller_method);

        // remove radix data
        unset($comment->extra->radix);

        // remove extra radix data
        unset($comment->current_board_for_prc);

        // we don't have captcha in use in api
        unset($comment->recaptcha_challenge, $comment->recaptcha_response);

        return $comment;
    }
}