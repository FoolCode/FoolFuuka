<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Cache\Cache;
use Foolz\Foolframe\Model\Model;
use Foolz\Plugin\PlugSuit;
use Foolz\Profiler\Profiler;

class BoardException extends \Exception {}
class BoardThreadNotFoundException extends BoardException {}
class BoardPostNotFoundException extends BoardException {}
class BoardMalformedInputException extends BoardException {}
class BoardNotCompatibleMethodException extends BoardException {}
class BoardMissingOptionsException extends BoardException {}

/**
 * Deals with all the data in the board tables
 */
class Board extends Model
{
    use PlugSuit;

    /**
     * Array of Comment sorted for output
     *
     * @var  CommentBulk[]
     */
    protected $comments = null;

    /**
     * Array of Comment in a plain array
     *
     * @var  CommentBulk[]
     */
    protected $comments_unsorted = null;

    /**
     * The count of the query without LIMIT
     *
     * @var  int
     */
    protected $total_count = null;

    /**
     * The method selected to retrieve comments
     *
     * @var  string
     */
    protected $method_fetching = null;

    /**
     * The method selected to retrieve the comment's count without LIMIT
     *
     * @var  string
     */
    protected $method_counting = null;

    /**
     * The options to give to the retrieving method
     *
     * @var  array
     */
    protected $options = [];

    /**
     * The options to give to the Comment class
     *
     * @var  array
     */
    protected $comment_options = [];

    /**
     * The selected Radix
     *
     * @var  Radix
     */
    protected $radix = null;

    /**
     * The options for the API. If null it means we're not using API mode
     *
     * @var  array
     */
    protected $api = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var CommentFactory
     */
    protected $comment_factory;

    public function __construct(\Foolz\Foolframe\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->profiler = $context->getService('profiler');
        $this->comment_factory = $context->getService('foolfuuka.comment_factory');
    }

    /**
     * Creates a new instance of Comment
     *
     * @return  \Foolz\Foolfuuka\Model\Comment
     */
    public static function forge($context)
    {
        return new static($context);
    }

    /**
     * Returns the comments, and executes the query if not already executed
     *
     * @return  \Foolz\Foolfuuka\Model\Comment[]  The array of comment objects
     */
    protected function p_getComments()
    {
        if ($this->comments === null) {
            if (method_exists($this, 'p_'.$this->method_fetching)) {
                $this->profiler->log('Start Board::getComments() with method '.$this->method_fetching);
                if (!$this->api)
                    $this->profiler->logMem('Start Board::getComments() with method '.$this->method_fetching, $this);

                $this->{$this->method_fetching}();
                $this->loadBacklinks();

                $this->profiler->log('End Board::getComments() with method '.$this->method_fetching);
                if (!$this->api)
                    $this->profiler->logMem('End Board::getComments() with method '.$this->method_fetching, $this);
            } else {
                $this->comments = false;
            }
        }

        return $this->comments;
    }

    /**
     * Returns the comments in a plain array, and executes the query if not already executed
     *
     * @return  \Foolz\Foolfuuka\Model\Comment[]  The array of comment objects
     */
    protected function p_getCommentsUnsorted()
    {
        $this->getComments();
        return $this->comments_unsorted;
    }

    /**
     * Returns the count without LIMIT, and executes the query if not already executed
     *
     * @return  int  The count
     */
    protected function p_getCount()
    {
        if ($this->total_count === null) {
            if (method_exists($this, 'p_'.$this->method_counting)) {
                $this->profiler->log('Start Board::getCount() with method '.$this->method_counting);
                if (!$this->api)
                    $this->profiler->logMem('Start Board::getCount() with method '.$this->method_counting, $this);

                $this->{$this->method_counting}();

                $this->profiler->log('End Board::getCount() with method '.$this->method_counting);
                if (!$this->api)
                    $this->profiler->logMem('End Board::getCount() with method '.$this->method_counting, $this);
            } else {
                $this->total_count = false;
            }
        }

        return $this->total_count;
    }

