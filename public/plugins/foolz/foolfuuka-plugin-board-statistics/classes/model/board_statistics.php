<?php

namespace Foolz\Foolfuuka\Plugins\BoardStatistics\Model;

use \Foolz\Foolframe\Model\DoctrineConnection as DC;

class BoardStatistics
{
	public static function getStats()
	{
		return [
			'activity' => [
				'name' => __('Activity'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 6, // every 6 hours
				'interface' => 'activity',
				'function' => 'Activity'
			],
			'availability' => [
				'name' => __('Availability'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 5,
				'interface' => 'availability',
				'function' => 'Availability'
			],
			'image-reposts' => [
				'name' => __('Image Reposts'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'image_reposts',
				'function' => 'ImageReposts'
			],
			'new-users' => [
				'name' => __('New Users'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'new_users',
				'function' => 'NewUsers'
			],
			'population' => [
				'name' => __('Population'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 24, // every day
				'interface' => 'population',
				'function' => 'Population',
			],
			'post-count' => [
				'name' => __('Post Count'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_count',
				'function' => 'PostCount',
			],
			'post-rate' => [
				'name' => __('Post Rate'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_rate',
				'function' => 'PostRate',
			],
			'users-online' => [
				'name' => __('Users Online'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'users_online',
				'function' => 'UsersOnline'
			]
		];
	}

	public static function getAvailableStats()
	{
		$stats = static::getStats();
		// this variable is going to be a serialized array
		$enabled = \Preferences::get('foolfuuka.plugins.board_statistics.enabled');

		if ( ! $enabled)
		{
			return array();
		}

		$enabled = unserialize($enabled);

		foreach ($stats as $k => $s)
		{
			if ( ! $enabled[$k])
			{
				unset($stats[$k]);
			}
		}
		return $stats;
	}

	public static function checkAvailableStats($stat, $selected_board)
	{
		$available = static::getAvailableStats();

		if (isset($available[$stat]))
		{
			if ( ! isset($available[$stat]['frequency']))
			{
				// real time stat
				$process_function = 'process'.$available[$stat]['function'];
				$result = static::$process_function($selected_board);

				return array('info' => $available[$stat], 'data' => json_encode($result));
			}
			else
			{
				$result = DC::qb()
					->select('*')
					->from(DC::p('plugin_fu_board_statistics'), 'bs')
					->where('board_id = :board_id')
					->andWhere('name = :name')
					->setParameter(':board_id', $selected_board->id)
					->setParameter(':name', $stat)
					->setFirstResult(0)
					->setMaxResults(1)
					->execute()
					->fetchAll();

				if (count($result) !== 1)
				{
					return false;
				}

			}

			return array('info' => $available[$stat], 'data' => $result[0]['data'], 'timestamp' => $result[0]['timestamp']);
		}

		return false;
	}

	public static function getStat($board_id, $name)
	{
		$stat = DC::qb()
			->select('*')
			->from(DC::p('plugin_fu_board_statistics'), 'bs')
			->where('board_id = :board_id')
			->andWhere('name = :name')
			->setParameter(':board_id', $board_id)
			->setParameter(':name', $name)
			->execute()
			->fetchAll();

		if ( ! count($stat))
			return false;

		return $stat[0];
	}

	public static function saveStat($board_id, $name, $timestamp, $data = '')
	{
		$count = DC::qb()
			->select('COUNT(*) as count')
			->from(DC::p('plugin_fu_board_statistics'), 'bs')
			->where('board_id = :board_id')
			->andWhere('name = :name')
			->setParameter(':board_id', $board_id)
			->setParameter(':name', $name)
			->execute()
			->fetch()['count'];

		if ( ! $count)
		{
			DC::forge()
				->insert(DC::p('plugin_fu_board_statistics'), [
					'board_id' => $board_id,
					'name' => $name,
					'timestamp' => $timestamp,
					'data' =>json_encode($data)
				]);
		}
		else
		{
			DC::qb()
				->update(DC::p('plugin_fu_board_statistics'))
				->where('board_id = :board_id')
				->andWhere('name = :name')
				->set('timestamp', ':timestamp')
				->set('data', ':data')
				->setParameter(':board_id', $board_id)
				->setParameter(':name', $name)
				->setParameter(':timestamp', $timestamp)
				->setParameter(':data', json_encode($data))
				->execute();
		}
	}

	public static function processAvailability($board)
	{
		$datetime = new \DateTime();
		$datetime->setTimestamp(time());

		if ($board->archive)
		{
			$datetime->setTimezone(new \DateTimeZone('America/New_York'));
		}

		$timestamp = $datetime->getTimestamp();

		return DC::qb()
			->select('
				name, trip, COUNT(num) AS posts,
				AVG(timestamp%86400) AS avg1,
				STDDEV_POP(timestamp%86400) AS std1,
				(AVG( (timestamp+43200) %86400) +43200)%86400 avg2,
				STDDEV_POP((timestamp+43200)%86400) AS std2
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.($timestamp - 2592000))
			->groupBy('name')
			->addGroupBy('trip')
			->having('count(*) > 4')
			->orderBy('name')
			->addOrderBy('trip')
			->execute()
			->fetchAll();
	}

	public static function processActivity($board)
	{
		$datetime = new \DateTime();
		$datetime->setTimestamp(time());

		if ($board->archive)
		{
			$datetime->setTimezone(new \DateTimeZone('America/New_York'));
		}

		$timestamp = $datetime->getTimestamp();
		$result = [];

		$result['board'] = DC::qb()
			->select('
				FLOOR(timestamp/300)*300 AS time,
				COUNT(timestamp) AS posts,
				COUNT(media_hash) AS images,
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE null END) AS sage
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.($timestamp - 86400))
			->groupBy('time')
			->orderBy('time')
			->execute()
			->fetchAll();

		$result['ghost'] = DC::qb()
			->select('
				FLOOR(timestamp/300)*300 AS time,
				COUNT(timestamp) AS posts,
				0 AS images,
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE null END) AS sage
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.($timestamp - 86400))
			->andWhere('subnum <> 0')
			->groupBy('time')
			->orderBy('time')
			->execute()
			->fetchAll();

		$result['karma'] = DC::qb()
			->select('day AS time, posts, images, sage')
			->from($board->getTable('_daily'), 'bd')
			->where('day > '.floor(($timestamp - 31536000) / 86400) * 86400)
			->groupBy('day')
			->orderBy('day')
			->execute()
			->fetchAll();

		return $result;
	}

	public static function processImageReposts($board)
	{
		return DC::qb()
			->select('*')
			->from($board->getTable( '_images'), 'bi')
			->where('banned = 0')
			->orderBy('total', 'desc')
			->setFirstResult(0)
			->setMaxResults(200)
			->execute()
			->fetchAll();
	}

	public static function processNewUsers($board)
	{
		return DC::qb()
			->select('name, trip, firstseen, postcount')
			->from($board->getTable('_users'), 'bu')
			->where('postcount > 30')
			->orderBy('firstseen', 'desc')
			->execute()
			->fetchAll();
	}

	public static function processPopulation($board)
	{
		$datetime = new \DateTime();
		$datetime->setTimestamp(time());

		if ($board->archive)
		{
			$datetime->setTimezone(new \DateTimeZone('America/New_York'));
		}

		$timestamp = $datetime->getTimestamp();

		return DC::qb()
			->select('day AS time, trips, names, anons')
			->from($board->getTable('_daily'), 'bd')
			->where('day > '.floor(($timestamp - 31536000) / 86400) * 86400)
			->groupBy('day')
			->orderBy('day')
			->execute()
			->fetchAll();
	}

	public static function processPostCount($board)
	{
		return DC::qb()
			->select('name, trip, postcount')
			->from($board->getTable('_users'), 'bu')
			->orderBy('postcount', 'desc')
			->setMaxResults(512)
			->execute()
			->fetchAll();
	}

	public static function processPostRate($board)
	{
		$datetime = new \DateTime();
		$datetime->setTimestamp(time());

		if ($board->archive)
		{
			$datetime->setTimezone(new \DateTimeZone('America/New_York'));
		}

		$timestamp = $datetime->getTimestamp();
		$result = [];

		$result['board'] = DC::qb()
			->select('COUNT(timestamp) AS last_hour, COUNT(timestamp)/60 AS per_minute')
			->from($board->getTable(), 'b')
			->where('timestamp > '.($timestamp - 3600))
			->andWhere('subnum = 0')
			->execute()
			->fetchAll();

		$result['ghost'] = DC::qb()
			->select('COUNT(timestamp) AS last_hour, COUNT(timestamp)/60 AS per_minute')
			->from($board->getTable(), 'b')
			->where('timestamp > '.($timestamp - 3600))
			->andWhere('subnum <> 0')
			->execute()
			->fetchAll();

		return $result;
	}

	public static function processUsersOnline($board)
	{
		$datetime = new \DateTime();
		$datetime->setTimestamp(time());

		if ($board->archive)
		{
			$datetime->setTimezone(new \DateTimeZone('America/New_York'));
		}

		$timestamp = $datetime->getTimestamp();
		$result = [];

		$result['board'] = DC::qb()
			->select('name, trip, MAX(timestamp) AS timestamp, num, subnum')
			->from($board->getTable(), 'b')
			->where('timestamp > '.($timestamp - 1800))
			->groupBy('name')
			->addGroupBy('trip')
			->addGroupBy('num')
			->addGroupBy('subnum')
			->orderBy('MAX(timestamp)', 'desc')
			->execute()
			->fetchAll();

		$result['ghost'] = DC::qb()
			->select('name, trip, MAX(timestamp) AS timestamp, num, subnum')
			->from($board->getTable(), 'b')
			->where('subnum <> 0')
			->andWhere('timestamp > '.($timestamp - 3600))
			->groupBy('name')
			->addGroupBy('trip')
			->addGroupBy('num')
			->addGroupBy('subnum')
			->orderBy('MAX(timestamp)', 'desc')
			->execute()
			->fetchAll();

		return $result;
	}
}