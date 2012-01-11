<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Statistics extends CI_Model
{

	var $stats = array();

	function __construct($id = NULL)
	{
		parent::__construct();
		$this->load_stats();
	}


	function get_table($board)
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			return $this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers($board->shortname);
			;
		}
		return $this->table = $this->db->protect_identifiers('board_' . $board->shortname, TRUE);
	}


	function load_stats()
	{
		$this->stats = array(
			'availability' => array(
				'name' => _('Availability'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 6, // every 6 hours
				'interface' => 'availability'
			),
			'daily_activity' => array(
				'name' => _('Daily activity'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 60 * 6, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'5-Minute Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages' lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'daily_activity_archive' => array(
				'name' => _('Daily activity archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 60, // every hour
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Sages' lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'daily_activity_hourly' => array(
				'name' => _('Daily activity hourly'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 60, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#ff0000' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages' lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'image_reposts' => array(
				'name' => _('Image reposts'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 7, // every 7 days
				'interface' => 'image_reposts'
			),
			'karma' => array(
				'name' => _('Karma'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 60 * 24 * 7, // every 7 days
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Day Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages' lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'new_tripfriends' => array(
				'name' => _('New tripfags'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 4, // every 4 days
				'interface' => 'new_tripfriends'
			),
			'population' => array(
				'name' => _('Population'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24, // every day
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'Posters'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:4 t 'Anonymous' lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:2 t 'Tripfriends' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Namefags' lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'post_count' => array(
				'name' => _('Post count'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 4, // every 4 days
				'interface' => 'post_count'
			),
			'post_rate' => array(
				'name' => _('Post rate'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 3, // every 3 minutes
				'interface' => 'list'
			),
			'post_rate_archive' => array(
				'name' => _('Post rate archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60 * 3, // every 3 minutes
				'interface' => 'list'
			),
			'users_online' => array(
				'name' => _('Users online'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60, // every minute
				'interface' => 'list'
			),
			'users_online_internal' => array(
				'name' => _('Users posting in archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => FALSE,
				'frequence' => 60, // every minute
				'interface' => 'list'
			)
		);
	}


	function get_stats()
	{
		return $this->stats;
	}


	function get_available_stats()
	{
		$stats = $this->get_stats();
		foreach ($stats as $k => $s)
		{
			if ($s['enabled'] !== TRUE)
			{
				unset($stats[$k]);
			}
		}
		return $stats;
	}


	function check_available_stats($stat, $selected_board)
	{
		$available = $this->get_available_stats();
		if (isset($available[$stat]) && $available[$stat]['enabled'] === TRUE)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->db->protect_identifiers('statistics', TRUE) . '
				WHERE board_id = ? AND name = ?
				LIMIT 0,1
			', array($selected_board->id, $stat));
			if ($query->num_rows() != 1)
			{
				return FALSE;
			}

			$result = $query->result();
			return array('info' => $available[$stat], 'data' => $result[0]->data);
		}
		return FALSE;
	}


	function cron()
	{
		$boards = new Board();
		$boards->get();

		$available = $this->get_available_stats();

		$stats = $this->db->query('
			SELECT board_id, name, timestamp
			FROM ' . $this->db->protect_identifiers('statistics', TRUE) . '
			ORDER BY timestamp DESC
		');

		$avail = array();
		foreach ($available as $k => $a)
		{
			$avail[] = $k;
		}

		foreach ($boards->all as $board)
		{
			echo $board->shortname . PHP_EOL;
			foreach ($available as $k => $a)
			{
				echo $k . PHP_EOL;
				$found = FALSE;
				foreach ($stats->result() as $r)
				{
					if ($r->board_id == $board->id && $r->name == $k)
					{
						$found = TRUE;
						//$r->timestamp >= time() - strtotime($a['frequence']) ||
						if (!$this->lock_stat($r->board_id, $k, $r->timestamp))
						{
							// another process took it up while we were O(n^3)ing!
							continue;
						}
						break;
					}
				}

				if ($found === FALSE)
				{
					// extremely rare case, let's hope we don't get in a racing condition with this!
					$this->save_stat($board->id, $k, date('Y-m-d H:i:s', time() + 600), '');
				}
				// we got the lock!
				$process = 'process_' . $k;
				$this->db->reconnect();
				$result = $this->$process($board);
				$this->save_stat($board->id, $k, date('Y-m-d H:i:s'), $result);
			}
		}
	}


	/**
	 * To avoid really dangerous racing conditions, turn up the timer before starting the update
	 *
	 * @param type $name
	 */
	function lock_stat($board_id, $name, $temp_timestamp)
	{
		// again, to avoid racing conditions, let's also check that the timestamp hasn't been changed
		$this->db->query('
			UPDATE ' . $this->db->protect_identifiers('statistics', TRUE) . '
			SET timestamp = ?
			WHERE board_id = ? AND name = ? AND timestamp = ?
		', array(date('Y-m-d H:i:s', time() + 600), $board_id, $name, $temp_timestamp)); // hopefully 10 minutes is enough for everything

		if ($this->db->affected_rows() != 1)
			return FALSE;
		return TRUE;
	}


	function save_stat($board_id, $name, $timestamp, $data = '')
	{
		$this->db->query('
			INSERT
			INTO ' . $this->db->protect_identifiers('statistics', TRUE) . '
			(board_id, name, timestamp, data)
			VALUES
			(?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
			timestamp = VALUES(timestamp), data = VALUES(data);
		', array($board_id, $name, $timestamp, json_encode($data)));
	}


	function process_availability($board)
	{
		$query = $this->db->query('
				SELECT name,trip,count(num) AS posts,avg(timestamp%86400) AS avg1,std(timestamp%86400) AS std1,
					(avg((timestamp+43200)%86400)+43200)%86400 avg2,std((timestamp+43200)%86400) AS std2
				FROM ' . $this->get_table($board) . '
				WHERE timestamp > ?
				GROUP BY name,trip
				HAVING count(*)>4
				ORDER BY name,trip
		', array(time() - 2592000));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_daily_activity($board)
	{
		$query = $this->db->query('
			SELECT (floor(timestamp/300)%288)*300, count(*),
				count(case media_hash when \'\' then NULL else 1 end),
				count(case email when \'sage\' then 1 else NULL end)
			FROM ' . $this->get_table($board) . '
			USE index(timestamp_index)
			WHERE timestamp > ?
			GROUP BY floor(timestamp/300)%288
			ORDER BY floor(timestamp/300)%288;
		', array(date('Y-m-d H:i:s', time() - 86400)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_daily_activity_archive($board)
	{
		$query = $this->db->query('
			SELECT ((floor(timestamp/3600)%24)*3600)+1800,
				count(*), count(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			USE index(timestamp_index)
			WHERE timestamp> ? AND subnum != 0
			GROUP BY floor(timestamp/3600)%24
			ORDER BY floor(timestamp/3600)%24;
		', array(date('Y-m-d H:i:s', time() - 86400)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_daily_activity_hourly($board)
	{
		$query = $this->db->query('
			SELECT ((floor(timestamp/3600)%24)*3600)+1800, count(*),
				count(CASE media_hash WHEN \'\' THEN NULL ELSE 1 END),
				count(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			USE index(timestamp_index)
			WHERE timestamp > ?
			GROUP BY floor(timestamp/3600)%24
			ORDER BY floor(timestamp/3600)%24;
		', array(date('Y-m-d H:i:s', time() - 86400)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_image_reposts($board)
	{
		$query = $this->db->query('
			SELECT preview, num, subnum, parent, media_hash, total
			FROM ' . $this->get_table($board) . '
			JOIN
			(
				SELECT hash, total, max(preview_w) AS w
				FROM ' . $this->get_table($board) . '
				JOIN
				(
					SELECT media_hash AS hash, count(media_hash) AS total
					FROM ' . $this->get_table($board) . '
					WHERE media_hash != \'\'
					GROUP BY media_hash
					ORDER BY count(media_hash) desc
					LIMIT 32
				) as x
				ON media_hash = hash
				GROUP BY media_hash
			) as x
			ON media_hash = hash AND preview_w = w
			GROUP BY media_hash
			ORDER BY total DESC;
		');

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_karma($board)
	{
		$query = $this->db->query('
			SELECT floor(timestamp/86400)*86400 AS days, count(*),
				count(case media_hash when \'\' then NULL else 1 end),
				count(case email when \'sage\' then 1 else NULL end)
			FROM ' . $this->get_table($board) . '
			FORCE index(timestamp_index)
			WHERE timestamp > ?
			GROUP BY days
			ORDER BY days;
		', array(date('Y-m-d H:i:s', time() - 31536000)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_new_tripfriends($board)
	{
		$query = $this->db->query('
			SELECT * from
			(
				SELECT name, trip, min(timestamp) AS firstseen,
					count(num) AS postcount
				FROM ' . $this->get_table($board) . ' group by trip
			) as l
			WHERE l.postcount > 30
			ORDER BY firstseen DESC;
		');

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_population($board)
	{
		$query = $this->db->query('
			SELECT floor(timestamp/86400)*86400 as days,
				count(CASE WHEN trip != \'\' THEN 1 ELSE NULL END),
				count(CASE WHEN name!=\'Anonymous\' AND trip = \'\' THEN 1 ELSE NULL END),
				count(case WHEN name=\'Anonymous\' AND trip = \'\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			FORCE index(timestamp_index)
			WHERE timestamp > ?
			GROUP BY days
			ORDER BY days
		', array(date('Y-m-d H:i:s', time() - 31536000)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_post_count($board)
	{
		$query = $this->db->query('
			SELECT name, trip, count(*)
			FROM ' . $this->get_table($board) . '
			GROUP BY name, trip
			ORDER BY count(*) DESC
			LIMIT 512
		');

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_post_rate($board)
	{
		$query = $this->db->query('
			SELECT count(*), count(*)/60
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ?
		', array(date('Y-m-d H:i:s', time() - 3600)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_post_rate_archive($board)
	{
		$query = $this->db->query('
			SELECT count(*), count(*)/60
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ? AND subnum != 0
		', array(date('Y-m-d H:i:s', time() - 3600)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_users_online($board)
	{
		$query = $this->db->query('
			SELECT name, trip, max(timestamp), num, subnum
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ?
			GROUP BY name, trip
			ORDER BY max(timestamp) DESC
		', array(date('Y-m-d H:i:s', time() - 1800)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_users_online_internal($board)
	{
		$query = $this->db->query('
			SELECT group_concat(DISTINCT concat(name) separator \', \'),
				max(timestamp), num, subnum
			FROM ' . $this->get_table($board) . '
			WHERE id != 0 AND timestamp > ?
			GROUP BY id
			ORDER BY max(timestamp) DESC
		', array(date('Y-m-d H:i:s', time() - 3600)));

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function graph_gnuplot($board, $stat, $data)
	{
		$INFILE = FCPATH . 'content/cache/reports-' . $board . '-' . $stat . '.dat';
		$OUTFILE = FCPATH . 'content/reports/' . $board . '/' . $stat . '.png';
		$GNUFILE = FCPATH . 'content/cache/reports-' . $board . '-' . $stat . '.gnu';

		$graph_data = array();
		foreach ($data as $line) {
			$graph_data[] = implode("\t", $line);
		}
		$graph_data = implode("\n", $graph_data);
		write_file($INFILE, $graph_data);

		$X_START = $data[0]['days'];
		$X_END = $data[count($data) - 1]['days'];

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

		$template = str_replace($template_vars, $template_vals, $this->generate_gnuplot_template($stat));
		write_file($GNUFILE, $template);
		$result = exec('/usr/bin/gnuplot ' . $GNUFILE);
		return $result;
	}

	function generate_gnuplot_template($stat)
	{
		$options = $this->stats[$stat]['gnuplot'];

		$template = array();

		$template[] = "set terminal png transparent size 800,600";
		$template[] = "set output '{{OUTFILE}}'";
		$template[] = "show terminal";
		foreach ($options as $key => $value)
		{
			if ($value === TRUE)
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