    /**
     * Returns the number of pages of the result
     *
     * @return  int  The number of pages
     */
    protected function p_getPages()
    {
        return floor($this->getCount() / $this->options['per_page']) + 1;
    }

    /**
     * Returns the comment with the highest item
     *
     * @param  string  $key  The key (column) on which to calculate the "highest" Comment
     *
     * @return  \Foolz\Foolfuuka\Model\Board
     */
    protected function p_getHighest($key)
    {
        $temp = $this->comments_unsorted[0];

        foreach ($this->comments_unsorted as $bulk) {
            if ($temp->comment->$key < $bulk->comment->$key) {
                $temp = $bulk;
            }
        }

        return $temp;
    }

    /**
     * Sets the fetching method
     *
     * @param  string  $name  The method name of the fetching method, without p_
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_setMethodFetching($name)
    {
        $this->method_fetching = $name;

        return $this;
    }

    /**
     * Sets the counting method
     *
     * @param  string  $name  The method name of the counting method, without p_
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_setMethodCounting($name)
    {
        $this->method_counting = $name;

        return $this;
    }

    /**
     * Set the options to pass to the counting and fetching methods. These are usually exploded in the method.
     *
     * @param  string|array  $name   The name of the variable, if associative array it will be used instead
     * @param  mixed         $value  The value of the variable
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    public function setOptions($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $item) {
                $this->setOptions($key, $item);
            }

            return $this;
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Set the options to pass to the Comment object
     *
     * @param  string|array  $name   The name of the variable, if associative array it will be used instead
     * @param  mixed         $value  The value of the variable
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_setCommentOptions($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $item) {
                $this->setCommentOptions($key, $item);
            }

            return $this;
        }

        $this->comment_options[$name] = $value;

        return $this;
    }

    /**
     * Sets the Radix object
     *
     * @param  Radix $radix  The Radix object pertaining this Board
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_setRadix(Radix $radix = null)
    {
        $this->radix = $radix;

        return $this;
    }

    /**
     * Set the API options
     *
     * @param  array|null  $enable  If array it will be used to set API options, if null it will disable API mode
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_setApi($enable = [])
    {
        $this->api = $enable;

        return $this;
    }

    /**
     * Sets the page to fetch
     *
     * @param  int  $page  The page to fetch
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     * @throws  \Foolz\Foolfuuka\Model\BoardException  If the page number is not valid
     */
    protected function p_setPage($page)
    {
        $page = intval($page);

        if ($page < 1) {
            throw new BoardException(_i('The page number is not valid.'));
        }

        $this->setOptions('page', $page);

        return $this;
    }

    /**
     * Checks if the string is a valid post number
     *
     * @param  string  $str  The string to test on
     *
     * @return  boolean  True if the post number is valid, false otherwise
     */
    public static function isValidPostNumber($str)
    {
        return ctype_digit((string) $str) || preg_match('/^[0-9]+(,|_)[0-9]+$/', $str);
    }

    /**
     * Splits the post number in num and subnum, even if there's no subnum in the string.
     *
     * @param  string  $num  The string to split. Must be a "valid post number" (check with static::isValidPostNumber())
     *
     * @return  array  Two keys: num, subnum
     */
    public static function splitPostNumber($num)
    {
        if (strpos($num, ',') !== false) {
            $arr = explode(',', $num);
        } elseif (strpos($num, '_') !== false) {
            $arr = explode('_', $num);
        } else {
            $result['num'] = $num;
            $result['subnum'] = 0;
            return $result;
        }

        $result['num'] = $arr[0];
        $result['subnum'] = isset($arr[1]) ? $arr[1] : 0;

        return $result;
    }

    /**
     * Loops over the unsorted comments to fetch all the backlinks
     */
    protected function loadBacklinks()
    {
        $c = new Comment($this->getContext());

        foreach ($this->comments_unsorted as $bulk) {
            $c->setBulk($bulk);
            $c->processComment(true);
            $bulk->clean();
        }
    }

