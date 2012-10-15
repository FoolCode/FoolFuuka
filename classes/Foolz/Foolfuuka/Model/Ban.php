<?php

namespace Foolz\Foolfuuka\Model;

/**
 * Thrown when there's no results from database or the value domain hasn't been respected
 */
class BanException extends \FuelException {}

/**
 * Thrown when there's no results from the database
 */
class BanNotFoundException extends BanException {}

/**
 * Manages the bans
 */
class Ban
{
	public $id = 0;
	public $ip = 0;
	public $reason = '';
	public $start = 0;
	public $length = 0;
	public $board_id = 0;
	public $creator_id = 0;

	const APPEAL_NONE = 0;
	const APPEAL_PENDING = 1;
	const APPEAL_REJECTED = 2;

	/**
	 * Converts an array into a Ban object
	 *
	 * @param   array  $array  The array from database
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
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Ban with as key the board_id
	 */
	public static function fromArrayDeep($array)
	{
		$new = [];

		foreach ($array as $key => $item)
		{
			// use the board_id as key
			$obj =  static::fromArray($item);
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
	public static function get_by_id($id)
	{
		$result = \DC::qb()
			->select('*')
			->from('banned_posters', 'u')
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
	 * @return  array
	 * @throws  BanNotFoundException
	 */
	public static function get_by_ip($decimal_ip)
	{
		$result = \DC::qb()
			->select('*')
			->from('banned_posters', 'u')
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

	public static function get_paged_by($order_by, $direction, $page, $per_page = 30)
	{
		$bans = \DB::select('*')
			->from('banned_posters')
			->order_by($order_by, $direction)
			->limit($per_page)
			->offset(($page * $per_page) - $per_page)
			->execute()
			->as_array();

		$ban_objects = [];
		foreach ($bans as $ban)
		{
			$new = new Ban();
			foreach ($ban as $k => $i)
			{
				$new->$k = $i;
			}
			$ban_objects[] = $new;
		}

		return $ban_objects;
	}


	public static function get_appeals_paged_by($order_by, $direction, $page, $per_page = 30)
	{
		$bans = \DB::select('*')
			->from('banned_posters')
			->where('appeal_status', '=', static::APPEAL_PENDING)
			->order_by($order_by, $direction)
			->limit($per_page)
			->offset(($page * $per_page) - $per_page)
			->execute()
			->as_array();

		$ban_objects = [];
		foreach ($bans as $ban)
		{
			$new = new Ban();
			foreach ($ban as $k => $i)
			{
				$new->$k = $i;
			}
			$ban_objects[] = $new;
		}

		return $ban_objects;
	}


	public static function is_banned($decimal_ip, $board)
	{
		try
		{
			$bans = static::get_by_ip($decimal_ip);
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


	public static function add($ip_decimal, $reason, $length, $board_ids = array())
	{
		// 0 is a global ban
		if (empty($board_ids))
		{
			$board_ids = array(0);
		}
		else
		{
			// check that all ids are existing boards
			$valid_board_ids = [];
			foreach (\Radix::get_all() as $board)
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

		if ( ! ctype_digit($ip_decimal))
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
			$old = static::get_by_ip($ip_decimal);
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
					->update('banned_posters')
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
					->insert('banned_posters', [
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
			->delete('banned_posters')
			->where('id = :id')
			->setParameter(':id', $this->id)
			->execute();

		return $this;
	}

	/**
	 * Adds the appeal message to the ban. Only one appeal is allowed
	 *
	 * @param   string  $appeal  The appeal test by the user
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public function appeal($appeal)
	{
		\DC::qb()
			->update('banned_posters')
			->where('id = :id')
			->set('appeal', $appeal)
			->set('appeal_status', static::APPEAL_PENDING)
			->setParameter(':id', $this->id)
			->execute();

		return $this;
	}

	/**
	 * Sets the flag to deny the appeal
	 *
	 * @return  \Foolz\Foolfuuka\Model\Ban
	 */
	public function appeal_reject()
	{
		\DC::qb()
			->update('banned_posters')
			->where('id = :id')
			->set('appeal_status', static::APPEAL_REJECTED)
			->setParameter(':id', $this->id)
			->execute();

		return $this;
	}
}