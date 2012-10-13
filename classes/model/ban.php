<?php

namespace FoolFuuka\Model;

class BanException extends \FuelException {}
class BanNotFoundException extends BanException {}
class BanReasonTooLongException extends BanException {}
class BanInvalidLengthException extends BanException {}

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

	public static function get_by_id($id)
	{
		$result = \DB::select()
			->from('banned_posters')
			->where('id', $id)
			->as_object()
			->execute()
			->current();

		if ( ! $result)
		{
			throw new BanNotFoundException(__('The ban could not be found.'));
		}

		$new = new static();
		foreach ($result as $key => $item)
		{
			$new->$key = $item;
		}

		return $new;
	}

	public static function get_by_ip($decimal_ip)
	{
		$result = \DB::select()
			->from('banned_posters')
			->where('ip', $decimal_ip)
			->as_object()
			->execute()
			->as_array();

		if ( ! count($result))
		{
			throw new BanNotFoundException(__('The ban could not be found.'));
		}

		$objects = array();

		foreach ($result as $r)
		{
			$new = new static();

			foreach ($r as $key => $item)
			{
				$new->$key = $item;
			}

			// handy to use the board_id as key
			$objects[$new->board_id] = $new;
		}

		return $objects;
	}

	public static function get_paged_by($order_by, $direction, $page, $per_page = 30)
	{
		$bans = \DB::select()
			->from('banned_posters')
			->order_by($order_by, $direction)
			->limit($per_page)
			->offset(($page * $per_page) - $per_page)
			->execute()
			->as_array();

		$ban_objects = array();
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
		$bans = \DB::select()
			->from('banned_posters')
			->where('appeal_status', '=', static::APPEAL_PENDING)
			->order_by($order_by, $direction)
			->limit($per_page)
			->offset(($page * $per_page) - $per_page)
			->execute()
			->as_array();

		$ban_objects = array();
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
			$valid_board_ids = array();
			foreach (\Radix::get_all() as $board)
			{
				$valid_board_ids[] = $board->id;
			}

			foreach ($board_ids as $id)
			{
				if ( ! in_array($id, $valid_board_ids))
				{
					throw new BanInvalidBoardId(__('You inserted a non-existant board ID.'));
				}
			}
		}

		if ( ! ctype_digit($ip_decimal))
		{
			throw new BanInvalidIpException(__('You inserted an invalid IP.'));
		}

		if (mb_strlen($reason) > 10000)
		{
			throw new BanReasonTooLongException(__('You inserted a too long reason for the ban.'));
		}

		if ( ! ctype_digit($length))
		{
			throw new BanInvalidLengthException(__('You inserted an invalid length for the ban.'));
		}

		$time = time();

		$objects = array();

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
				\DB::update('banned_posters')
					->where('id', $old[$new->board_id]->id)
					->value('start', $new->start)
					->value('length', $new->length)
					->value('creator_id', $new->creator_id)
					->execute();
			}
			else
			{
				\DB::insert('banned_posters')
					->set(array(
						'ip' => $new->ip,
						'reason' => $new->reason,
						'start' => $new->start,
						'length' => $new->length,
						'board_id' => $new->board_id,
						'creator_id' => $new->creator_id,
					))->execute();
			}

			$objects[] = $new;
		}

		return $objects;
	}

	/**
	 * Remove the entry for a ban (unban)
	 */
	public function delete()
	{
		\DB::delete('banned_posters')
			->where('id', '=', $this->id)
			->execute();
	}

	/**
	 * Adds the appeal message to the ban. Only one appeal is allowed
	 */
	public function appeal($appeal)
	{
		\DB::update('banned_posters')
			->where('id', '=', $this->id)
			->value('appeal', $appeal)
			->value('appeal_status', static::APPEAL_PENDING)
			->execute();
	}

	/**
	 * Sets the flag to deny the appeal
	 */
	public function appeal_reject()
	{
		\DB::update('banned_posters')
			->where('id', '=', $this->id)
			->value('appeal_status', static::APPEAL_REJECTED)
			->execute();
	}
}