    /**
     * Sets the board to the "latest" mode, to create index pages with a couple of the last posts per thread
     * Options: page, per_page, order[by_post, by_thread, ghost]
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getLatest()
    {
        $this
            ->setMethodFetching('getLatestComments')
            ->setMethodCounting('getLatestCount')
            ->setOptions([
                'per_page' => 20,
                'per_thread' => 5,
                'order' => 'by_post'
            ]);

        return $this;
    }


    /**
     * Returns the latest threads with a couple of the latest posts in each thread
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getLatestComments()
    {
        $this->profiler->log('Board::getLatestComments Start');
        extract($this->options);

        try {
            // not archives, not ghosts, under 10 pages, 10 per page
            if (!$this->radix->archive && $order !== 'ghost' && $page <= 10 && $per_page == 10) {
                list($results, $query_posts) = Cache::item('foolfuuka.model.board.getLatestComments.query.'
                    .$this->radix->shortname.'.'.$order.'.'.$page)->get();			} else {
                // lots of cases we don't want to handle go dynamic
                throw new \OutOfBoundsException;
            }
        } catch(\OutOfBoundsException $e) {
            switch ($order) {
                case 'by_post':
                    $query = $this->dc->qb()
                        ->select('*, thread_num AS unq_thread_num')
                        ->from($this->radix->getTable('_threads'), 'rt')
                        ->orderBy('rt.time_bump', 'DESC')
                        ->setMaxResults($per_page)
                        ->setFirstResult(($page * $per_page) - $per_page);
                    break;

                case 'by_thread':
                    $query = $this->dc->qb()
                        ->select('*, thread_num AS unq_thread_num')
                        ->from($this->radix->getTable('_threads'), 'rt')
                        ->orderBy('rt.thread_num', 'DESC')
                        ->setMaxResults($per_page)
                        ->setFirstResult(($page * $per_page) - $per_page);
                    break;

                case 'ghost':
                    $query = $this->dc->qb()
                        ->select('*, thread_num AS unq_thread_num')
                        ->from($this->radix->getTable('_threads'), 'rt')
                        ->where('rt.time_ghost_bump IS NOT null')
                        ->orderBy('rt.time_ghost_bump', 'DESC')
                        ->setMaxResults($per_page)
                        ->setFirstResult(($page * $per_page) - $per_page);
                    break;
            }

            $threads = $query
                ->execute()
                ->fetchAll();

            if (!count($threads)) {
                $this->comments = [];
                $this->comments_unsorted = [];

                $this->profiler->logMem('Board $this', $this);
                $this->profiler->log('Board::getLatestComments End Prematurely');
                return $this;
            }

            // populate arrays with posts
            $results = [];
            $sql_arr = [];

            foreach ($threads as $thread) {
                $sql_arr[] = '('.$this->dc->qb()
                    ->select('*')
                    ->from($this->radix->getTable(), 'r')
                    ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                    ->where('r.thread_num = '.$thread['unq_thread_num'])
                    ->orderBy('op', 'DESC')
                    ->addOrderBy('num', 'DESC')
                    ->addOrderBy('subnum', 'DESC')
                    ->setMaxResults($per_thread + 1)
                    ->setFirstResult(0)
                    ->getSQL().')';

                $omitted = ($thread['nreplies'] - ($per_thread + 1));
                $results[$thread['thread_num']] = [
                    'omitted' => $omitted > 0 ? $omitted : 0,
                    'images_omitted' => ($thread['nimages'] - 1)
                ];
            }

            $query_posts = $this->dc->getConnection()
                ->executeQuery(implode(' UNION ', $sql_arr))
                ->fetchAll();

            // not archives, not ghosts, under 10 pages, 10 per page
            if (!$this->radix->archive && $order !== 'ghost' && $page <= 10 && $per_page == 10) {
                Cache::item('foolfuuka.model.board.getLatestComments.query.'
                    .$this->radix->shortname.'.'.$order.'.'.$page)->set([$results, $query_posts], 300);
            }
        }

        foreach ($query_posts as $key => $row) {
            $data = new CommentBulk();
            $data->import($row, $this->radix);
            unset($query_posts[$key]);
            $this->comments_unsorted[] = $data;
        }

        unset($query_posts);

        $this->profiler->logMem('Board $this->comments_unsorted', $this->comments_unsorted);

        // populate results array and order posts
        foreach ($this->comments_unsorted as $bulk) {
            if ($bulk->comment->op == 0) {
                if ($bulk->media !== null && $bulk->media->preview_orig) {
                    $results[$bulk->comment->thread_num]['images_omitted']--;
                }

                if (!isset($results[$bulk->comment->thread_num]['posts'])) {
                    $results[$bulk->comment->thread_num]['posts'] = [];
                }

                array_unshift($results[$bulk->comment->thread_num]['posts'], $bulk);
            } else {
                $results[$bulk->comment->thread_num]['op'] = $bulk;
            }
        }

        $this->comments = $results;

        $this->profiler->logMem('Board $this->comments', $this->comments);
        $this->profiler->logMem('Board $this', $this);
        $this->profiler->log('Board::getLatestComments End');
        return $this;
    }

    /**
     * Returns the count of the threads available
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getLatestCount()
    {
        $this->profiler->log('Board::getLatestCount Start');
        extract($this->options);

        $type_cache = 'thread_num';

        if ($order == 'ghost') {
            $type_cache = 'ghost_num';
        }

        try {
            $this->total_count = Cache::item('Foolz_Foolfuuka_Model_Board.getLatestCount.result.'.$type_cache)->get();
            return $this;
        } catch (\OutOfBoundsException $e) {
            switch ($order) {
                // these two are the same
                case 'by_post':
                case 'by_thread':
                    $query_threads = $this->dc->qb()
                        ->select('COUNT(thread_num) AS threads')
                        ->from($this->radix->getTable('_threads'), 'rt');
                    break;

                case 'ghost':
                    $query_threads = $this->dc->qb()
                        ->select('COUNT(thread_num) AS threads')
                        ->from($this->radix->getTable('_threads'), 'rt')
                        ->where('rt.time_ghost_bump IS NOT null');
                    break;
            }

            $result = $query_threads
                ->execute()
                ->fetch();

            $this->total_count = $result['threads'];
            Cache::item('Foolz_Foolfuuka_Model_Board.getLatestCount.result.'.$type_cache)->set($this->total_count, 300);
        }

        $this->profiler->logMem('Board $this', $this);
        $this->profiler->log('Board::getLatestCount End');
        return $this;
    }

    /**
     * Sets the "thread" mode
     * Options: page, per_page
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getThreads()
    {
        $this
            ->setMethodFetching('getThreadsComments')
            ->setMethodCounting('getThreadsCount')
            ->setOptions([
                'per_page' => 20,
                'order' => 'by_post'
            ]);

        return $this;
    }

    /**
     * Fetches a bunch of threads (in example for gallery)
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getThreadsComments()
    {
        $this->profiler->log('Board::getThreadsComments Start');
        extract($this->options);

        try {
            // not archives, not ghosts, under 10 pages, 10 per page
            if (!$this->radix->archive && $page <= 10 && $per_page == 100) {
                $result = Cache::item('foolfuuka.model.board.getThreadsComments.query.'
                    .$this->radix->shortname.'.'.$page)->get();
            } else {
                // lots of cases we don't want to handle go dynamic
                throw new \OutOfBoundsException;
            }
        } catch(\OutOfBoundsException $e) {
            $inner_query = $this->dc->qb()
                ->select('*, thread_num as unq_thread_num')
                ->from($this->radix->getTable('_threads'), 'rt')
                ->orderBy('rt.time_op', 'DESC')
                ->setMaxResults($per_page)
                ->setFirstResult(($page * $per_page) - $per_page)
                ->getSQL();

            $result = $this->dc->qb()
                ->select('*')
                ->from('('.$inner_query.')', 'g')
                ->join('g', $this->radix->getTable(), 'r', 'r.num = g.unq_thread_num AND r.subnum = 0')
                ->leftJoin('g', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                ->execute()
                ->fetchAll();

            // not archives, not ghosts, under 10 pages, 10 per page
            if (!$this->radix->archive && $page <= 10 && $per_page == 100) {
                Cache::item('foolfuuka.model.board.getThreadsComments.query.'
                    .$this->radix->shortname.'.'.$page)->set($result, 300);
            }
        }

        if (!count($result)) {
            $this->comments = [];
            $this->comments_unsorted = [];

            $this->profiler->logMem('Board $this', $this);
            $this->profiler->log('Board::get_threadscomments End Prematurely');
            return $this;
        }

        foreach ($result as $key => $row) {
            $data = new CommentBulk();
            $data->import($row, $this->radix);
            unset($result[$key]);
            $this->comments_unsorted[] = $data;
        }

        unset($result);

        $this->comments = $this->comments_unsorted;

        $this->profiler->logMem('Board $this->comments', $this->comments);
        $this->profiler->logMem('Board $this', $this);
        $this->profiler->log('Board::getThreadsComments End');

        return $this;
    }

    /**
     * Counts the available threads
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getThreadsCount()
    {
        extract($this->options);

        try {
            $this->total_count = Cache::item('Foolz_Foolfuuka_Model_Board.getThreadsCount.result')->get();
        } catch (\OutOfBoundsException $e) {
            $result = $this->dc->qb()
                ->select('COUNT(thread_num) AS threads')
                ->from($this->radix->getTable('_threads'), 'rt')
                ->execute()
                ->fetch();

            $this->total_count = $result['threads'];
            Cache::item('Foolz_Foolfuuka_Model_Board.getThreadsCount.result')->set($this->total_count, 300);
        }

        return $this;
    }

    /**
     * Sets the Board object to Thread mode
     * Options: type=[from_doc_id, ghosts, last_x], (int)num(thread number)
     * Options for "from_doc_id": (int)latest_doc_id
     * Options for "last_x": (int)last_limit
     *
     * @param  int  $num  The number of the thread
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     * @throws  BoardMalformedInputException
     */
    protected function p_getThread($num)
    {
        // default variables
        $this
            ->setMethodFetching('getThreadComments')
            ->setOptions(['type' => 'thread', 'realtime' => false]);

        if (!ctype_digit((string) $num) || $num < 1) {
            throw new BoardMalformedInputException(_i('The thread number is invalid.'));
        }

        $this->setOptions('num', $num);

        return $this;
    }

