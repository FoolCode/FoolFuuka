<?php

namespace FoolFuuka\Model;

class BanException extends \FuelException {}
class BanNotFoundException extends BanException {}
class BanReasonTooLongException extends BanException {}
class BanInvalidLengthException extends BanException {}

class Ban extends \Model\Model_Base
{
	public $id = 0;
	public $ip = 0;
	public $reason = '';
	public $start = 0;
	public $length = 0;
	public $board_id = 0;
	
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
			throw new BanNotFoundException(__('The ban could not be found'));
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
		else if ($bans[$board->id])
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
		if ( empty($board_ids))
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
		
		foreach ($board_ids as $board_id)
		{
			$new = new static();
			
			$new->ip = $ip_decimal;
			$new->reason = $reason;
			$new->start = $time;
			$new->length = $length;
			$new->board_id = $board_id;
			
			try
			{ 
				$old = static::get_by_ip($ip_decimal);
				\DB::update('banned_posters')
					->where('id', $old->id)
					->value('start', $new->start)
					->value('length', $new->length)
					->execute();
			}
			catch (BanNotFoundException $e)
			{
				\DB::insert('banned_posters')
					->set(array(
						'ip' => $new->ip,
						'reason' => $new->reason,
						'start' => $new->start,
						'length' => $new->length,
						'board_id' => $new->board_id,
					))->execute();
			}
			
			$objects[] = $new;
		}
		
		return $objects;
	}
}