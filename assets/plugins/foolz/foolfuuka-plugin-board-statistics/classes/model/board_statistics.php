<?php

namespace Foolz\Foolfuuka\Plugins\BoardStatistics\Model;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Preferences;

class BoardStatistics extends Model
{
    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Preferences
     */
    protected $preferences;

    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->preferences = $context->getService('preferences');
    }

    public function getStats()
    {
        return [
            'activity' => [
                'name' => _i('Activity'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'frequency' => 60 * 60 * 6, // every 6 hours
                'interface' => 'activity',
                'function' => 'Activity'
            ],
            'availability' => [
                'name' => _i('Availability'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'frequency' => 60 * 60 * 5,
                'interface' => 'availability',
                'function' => 'Availability'
            ],
            'image-reposts' => [
                'name' => _i('Image Reposts'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'interface' => 'image_reposts',
                'function' => 'ImageReposts'
            ],
            'new-users' => [
                'name' => _i('New Users'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'interface' => 'new_users',
                'function' => 'NewUsers'
            ],
            'population' => [
                'name' => _i('Population'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'frequency' => 60 * 60 * 24, // every day
                'interface' => 'population',
                'function' => 'Population',
            ],
            'post-count' => [
                'name' => _i('Post Count'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'interface' => 'post_count',
                'function' => 'PostCount',
            ],
            'post-rate' => [
                'name' => _i('Post Rate'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'interface' => 'post_rate',
                'function' => 'PostRate',
            ],
            'users-online' => [
                'name' => _i('Users Online'),
                'description' => _i('Posts in last month by name and availability by time of day.'),
                'interface' => 'users_online',
                'function' => 'UsersOnline'
            ]
        ];
    }

    public function getAvailableStats()
    {
        $stats = $this->getStats();
        // this variable is going to be a serialized array
        $enabled = $this->preferences->get('foolfuuka.plugins.board_statistics.enabled');

        if (!$enabled) {
            return array();
        }

        $enabled = unserialize($enabled);

        foreach ($stats as $k => $s) {
            if (!$enabled[$k]) {
                unset($stats[$k]);
            }
        }
        return $stats;
    }

    public function checkAvailableStats($stat, $selected_board)
    {
        $available = $this->getAvailableStats();

        if (isset($available[$stat])) {
            if (!isset($available[$stat]['frequency'])) {
                // real time stat
                $process_function = 'process'.$available[$stat]['function'];
                $result = $this->$process_function($selected_board);

                return array('info' => $available[$stat], 'data' => json_encode($result));
            } else {
                $result = $this->dc->qb()
                    ->select('*')
                    ->from($this->dc->p('plugin_fu_board_statistics'), 'bs')
                    ->where('board_id = :board_id')
                    ->andWhere('name = :name')
                    ->setParameter(':board_id', $selected_board->id)
                    ->setParameter(':name', $stat)
                    ->setFirstResult(0)
                    ->setMaxResults(1)
                    ->execute()
                    ->fetchAll();

                if (count($result) !== 1) {
                    return false;
                }

            }

            return array('info' => $available[$stat], 'data' => $result[0]['data'], 'timestamp' => $result[0]['timestamp']);
        }

        return false;
    }

    public function getStat($board_id, $name)
    {
        $stat = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('plugin_fu_board_statistics'), 'bs')
            ->where('board_id = :board_id')
            ->andWhere('name = :name')
            ->setParameter(':board_id', $board_id)
            ->setParameter(':name', $name)
            ->execute()
            ->fetchAll();

        if (!count($stat))
            return false;

        return $stat[0];
    }

    public function saveStat($board_id, $name, $timestamp, $data = '')
    {
        $count = $this->dc->qb()
            ->select('COUNT(*) as count')
            ->from($this->dc->p('plugin_fu_board_statistics'), 'bs')
            ->where('board_id = :board_id')
            ->andWhere('name = :name')
            ->setParameter(':board_id', $board_id)
            ->setParameter(':name', $name)
            ->execute()
            ->fetch()['count'];

        if (!$count) {
            $this->dc->getConnection()
                ->insert($this->dc->p('plugin_fu_board_statistics'), [
                    'board_id' => $board_id,
                    'name' => $name,
                    'timestamp' => $timestamp,
                    'data' =>json_encode($data)
                ]);
        } else {
            $this->dc->qb()
                ->update($this->dc->p('plugin_fu_board_statistics'))
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

    public function processAvailability($board)
    {
        $datetime = new \DateTime('@'.time(), new \DateTimeZone('UTC'));

        if ($board->archive) {
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));
        }

        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));

        return $this->dc->qb()
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

    public function processActivity($board)
    {
        $datetime = new \DateTime('@'.time(), new \DateTimeZone('UTC'));

        if ($board->archive) {
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));
        }

        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));
        $result = [];

        $result['board'] = $this->dc->qb()
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

        $result['ghost'] = $this->dc->qb()
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

        $result['karma'] = $this->dc->qb()
            ->select('day AS time, posts, images, sage')
            ->from($board->getTable('_daily'), 'bd')
            ->where('day > '.floor(($timestamp - 31536000) / 86400) * 86400)
            ->groupBy('day')
            ->orderBy('day')
            ->execute()
            ->fetchAll();

        $result['total'] = $this->dc->qb()
            ->select('day AS time, posts, images, sage')
            ->from($board->getTable('_daily'), 'bd')
            ->groupBy('time')
            ->orderBy('time')
            ->execute()
            ->fetchAll();

        return $result;
    }

    public function processImageReposts($board)
    {
        return $this->dc->qb()
            ->select('*')
            ->from($board->getTable( '_images'), 'bi')
            ->where('banned = 0')
            ->orderBy('total', 'desc')
            ->setFirstResult(0)
            ->setMaxResults(200)
            ->execute()
            ->fetchAll();
    }

    public function processNewUsers($board)
    {
        return $this->dc->qb()
            ->select('name, trip, firstseen, postcount')
            ->from($board->getTable('_users'), 'bu')
            ->where('postcount > 30')
            ->orderBy('firstseen', 'desc')
            ->execute()
            ->fetchAll();
    }

    public function processPopulation($board)
    {
        $datetime = new \DateTime('@'.time(), new \DateTimeZone('UTC'));

        if ($board->archive) {
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));
        }

        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));

        return $this->dc->qb()
            ->select('day AS time, trips, names, anons')
            ->from($board->getTable('_daily'), 'bd')
            ->where('day > '.floor(($timestamp - 31536000) / 86400) * 86400)
            ->groupBy('day')
            ->orderBy('day')
            ->execute()
            ->fetchAll();
    }

    public function processPostCount($board)
    {
        return $this->dc->qb()
            ->select('name, trip, postcount')
            ->from($board->getTable('_users'), 'bu')
            ->orderBy('postcount', 'desc')
            ->setMaxResults(512)
            ->execute()
            ->fetchAll();
    }

    public function processPostRate($board)
    {
        $datetime = new \DateTime('@'.time(), new \DateTimeZone('UTC'));

        if ($board->archive) {
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));
        }

        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));
        $result = [];

        $result['board'] = $this->dc->qb()
            ->select('COUNT(timestamp) AS last_hour, COUNT(timestamp)/60 AS per_minute')
            ->from($board->getTable(), 'b')
            ->where('timestamp > '.($timestamp - 3600))
            ->andWhere('subnum = 0')
            ->execute()
            ->fetchAll();

        $result['ghost'] = $this->dc->qb()
            ->select('COUNT(timestamp) AS last_hour, COUNT(timestamp)/60 AS per_minute')
            ->from($board->getTable(), 'b')
            ->where('timestamp > '.($timestamp - 3600))
            ->andWhere('subnum <> 0')
            ->execute()
            ->fetchAll();

        return $result;
    }

    public function processUsersOnline($board)
    {
        $datetime = new \DateTime('@'.time(), new \DateTimeZone('UTC'));

        if ($board->archive) {
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));
        }

        $timestamp = strtotime($datetime->format('Y-m-d H:i:s'));
        $result = [];

        $result['board'] = $this->dc->qb()
            ->select('name, trip, MAX(timestamp) AS timestamp, num, subnum')
            ->from($board->getTable(), 'b')
            ->where('timestamp > '.($timestamp - 1800))
            ->groupBy('name')
            ->addGroupBy('trip')
            ->orderBy('MAX(timestamp)', 'desc')
            ->execute()
            ->fetchAll();

        $result['ghost'] = $this->dc->qb()
            ->select('name, trip, MAX(timestamp) AS timestamp, num, subnum')
            ->from($board->getTable(), 'b')
            ->where('subnum <> 0')
            ->andWhere('timestamp > '.($timestamp - 3600))
            ->groupBy('name')
            ->addGroupBy('trip')
            ->orderBy('MAX(timestamp)', 'desc')
            ->execute()
            ->fetchAll();

        return $result;
    }
}