    /**
     * Gets a thread
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     * @throws  BoardThreadNotFoundException  If the thread wasn't found
     */
    protected function p_getThreadComments()
    {
        $this->profiler->log('Board::getThreadComments Start');

        extract($this->options);

        // determine type
        switch ($type) {
            case 'from_doc_id':
                $query_result = $this->dc->qb()
                    ->select('*')
                    ->from($this->radix->getTable(), 'r')
                    ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                    ->where('thread_num = :thread_num')
                    ->andWhere('doc_id > :latest_doc_id')
                    ->orderBy('num', 'ASC')
                    ->addOrderBy('subnum', 'ASC')
                    ->setParameter(':thread_num', $num)
                    ->setParameter(':latest_doc_id', $latest_doc_id)
                    ->execute()
                    ->fetchAll();

                break;

            case 'ghosts':
                $query_result = $this->dc->qb()
                    ->select('*')
                    ->from($this->radix->getTable(), 'r')
                    ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                    ->where('thread_num = :thread_num')
                    ->where('subnum <> 0')
                    ->orderBy('num', 'ASC')
                    ->addOrderBy('subnum', 'ASC')
                    ->setParameter(':thread_num', $num)
                    ->execute()
                    ->fetchAll();

                break;

            case 'last_x':

                try {
                    // we save some cache memory by only saving last_50, so it must always fail otherwise
                    if ($last_limit != 50) {
                        throw new \OutOfBoundsException;
                    }

                    $query_result = Cache::item('foolfuuka.model.board.getThreadComments.last_50.'
                        .md5(serialize([$this->radix->shortname, $num])))->get();
                } catch (\OutOfBoundsException $e) {
                    $subquery_first = $this->dc->qb()
                        ->select('*')
                        ->from($this->radix->getTable(), 'xr')
                        ->where('num = '.$this->dc->getConnection()->quote($num))
                        ->setMaxResults(1)
                        ->getSQL();
                    $subquery_last = $this->dc->qb()
                        ->select('*')
                        ->from($this->radix->getTable(), 'xrr')
                        ->where('thread_num = '.$this->dc->getConnection()->quote($num))
                        ->orderBy('num', 'DESC')
                        ->addOrderBy('subnum', 'DESC')
                        ->setMaxResults($last_limit)
                        ->getSQL();
                    $query_result = $this->dc->qb()
                        ->select('*')
                        ->from('(('.$subquery_first.') UNION ('.$subquery_last.'))', 'r')
                        ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                        ->orderBy('num', 'ASC')
                        ->addOrderBy('subnum', 'ASC')
                        ->execute()
                        ->fetchAll();

                    // cache only if it's last_50
                    if ($last_limit == 50) {
                        $cache_time = 300;
                        if ($this->radix->archive) {
                            $cache_time = 30;

                            // over 7 days is old
                            $old = time() - 604800;

                            // set a very long cache time for archive threads older than a week, in case a ghost post will bump it
                            foreach ($query_result as $k => $r) {
                                if ($r['timestamp'] < $old) {
                                    $cache_time = 300;
                                    break;
                                }
                            }
                        }

                        Cache::item('foolfuuka.model.board.getThreadComments.last_50.'
                            .md5(serialize([$this->radix->shortname, $num])))->set($query_result, $cache_time);
                    }
                }

                break;

            case 'thread':

                try {
                    $query_result = Cache::item('foolfuuka.model.board.getThreadComments.thread.'
                        .md5(serialize([$this->radix->shortname, $num])))->get();
                } catch (\OutOfBoundsException $e) {
                    $query_result = $this->dc->qb()
                        ->select('*')
                        ->from($this->radix->getTable(), 'r')
                        ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                        ->where('thread_num = :thread_num')
                        ->orderBy('num', 'ASC')
                        ->addOrderBy('subnum', 'ASC')
                        ->setParameter(':thread_num', $num)
                        ->execute()
                        ->fetchAll();

                    $cache_time = 300;
                    if ($this->radix->archive) {
                        $cache_time = 30;

                        // over 7 days is old
                        $old = time() - 604800;

                        // set a very long cache time for archive threads older than a week, in case a ghost post will bump it
                        foreach ($query_result as $k => $r) {
                            if ($r['timestamp'] < $old) {
                                $cache_time = 300;
                                break;
                            }
                        }
                    }

                    Cache::item('foolfuuka.model.board.getThreadComments.thread.'
                        .md5(serialize([$this->radix->shortname, $num])))->set($query_result, $cache_time);
                }

                break;
        }

        if (!count($query_result) && isset($latest_doc_id)) {
            return $this->comments = $this->comments_unsorted = [];
        }

        if (!count($query_result)) {
            throw new BoardThreadNotFoundException(_i('There\'s no such a thread.'));
        }

        foreach ($query_result as $key => $row) {
            $data = new CommentBulk();
            $data->import($row, $this->radix);
            unset($query_result[$key]);
            $this->comments_unsorted[] = $data;
        }

        unset($query_result);

        foreach ($this->comments_unsorted as $key => $bulk) {
            if ($bulk->comment->op == 0) {
                $this->comments[$bulk->comment->thread_num]['posts']
                    [$bulk->comment->num.(($bulk->comment->subnum == 0) ? '' : '_'.$bulk->comment->subnum)] = &$this->comments_unsorted[$key];
            } else {
                $this->comments[$bulk->comment->num]['op'] = &$this->comments_unsorted[$key];
            }
        }

        $this->profiler->logMem('Board $this->comments', $this->comments);
        $this->profiler->logMem('Board $this', $this);
        $this->profiler->log('Board::getThreadComments End');

        return $this;
    }

