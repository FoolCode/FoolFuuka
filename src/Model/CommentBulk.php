<?php
/**
 * Created by IntelliJ IDEA.
 * User: woxxy
 * Date: 26/04/14
 * Time: 00:18
 */

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

    public function clean()
    {
        $this->comment->clean();
        if ($this->media !== null) {
            $this->media->clean();
        }
    }

    public function getRadix()
    {
        return $this->radix;
    }
}
