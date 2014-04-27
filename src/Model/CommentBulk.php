<?php

namespace Foolz\Foolfuuka\Model;


class CommentBulk implements \JsonSerializable
{
    /**
     * @var Radix
     */
    protected $radix = null;

    /**
     * @var CommentData
     */
    public $comment = null;

    /**
     * @var MediaData
     */
    public $media = null;

    /**
     * @param Radix $radix
     * @param CommentData $comment
     * @param MediaData $media
     * @return static
     */
    public static function forge(Radix $radix, CommentData $comment = null, MediaData $media = null)
    {
        $new = new static();
        $new->radix = $radix;
        $new->comment = $comment;
        $new->media = $media;

        return $new;
    }

    /**
     * Implements \JsonSerializable interface
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $array = $this->comment->export();

        if (!$this->radix->getContext()->getService('auth')->hasAccess('comment.see_ip')) {
            unset($array['poster_ip']);
        }

        if ($this->media !== null) {
            $array['media'] = $this->media->export();
        } else {
            $array['media'] = null;
        }

        return $array;
    }

    /**
     * Imports from an array the keys that match the properties of the data objects
     *
     * @param array $data
     * @param Radix $radix
     */
    public function import(array $data, Radix $radix)
    {
        $this->radix = $radix;

        $this->comment = new CommentData();
        $this->comment->import($data);

        if (isset($data['media_id'])) {
            $this->media = new MediaData();
            $this->media->import($data);
        }
    }

    /**
     * Cleans all the generated fields of the data obkects
     */
    public function clean()
    {
        $this->comment->clean();
        if ($this->media !== null) {
            $this->media->clean();
        }
    }

    /**
     * @return Radix
     */
    public function getRadix()
    {
        return $this->radix;
    }
}