    /**
     * Returns an array specifying the thread statuses.
     * Returned array keys: closed, dead, disable_image_upload
     *
     * @return  array  An associative array with boolean values
     * @throws  BoardNotCompatibleMethodException  If the specified fetching method is not "getThreadComments"
     * @throws  BoardThreadNotFoundException       If the thread was not found
     */
    protected function p_getThreadStatus()
    {
        if ($this->method_fetching !== 'getThreadComments') {
            throw new BoardNotCompatibleMethodException;
        }

        extract($this->options);

        $thread = $this->dc->qb()
            ->select('*')
            ->from($this->radix->getTable('_threads'), 't')
            ->where('thread_num = :thread_num')
            ->setParameter(':thread_num', $num)
            ->execute()
            ->fetch();

        if (!$thread) {
            throw new BoardThreadNotFoundException(_i('The thread you were looking for does not exist.'));
        }

        // define variables to override
        $ghost_post_present = false;
        $last_modified = $thread['time_last_modified'];

        if ($thread['time_ghost'] !== null) {
            $ghost_post_present = true;
        }

        if ($this->radix->archive) {
            $timestamp = new \DateTime(date('Y-m-d H:i:s', $last_modified), new \DateTimeZone('America/New_York'));
            $timestamp->setTimezone(new \DateTimeZone('UTC'));
            $last_modified = strtotime($timestamp->format('Y-m-d H:i:s'));
        }

        $result = [
            'closed' => (bool) $thread['locked'],
            'dead' => (bool) $this->radix->archive,
            'disable_image_upload' => (bool) $this->radix->archive,
            'last_modified' => $last_modified
        ];

        // time check
        if (time() - $thread['time_last'] > 432000 || $ghost_post_present) {
            $result['dead'] = true;
            $result['disable_image_upload'] = true;
        }

        if ($thread['nreplies'] > $this->radix->getValue('max_posts_count')) {
            $result['dead'] = true;
            $result['disable_image_upload'] = true;
        } elseif ($thread['nimages'] >= $this->radix->getValue('max_images_count')) {
            $result['disable_image_upload'] = true;
        }

        if ($this->radix->getValue('disable_ghost') && $result['dead']) {
            $result['closed'] = true;
        }

        return $result;
    }

