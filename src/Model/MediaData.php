<?php

namespace Foolz\Foolfuuka\Model;


class MediaData extends Data
{
    public $media_id = 0;
    public $spoiler = false;
    public $preview_orig = null;
    public $media = null;
    public $preview_op = null;
    public $preview_reply = null;
    public $preview_w = 0;
    public $preview_h = 0;
    public $media_filename = null;
    public $media_w = 0;
    public $media_h = 0;
    public $media_size = 0;
    public $media_hash = null;
    public $media_orig = null;
    public $exif = null;
    public $total = 0;
    public $banned = false;

    /**
     * Caches media_status value
     *
     * @var  false|int  false if not yet cached
     */
    public $media_status = false;

    /**
     * Caches the media hash converted for URLs
     *
     * @var  false|string  false if not yet cached
     */
    public $safe_media_hash = false;

    /**
     * Caches the remote media link, an URL to external resources
     *
     * @var  false|string  false if not yet cached
     */
    public $remote_media_link = false;

    /**
     * Caches the media link, the direct URL to the resource
     *
     * @var  false|null|string  false if not cached, null if not found, string if found
     */
    public $media_link = false;

    /**
     * Caches the thumb link, the direct URL to the thumbnail
     *
     * @var  false|null|string  false if not cached, null if not found, string if found
     */
    public $thumb_link = false;

    /**
     * Caches the sanitized media filename
     *
     * @var  false|string  false if not cached
     */
    public $media_filename_processed = false;

    public function clean()
    {
        $this->media_status = false;
        $this->safe_media_hash = false;
        $this->remote_media_link = false;
        $this->media_link = false;
        $this->thumb_link = false;
        $this->media_filename_processed = false;
    }
}
