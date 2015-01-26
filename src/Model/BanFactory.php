<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\Model;

class BanFactory extends Model
{
    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
    }

    /**
     * Converts an array into a Ban object
     *
     * @param   array  $array  The array from database
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     */
    public function fromArray($array)
    {
        $new = new Ban($this->getContext());

        foreach ($array as $key => $item) {
            $new->$key = $item;
        }

        return $new;
    }

    /**
     * Takes an array of arrays to create Ban objects
     *
     * @param   array  $array  The array from database
     *
     * @return  array  An array of \Foolz\FoolFuuka\Model\Ban with as key the board_id
     */
    public function fromArrayDeep($array)
    {
        $new = [];

        foreach ($array as $item) {
            // use the board_id as key
            $obj = $this->fromArray($item);
            $new[$item['board_id']] = $obj;
        }

        return $new;
    }

    /**
     * Get the object the user was banned for by ID
     *
     * @param   int  $id  The Ban id
     * @return  \Foolz\FoolFuuka\Model\Ban
     * @throws  BanNotFoundException
     */
    public function getById($id)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('banned_posters'), 'u')
            ->where('u.id = :id')
            ->setParameter(':id', $id)
            ->execute()
            ->fetch();

        if (!$result) {
            throw new BanNotFoundException(_i('The ban could not be found.'));
        }

        return $this->fromArray($result);
    }

    /**
     * Get the Ban(s) pending on one IP
     *
     * @param   int  $decimal_ip  The user IP in decimal form
     *
     * @return  array
     * @throws  BanNotFoundException
     */
    public function getByIp($decimal_ip)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('banned_posters'), 'u')
            ->where('u.ip = :ip')
            ->setParameter(':ip', $decimal_ip)
            ->execute()
            ->fetchAll();

        if (!count($result)) {
            throw new BanNotFoundException(_i('The ban could not be found.'));
        }

        return $this->fromArrayDeep($result);
    }

    /**
     * Returns a list of bans by page and with a custom ordering
     *
     * @param   string  $order_by  The column to order by
     * @param   string  $order     The direction of the ordering
     * @param   int     $page      The page to fetch
     * @param   int     $per_page  The number of entries per page
     *
     * @return  array  An array of \Foolz\FoolFuuka\Model\Ban
     */
    public function getPagedBy($order_by, $order, $page, $per_page = 30)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('banned_posters'), 'u')
            ->orderBy($order_by, $order)
            ->setMaxResults($per_page)
            ->setFirstResult(($page * $per_page) - $per_page)
            ->execute()
            ->fetchAll();

        return $this->fromArrayDeep($result);
    }

    /**
     * Returns a list of bans with an appended appeal by page and with a custom ordering
     *
     * @param   string  $order_by  The column to order by
     * @param   string  $order     The direction of the ordering
     * @param   int     $page      The page to fetch
     * @param   int     $per_page  The number of entries per page
     *
     * @return  array  An array of \Foolz\FoolFuuka\Model\Ban
     */
    public function getAppealsPagedBy($order_by, $order, $page, $per_page = 30)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('banned_posters'), 'u')
            ->where('appeal_status = :appeal_status')
            ->orderBy($order_by, $order)
            ->setMaxResults($per_page)
            ->setFirstResult(($page * $per_page) - $per_page)
            ->setParameter(':appeal_status', Ban::APPEAL_PENDING)
            ->execute()
            ->fetchAll();

        return $this->fromArrayDeep($result);
    }

    /**
     * Check if a user is banned on any board
     *
     * @param   string  $decimal_ip
     * @param   array   $board
     *
     * @return  \Foolz\FoolFuuka\Model\Ban|boolean
     */
    public function isBanned($decimal_ip, $board)
    {
        try {
            $bans = $this->getByIp($decimal_ip);
        } catch (BanException $e) {
            return false;
        }

        // check for global ban
        if (isset($bans[0])) {
            $ban = $bans[0];
        } elseif (isset($bans[$board->id])) {
            $ban = $bans[$board->id];
        } else {
            return false;
        }

        // if length = 0 then we have a permaban
        if (!$ban->length || $ban->start + $ban->length > time()) {
            // return the object, will be global if it's a global ban
            return $ban;
        }

        return false;
    }

    /**
     * Adds a new ban
     *
     * @param   string  $ip_decimal  The IP of the banned user in decimal format
     * @param   string  $reason      The reason for the ban
     * @param   int     $length      The length of the ban in seconds
     * @param   array   $board_ids   The array of board IDs, global ban if left empty
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     * @throws  \Foolz\FoolFuuka\Model\BanException
     */
    public function add($ip_decimal, $reason, $length, $board_ids = [])
    {
        // 0 is a global ban
        if (empty($board_ids)) {
            $board_ids = [0];
        } else {
            // check that all ids are existing boards
            $valid_board_ids = [];
            foreach ($this->radix_coll->getAll() as $board) {
                $valid_board_ids[] = $board->id;
            }

            foreach ($board_ids as $id) {
                if (!in_array($id, $valid_board_ids)) {
                    throw new BanException(_i('You entered a non-existent board ID.'));
                }
            }
        }

        if (!ctype_digit((string) $ip_decimal)) {
            throw new BanException(_i('You entered an invalid IP.'));
        }

        if (mb_strlen($reason, 'utf-8') > 10000) {
            throw new BanException(_i('You entered a too long reason for the ban.'));
        }

        if (!ctype_digit($length)) {
            throw new BanException(_i('You entered an invalid length for the ban.'));
        }

        $time = time();

        $objects = [];

        try {
            $old = $this->getByIp($ip_decimal);
        } catch (BanNotFoundException $e) {
            $old = false;
        }

        foreach ($board_ids as $board_id) {
            $new = new Ban($this->getContext());

            $new->ip = $ip_decimal;
            $new->reason = $reason;
            $new->start = $time;
            $new->length = $length;
            $new->board_id = $board_id;
            $new->creator_id = $this->getAuth()->getUser()->getId();
            $new->appeal = '';

            if (isset($old[$new->board_id])) {
                if ($new->length < $old[$new->board_id]->length) {
                    $new->length = $old[$new->board_id]->length;
                }

                $this->dc->qb()
                    ->update($this->dc->p('banned_posters'))
                    ->where('id = :id')
                    ->set('start', $new->start)
                    ->set('length', $new->length)
                    ->set('creator_id', $new->creator_id)
                    ->setParameter(':id', $old[$new->board_id]->id)
                    ->execute();
            } else {
                $this->dc->getConnection()
                    ->insert($this->dc->p('banned_posters'), [
                        'ip' => $new->ip,
                        'reason' => $new->reason,
                        'start' => $new->start,
                        'length' => $new->length,
                        'board_id' => $new->board_id,
                        'creator_id' => $new->creator_id,
                        'appeal' => $new->appeal
                    ]);
            }

            $objects[] = $new;
        }

        return $objects;
    }
}
