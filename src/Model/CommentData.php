<?php

namespace Foolz\FoolFuuka\Model;


class CommentData extends Data
{
    public $doc_id = 0;
    public $poster_ip = null;
    public $num = 0;
    public $subnum = 0;
    public $thread_num = 0;
    public $op = 0;
    public $timestamp = 0;
    public $timestamp_expired = 0;
    public $capcode = 'N';
    public $email = null;
    public $name = null;
    public $trip = null;
    public $title = null;
    public $comment = null;
    protected $delpass = null;
    public $poster_hash = null;
    public $poster_country = null;
    public $sticky = false;
    public $locked = false;
    public $deleted = false;

    public $nreplies = null;
    public $nimages = null;

    public $fourchan_date = false;
    public $comment_sanitized = false;
    public $comment_processed = false;
    public $formatted = false;
    public $title_processed = false;
    public $name_processed = false;
    public $email_processed = false;
    public $trip_processed = false;
    public $poster_hash_processed = false;
    public $poster_country_name = false;
    public $poster_country_name_processed = false;

    private $archive_timezone = false;

    public function getPostNum($separator = ',')
    {
        return $this->num.($this->subnum ? $separator.$this->subnum : '');
    }

    /**
     * @param $delpass
     */
    public function setDelpass($delpass)
    {
        $this->delpass = $delpass;
    }

    /**
     * @return string
     */
    public function getDelpass()
    {
        return $this->delpass;
    }

    /**
     * @param $bool
     */
    public function setArchiveTimezone($bool)
    {
        $this->archive_timezone = $bool;
    }

    /**
     * @return bool
     */
    public function isArchiveTimezone()
    {
        return $this->archive_timezone;
    }

    public function export()
    {
        $result = [];
        foreach ($this as $key => $value) {
            if ($key !== 'delpass' && $key !== 'archive_timezone') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Removes extra unnecessary data that can be recreated, to save memory
     */
    public function clean()
    {
        $this->fourchan_date = false;
        $this->comment_sanitized = false;
        $this->comment_processed = false;
        $this->formatted = false;
        $this->title_processed = false;
        $this->name_processed = false;
        $this->email_processed = false;
        $this->trip_processed = false;
        $this->poster_hash_processed = false;
        $this->poster_country_name = false;
        $this->poster_country_name_processed = false;
    }
}
