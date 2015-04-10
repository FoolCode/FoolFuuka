<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\Model;

/**
 * Thrown when there's no results from database or the value domain hasn't been respected
 */
class BanException extends \Exception {}

/**
 * Thrown when there's no results from the database
 */
class BanNotFoundException extends BanException {}

/**
 * Manages the bans
 */
class Ban extends Model
{
    /**
     * Autoincremented ID
     *
     * @var  int
     */
    public $id = 0;

    /**
     * Decimal IP of the banned user
     *
     * @var  string  A numeric string representing the decimal IP
     */
    public $ip = 0;

    /**
     * Explanation of why the user has been banned
     *
     * @var  string
     */
    public $reason = '';

    /**
     * The starting point of the ban in UNIX time
     *
     * @var  int
     */
    public $start = 0;

    /**
     * The length of the ban in seconds
     *
     * @var  int
     */
    public $length = 0;

    /**
     * The board the user has been banned from. 0 is a global ban
     *
     * @var  int  The board ID, otherwise 0 for global ban
     */
    public $board_id = 0;

    /**
     * The author of the ban as defined by the login system
     *
     * @var  int
     */
    public $creator_id = 0;

    /**
     * The plea by the user to get unbanned
     *
     * @var  string
     */
    public $appeal = '';

    /**
     * The status of the appeal
     *
     * @var  int  Based on the class constants APPEAL_*
     */
    public $appeal_status = 0;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * Appeal statuses for the appeal_status field
     */
    const APPEAL_NONE = 0;
    const APPEAL_PENDING = 1;
    const APPEAL_REJECTED = 2;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->uri = $context->getService('uri');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
    }

    /**
     * Adds the appeal message to the ban. Only one appeal is allowed
     *
     * @param   string  $appeal  The appeal submitted by the user
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     */
    public function appeal($appeal)
    {
        $this->dc->qb()
            ->update($this->dc->p('banned_posters'))
            ->where('id = :id')
            ->set('appeal', ':appeal')
            ->set('appeal_status', static::APPEAL_PENDING)
            ->setParameter(':id', $this->id)
            ->setParameter(':appeal', $appeal)
            ->execute();

        return $this;
    }

    /**
     * Sets the flag to deny the appeal
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     */
    public function appealReject()
    {
        $this->dc->qb()
            ->update($this->dc->p('banned_posters'))
            ->where('id = :id')
            ->set('appeal_status', static::APPEAL_REJECTED)
            ->setParameter(':id', $this->id)
            ->execute();

        return $this;
    }

    /**
     * Remove the entry for a ban (unban)
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     */
    public function delete()
    {
        $this->dc->qb()
            ->delete($this->dc->p('banned_posters'))
            ->where('id = :id')
            ->setParameter(':id', $this->id)
            ->execute();

        return $this;
    }

    public function getMessage()
    {
        if ($this->board_id == 0) {
            $message = _i('It looks like you were banned on all boards.');
        } else {
            $message = _i('It looks like you were banned on /'.$this->radix_coll->getById($this->board_id)->shortname.'/.');
        }

        if ($this->length) {
            $message .= ' '._i('This ban will last until:').' '.date(DATE_COOKIE, $this->start + $this->length).'.';
        } else {
            $message .= ' '._i('This ban will last forever.');
        }

        if ($this->reason) {
            $message .= ' '._i('The reason for this ban is:').' «'.$this->reason.'».';
        }

        if ($this->appeal_status == Ban::APPEAL_NONE) {
            $message .= ' '._i(
                    'If you wish to appeal your ban, go to the "%s" page.',
                    '<a href="'.$this->uri->create('_/appeal_ban').'">'._i('appeal ban').'</a>'
                );
        } elseif ($this->appeal_status == Ban::APPEAL_PENDING) {
            $message .= ' '._i('Your appeal is pending.');
        }

        return $message;
    }
}
