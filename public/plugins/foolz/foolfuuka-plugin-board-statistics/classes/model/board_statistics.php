<?php

namespace Foolz\Foolfuuka\Plugins\BoardStatistics\Model;

use \Foolz\Foolframe\Model\DoctrineConnection as DC;

class BoardStatistics
{
	public static function getStats()
	{
		return [
			'availability' => [
				'location' => 'availability',
				'name' => __('Availability'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 5,
				'interface' => 'availability'
			],
			'daily_activity' => [
				'location' => 'daily_activity',
				'name' => __('Daily Activity'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 6, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => [
					'title' => "'5-Minute Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%H:%M"',
					'grid' => true,
					'key' => 'left',
					'plot' => [
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with filledcurve x1"
					]
				]
			],
			'daily_activity_archive' => [
				'location' => 'daily_activity_archive',
				'name' => __('Daily Activity "Archive"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60, // every hour
				'interface' => 'graph',
				'gnuplot' => [
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => true,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => [
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with boxes",
						"'{{INFILE}}' using 1:3 t 'Sages' lt rgb '#ff0000' with boxes"
					]
				]
			],
			'daily_activity_hourly' => [
				'location' => 'daily_activity_hourly',
				'name' => __('Daily Activity "Hourly"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => [
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => true,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => [
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with boxes",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with boxes",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with boxes"
					]
				]
			],
			'image_reposts' => [
				'location' => 'image_reposts',
				'name' => __('Image Reposts'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'image_reposts'
			],
			'karma' => [
				'location' => 'karma',
				'name' => __('Karma'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 24 * 7, // every 7 days
				'interface' => 'graph',
				'gnuplot' => [
					'title' => "'1-Day Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => true,
					'key' => 'left',
					'plot' => [
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with filledcurve x1"
					]
				]
			],
			'new_users' => [
				'location' => 'new_users',
				'name' => __('New Users'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'new_users'
			],
			'population' => [
				'location' => 'population',
				'name' => __('Population'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 24, // every day
				'interface' => 'graph',
				'gnuplot' => [
					'title' => "'Posters'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => true,
					'key' => 'left',
					'plot' => [
						"'{{INFILE}}' using 1:4 t 'Anonymous'   lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:2 t 'Tripfriends' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Namefags'    lt rgb '#ff0000' with filledcurve x1"
					]
				]
			],
			'post_count' => [
				'location' => 'post_count',
				'name' => __('Post Count'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_count'
			],
			'post_rate' => [
				'location' => 'post_rate',
				'name' => __('Post Rate'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_rate'
			],
			'post_rate_archive' => [
				'location' => 'post_rate_archive',
				'name' => __('Post Rate "Archive"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_rate'
			],
			'users_online' => [
				'location' => 'users_online',
				'name' => __('Users Online'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'users_online'
			],
			'users_online_internal' => [
				'location' => 'users_online_internal',
				'name' => __('Users Posting in Archive'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'users_online'
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
				$process_function = 'process_' . $stat;
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
					->setParameters([':board_id' => $selected_board->id, ':name' => $stat])
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
			->setParameters([':board_id' => $board_id, ':name' => $name])
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
			->setParameters([':board_id' => $board_id, ':name' => $name])
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
				->setParameters([
					':board_id' => $board_id,
					':name' => $name,
					':timestamp' => $timestamp,
					':data' => json_encode($data)
				])
				->execute();
		}
	}

	public static function processAvailability($board)
	{
		return DC::qb()
			->select('
				name, trip, COUNT(num) AS posts,
				AVG(timestamp%86400) AS avg1,
				STDDEV_POP(timestamp%86400) AS std1,
				(AVG((timestamp+43200)%86400)+43200)%86400 avg2,
				STDDEV_POP((timestamp+43200)%86400) AS std2
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.(time() - 2592000))
			->groupBy('name')
			->addGroupBy('trip')
			->having('count(*) > 4')
			->orderBy('name')
			->addOrderBy('trip')
			->execute()
			->fetchAll();
	}

	public static function processDailyActivity($board)
	{
		return DC::qb()
			->select('
				(FLOOR(timestamp/300)%288)*300 AS time,
				COUNT(timestamp),
				COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE null END)
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.(time() - 86400))
			->groupBy('time')
			->orderBy('time')
			->execute()
			->fetchAll();
	}

	public static function processDailyActivityArchive($board)
	{
		return DC::qb()
			->select('
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time,
				COUNT(timestamp),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE null END)
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.(time() - 86400))
			->andWhere('subnum <> 0')
			->groupBy('time')
			->orderBy('time')
			->execute()
			->fetchAll();
	}

	public static function processDailyActivityHourly($board)
	{
		return DC::qb()
			->select('
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time,
				COUNT(timestamp),
				COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE null END)
			')
			->from($board->getTable(), 'b') // TODO FORCE INDEX(timestamp_index)
			->where('timestamp > '.(time() - 86400))
			->groupBy('time')
			->orderBy('time')
			->execute()
			->fetchAll();
	}

	public static function processImageReposts($board)
	{
		return DC::qb()
			->select('*')
			->from($board->getTable( '_images'), 'bi')
			->orderBy('total', 'desc')
			->setFirstResult(0)
			->setMaxResults(200)
			->execute()
			->fetchAll();
	}

	public static function processKarma($board)
	{
		return DC::qb()
			->select('day AS time, posts, images, sage')
			->from($board->getTable('_daily'), 'bd')
			->where('day > '.floor((time() - 31536000) / 86400) * 86400)
			->groupBy('day')
			->orderBy('day')
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
		return DC::qb()
			->select('day AS TIME, trips, names, anons')
			->from($board->getTable('_daily'), 'bd')
			->where('day > '.floor((time() - 31536000) / 86400) * 86400)
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
		return DC::qb()
			->select('COUNT(timestamp), COUNT(timestamp)/60')
			->from($board->getTable(), 'b')
			->where('timestamp > '.(time() - 3600))
			->execute()
			->fetchAll();
	}

	public static function processPostRateArchive($board)
	{
		return DC::qb()
			->select('COUNT(timestamp), COUNT(timestamp)/60')
			->from($board->getTable(), 'b')
			->where('timestamp > '.(time() - 3600))
			->andWhere('subnum <> 0')
			->execute()
			->fetchAll();
	}

	public static function processUsersOnline($board)
	{
		return DC::qb()
			->select('name, trip, MAX(timestamp), num, subnum')
			->from($board->getTable(), 'b')
			->where('timestamp > '.(time() - 1800))
			->groupBy('name')
			->addGroupBy('trip')
			->orderBy('MAX(timestamp)', 'desc')
			->execute()
			->fetchAll();
	}

	public static function processUsersOnlineInternal($board)
	{
		return DC::qb()
			->select('name, trip, MAX(timestamp), num, subnum')
			->from($board->getTable(), 'b')
			->where('poster_ip <> 0')
			->andWhere('timestamp > '.(time() - 3600))
			->groupBy('name')
			->addGroupBy('trip')
			->orderBy('MAX(timestamp)', 'desc')
			->execute()
			->fetchAll();
	}

	/**
	 * Generate the INFILE, OUTFILE, TEMPLATE files used by GNUPLOT to create graphs.
	 *
	 * @param $board name of the board
	 * @param $stat  name of the statistics generated
	 * @param $data  input dataset array
	 *
	 * @return void
	 */
	public static function graphGnuplot($board, $stat, $data)
	{
		// Create all missing directory paths for statistics.
		if ( ! file_exists(DOCROOT . 'foolfuuka/cache/'))
		{
			mkdir(DOCROOT . 'foolfuuka/cache/');
		}
		if ( ! file_exists(DOCROOT . 'foolfuuka/statistics/'))
		{
			mkdir(DOCROOT . 'foolfuuka/statistics/');
		}
		if ( ! file_exists(DOCROOT . 'foolfuuka/statistics/' . $board . '/'))
		{
			mkdir(DOCROOT . 'foolfuuka/statistics/' . $board . '/');
		}

		// Set PATH for INFILE, GNUFILE, and OUTFILE for read/write.
		$INFILE = DOCROOT . 'foolfuuka/cache/statistics-' . $board . '-' . $stat . '.dat';
		$GNUFILE = DOCROOT . 'foolfuuka/cache/statistics-' . $board . '-' . $stat . '.gnu';
		$OUTFILE = DOCROOT . 'foolfuuka/statistics/' . $board . '/' . $stat . '.png';

		// Obtain starting and ending data points for x range.
		$X_START = (!empty($data) ? $data[0]['time'] : 0);
		$X_END = (!empty($data) ? $data[count($data) - 1]['time'] : 0);

		// Format and save the INFILE dataset for GNUPLOT.
		$graph_data = array();
		foreach ($data as $line)
		{
			$graph_data[] = implode("\t", $line);
		}
		$graph_data = implode("\n", $graph_data);
		file_put_contents($INFILE, $graph_data);

		// Set template variables for replacement.
		$template_vars = array(
			'{{INFILE}}',
			'{{OUTFILE}}',
			'{{X_START}}',
			'{{X_END}}'
		);
		$template_vals = array(
			$INFILE,
			$OUTFILE,
			$X_START,
			$X_END
		);

		$template = str_replace($template_vars, $template_vals,
			static::generateGnuplotTemplate($stat));
		file_put_contents($GNUFILE, $template);

		// Execute GNUPLOT with GNUFILE input.
		$result = @exec('/usr/bin/gnuplot ' . $GNUFILE);
		return $result;
	}

	public static function generateGnuplotTemplate($stat)
	{
		$stats = static::getAvailableStats();
		$options = $stats[$stat]['gnuplot'];

		$template = array();
		$template[] = "set terminal png transparent size 800,600";
		$template[] = "set output '{{OUTFILE}}'";
		$template[] = "show terminal";

		foreach ($options as $key => $value)
		{
			if ($value === true)
			{
				$template[] = "set {$key}";
			}
			else
			{
				if (is_array($value))
				{
					$value = implode(", ", $value);
					$template[] = "{$key} {$value}";
				}
				else
				{
					$template[] = "set {$key} {$value}";
				}
			}
		}

		return implode("\n", $template);
	}
}