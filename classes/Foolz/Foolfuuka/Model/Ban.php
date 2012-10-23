<?php

namespace Foolz\Foolfuuka\Model;

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
class Ban
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
	 * The board to which this ban is referred to. 0 is a global ban
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
	 * Appeal statuses for the appeal_status field
	 */
	const APPEAL_NONE = 0;
	const APPEAL_PENDING = 1;
	const APPEAL_REJECTED = 2;

	/**
	 * Converts an array into a Ban object
	 *
	 * @param   array  $array  The array from database
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public static function fromArray($array)
	{
		$new = new static();

		foreach ($array as $key => $item)
		{
			$new->$key = $item;
		}

		return $new;
	}

	/**
	 * Takes an array of arrays to create Ban objects
	 *
	 * @param   array  $array  The array from database
	 *
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Ban with as key the board_id
	 */
	public static function fromArrayDeep($array)
	{
		$new = [];

		foreach ($array as $item)
		{
			// use the board_id as key
			$obj = static::fromArray($item);
			$new[$obj->board_id] = $obj;
		}

		return $new;
	}

	/**
	 * Get the Ban object by ID
	 *
	 * @param   int  $id  The Ban id
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 * @throws  BanNotFoundException
	 */
	public static function getById($id)
	{
		$result = \DC::qb()
			->select('*')
			->from(\DC::p('banned_posters'), 'u')
			->where('u.id = :id')
			->setParameter(':id', $id)
			->execute()
			->fetch();

		if ( ! $result)
		{
			throw new BanNotFoundException(__('The ban could not be found.'));
		}

		return static::fromArray($result);
	}

	/**
	 * Get the Ban(s) pending on one IP
	 *
	 * @param   int  $decimal_ip  The user IP in decimal form
	 *
	 * @return  array
	 * @throws  BanNotFoundException
	 */
	public static function getByIp($decimal_ip)
	{
		$result = \DC::qb()
			->select('*')
			->from(\DC::p('banned_posters'), 'u')
			->where('u.ip = :ip')
			->setParameter(':ip', $decimal_ip)
			->execute()
			->fetchAll();

		if ( ! count($result))
		{
			throw new BanNotFoundException(__('The ban could not be found.'));
		}

		return static::fromArrayDeep($result);
	}

	/**
	 * Returns a list of bans by page and with a custom ordering
	 *
	 * @param   string  $order_by  The column to order by
	 * @param   string  $order     The direction of the ordering
	 * @param   int     $page      The page to fetch
	 * @param   int     $per_page  The number of entries per page
	 *
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Ban
	 */
	public static function getPagedBy($order_by, $order, $page, $per_page = 30)
	{
		$result = \DC::qb()
			->select('*')
			->from(\DC::p('banned_posters'), 'u')
			->orderBy($order_by, $order)
			->setMaxResults($per_page)
			->setFirstResult(($page * $per_page) - $per_page)
			->execute()
			->fetchAll();

		return static::fromArrayDeep($result);
	}

	/**
	 * Returns a list of bans with an appended appeal by page and with a custom ordering
	 *
	 * @param   string  $order_by  The column to order by
	 * @param   string  $order     The direction of the ordering
	 * @param   int     $page      The page to fetch
	 * @param   int     $per_page  The number of entries per page
	 *
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Ban
	 */
	public static function getAppealsPagedBy($order_by, $order, $page, $per_page = 30)
	{
		$result = \DC::qb()
			->select('*')
			->from(\DC::p('banned_posters'), 'u')
			->where('appeal_status = :appeal_status')
			->orderBy($order_by, $order)
			->setMaxResults($per_page)
			->setFirstResult(($page * $per_page) - $per_page)
			->setParameter(':appeal_status', static::APPEAL_PENDING)
			->execute()
			->fetchAll();

		return static::fromArrayDeep($result);
	}

	/**
	 * Check if an user is banned on any board
	 *
	 * @param   string  $decimal_ip
	 * @param   array   $board
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban|boolean
	 */
	public static function isBanned($decimal_ip, $board)
	{
		try
		{
			$bans = static::getByIp($decimal_ip);
		}
		catch (BanException $e)
		{
			return false;
		}

		// check for global ban
		if(isset($bans[0]))
		{
			$ban = $bans[0];
		}
		else if (isset($bans[$board->id]))
		{
			$ban = $bans[$board->id];
		}
		else
		{
			return false;
		}

		// if length = 0 then we have a permaban
		if ( ! $ban->length || $ban->start + $ban->length > time())
		{
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
	 * @param   int     $length      The lengthof the ban in seconds
	 * @param   array   $board_ids   The array of board IDs, global ban if left empty
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 * @throws  \Foolz\Foolfuuka\Model\BanException
	 */
	public static function add($ip_decimal, $reason, $length, $board_ids = [])
	{
		// 0 is a global ban
		if (empty($board_ids))
		{
			$board_ids = [0];
		}
		else
		{
			// check that all ids are existing boards
			$valid_board_ids = [];
			foreach (\Radix::getAll() as $board)
			{
				$valid_board_ids[] = $board->id;
			}

			foreach ($board_ids as $id)
			{
				if ( ! in_array($id, $valid_board_ids))
				{
					throw new BanException(__('You inserted a non-existant board ID.'));
				}
			}
		}

		if ( ! ctype_digit((string) $ip_decimal))
		{
			throw new BanException(__('You inserted an invalid IP.'));
		}

		if (mb_strlen($reason) > 10000)
		{
			throw new BanException(__('You inserted a too long reason for the ban.'));
		}

		if ( ! ctype_digit($length))
		{
			throw new BanException(__('You inserted an invalid length for the ban.'));
		}

		$time = time();

		$objects = [];

		try
		{
			$old = static::getByIp($ip_decimal);
		}
		catch (BanNotFoundException $e)
		{
			$old = false;
		}

		foreach ($board_ids as $board_id)
		{
			$new = new static();

			$new->ip = $ip_decimal;
			$new->reason = $reason;
			$new->start = $time;
			$new->length = $length;
			$new->board_id = $board_id;
			// the user_id is an array of (FoolAuth,xyz)
			$user_id = \Auth::get_user_id();
			$new->creator_id = $user_id[1];

			if (isset($old[$new->board_id]))
			{
				\DC::qb()
					->update(\DC::p('banned_posters'))
					->where('id = :id')
					->set('start', $new->start)
					->set('length', $new->length)
					->set('creator_id', $new->creator_id)
					->setParameter(':id', $old[$new->board_id]->id)
					->execute();
			}
			else
			{
				\DC::forge()
					->insert(\DC::p('banned_posters'), [
						'ip' => $new->ip,
						'reason' => $new->reason,
						'start' => $new->start,
						'length' => $new->length,
						'board_id' => $new->board_id,
						'creator_id' => $new->creator_id,
					]);
			}

			$objects[] = $new;
		}

		return $objects;
	}

	/**
	 * Remove the entry for a ban (unban)
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public function delete()
	{
		\DC::qb()
			->delete(\DC::p('banned_posters'))
			->where('id = :id')
			->setParameter(':id', $this->id)
			->execute();

		return $this;
	}

	/**
	 * Adds the appeal message to the ban. Only one appeal is allowed
	 *
	 * @param   string  $appeal  The appeal test by the user
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public function appeal($appeal)
	{
		\DC::qb()
			->update(\DC::p('banned_posters'))
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
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public function appealReject()
	{
		\DC::qb()
			->update(\DC::p('banned_posters'))
			->where('id = :id')
			->set('appeal_status', static::APPEAL_REJECTED)
			->setParameter(':id', $this->id)
			->execute();

		return $this;
	}
}