    /**
     * Sets the Board object to fetch a post
     * Options available: num OR doc_id
     *
     * @param  string  $num  If specified, a valid post number
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     */
    protected function p_getPost($num = null)
    {
        // default variables
        $this->setMethodFetching('getPostComments');

        if ($num !== null) {
            $this->setOptions('num', $num);
        }

        return $this;
    }

    /**
     * Gets a post by num or doc_d
     *
     * @return  \Foolz\Foolfuuka\Model\Board  The current object
     * @throws  BoardMalformedInputException  If the $num is not a valid post number
     * @throws  BoardMissingOptionsException  If doc_id or num has not been specified
     * @throws  BoardPostNotFoundException    If the post has not been found
     */
    protected function p_getPostComments()
    {
        extract($this->options);

        $query = $this->dc->qb()
            ->select('*')
            ->from($this->radix->getTable(), 'r');

        if (isset($num)) {
            if (!static::isValidPostNumber($num)) {
                throw new BoardMalformedInputException;
            }
            $num_arr = static::splitPostNumber($num);
            $query->where('num = :num')
                ->andWhere('subnum = :subnum')
                ->setParameter(':num', $num_arr['num'])
                ->setParameter(':subnum', $num_arr['subnum']);
        } elseif (isset($doc_id)) {
            $query->where('doc_id = :doc_id')
                ->setParameter(':doc_id', $doc_id);
        } else {
            throw new BoardMissingOptionsException(_i('No posts found with the submitted options.'));
        }

        $result = $query
            ->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
            ->execute()
            ->fetchAll();

        if (!count($result)) {
            throw new BoardPostNotFoundException(_i('Post not found.'));
        }

        foreach ($result as $bulk) {
            $data = new CommentBulk();
            $data->import($bulk, $this->radix);
            $this->comments_unsorted[] = $data;
        }

        foreach ($this->comments_unsorted as $comment) {
            $this->comments[$comment->comment->num.($comment->comment->subnum ? '_'.$comment->comment->subnum : '')] = $comment;
        }

        return $this;
    }
}
