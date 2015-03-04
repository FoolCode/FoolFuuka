<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\Logger;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\Inet\Inet;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Connection as SphinxConnnection;

class SearchException extends \Exception {}
class SearchRequiresSphinxException extends SearchException {}
class SearchSphinxOfflineException extends SearchException {}
class SearchInvalidException extends SearchException {}
class SearchEmptyResultException extends SearchException {}

class Search extends Board
{
    /**
     * The total number of results found
     *
     * @var  int
     */
    protected $total_found;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->logger = $context->getService('logger');
        $this->preferences = $context->getService('preferences');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->media_factory = $context->getService('foolfuuka.media_factory');
    }

    /**
     * Returns the structure for the search form
     *
     * @return  array
     */
    public static function structure()
    {
        return [
            [
                'type' => 'input',
                'label' => _i('Comment'),
                'name' => 'text'
            ],
            [
                'type' => 'input',
                'label' => _i('Subject'),
                'name' => 'subject'
            ],
            [
                'type' => 'input',
                'label' => _i('Username'),
                'name' => 'username'
            ],
            [
                'type' => 'input',
                'label' => _i('Tripcode'),
                'name' => 'tripcode'
            ],
            [
                'type' => 'input',
                'label' => _i('Email'),
                'name' => 'email'
            ],
            [
                'type' => 'input',
                'label' => _i('Unique ID'),
                'name' => 'uid'
            ],
            [
                'type' => 'input',
                'label' => _i('Country'),
                'name' => 'country'
            ],
            [
                'type' => 'input',
                'label' => _i('Poster IP'),
                'name' => 'poster_ip',
                'access' => 'comment.see_ip'
            ],
            [
                'type' => 'input',
                'label' => _i('Filename'),
                'name' => 'filename'
            ],
            [
                'type' => 'input',
                'label' => _i('Image Hash'),
                'placeholder' => _i('Drop your image here'),
                'name' => 'image'
            ],
            [
                'type' => 'date',
                'label' => _i('Date Start'),
                'name' => 'start',
                'placeholder' => 'YYYY-MM-DD'
            ],
            [
                'type' => 'date',
                'label' => _i('Date End'),
                'name' => 'end',
                'placeholder' => 'YYYY-MM-DD'
            ],
            [
                'type' => 'radio',
                'label' => _i('Capcode'),
                'name' => 'capcode',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'user', 'text' => _i('Only User Posts')],
                    ['value' => 'mod', 'text' => _i('Only Moderator Posts')],
                    ['value' => 'admin', 'text' => _i('Only Admin Posts')],
                    ['value' => 'dev', 'text' => _i('Only Developer Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Show Posts'),
                'name' => 'filter',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'text', 'text' => _i('Only With Images')],
                    ['value' => 'image', 'text' => _i('Only Without Images')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Deleted Posts'),
                'name' => 'deleted',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'deleted', 'text' => _i('Only Deleted Posts')],
                    ['value' => 'not-deleted', 'text' => _i('Only Non-Deleted Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Ghost Posts'),
                'name' => 'ghost',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'only', 'text' => _i('Only Ghost Posts')],
                    ['value' => 'none', 'text' => _i('Only Non-Ghost Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Post Type'),
                'name' => 'type',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'sticky', 'text' => _i('Only Sticky Threads')],
                    ['value' => 'op', 'text' => _i('Only Opening Posts')],
                    ['value' => 'posts', 'text' => _i('Only Reply Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Results'),
                'name' => 'results',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'thread', 'text' => _i('Grouped By Threads')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Order'),
                'name' => 'order',
                'elements' => [
                    ['value' => false, 'text' => _i('Latest Posts First')],
                    ['value' => 'asc', 'text' => _i('Oldest Posts First')]
                ]
            ]
        ];
    }

    /**
     * Sets the Board to Search mode
     * Options: (array)arguments, (int)limit, (int)page
     *
     * @param  array  $arguments  The search arguments
     *
     * @return  \Foolz\FoolFuuka\Model\Search  The current object
     */
    protected function p_getSearch($arguments)
    {
        // prepare
        $this->setMethodFetching('getResults')
            ->setMethodCounting('getTotalResults')
            ->setOptions([
                'args' => $arguments,
                'limit' => 25,
            ]);

        return $this;
    }

    protected function getUserInput()
    {
        $args = [];
        extract($this->options);

        $search_inputs = [
            'boards', 'subject', 'text', 'username', 'tripcode', 'email', 'capcode', 'uid', 'poster_ip', 'country',
            'filename', 'image', 'deleted', 'ghost', 'filter', 'type', 'start', 'end', 'results', 'order'
        ];

        foreach ($search_inputs as $field) {
            if (!isset($args[$field])) {
                $args[$field] = null;
            }
        }

        return $args;
    }

    /**
     * Gets the search results
     *
     * @return  \Foolz\FoolFuuka\Model\Search  The current object
     * @throws  SearchEmptyResultException     If there's no results to display
     * @throws  SearchRequiresSphinxException  If the search submitted requires Sphinx to run
     * @throws  SearchSphinxOfflineException   If the Sphinx server is unreachable
     * @throws  SearchInvalidException         If the values of the search weren't compatible with the domain
     */
    protected function p_getResults()
    {
        $this->profiler->log('Search::getResults Start');
        extract($this->options);

        $boards = [];
        $input = $this->getUserInput();

        if ($this->radix !== null) {
            $boards[] = $this->radix;
        } elseif ($input['boards'] !== null) {
            foreach ($input['boards'] as $board) {
                $b = $this->radix_coll->getByShortname($board);
                if ($b) {
                    $boards[] = $b;
                }
            }
        }

        // search all boards if none selected
        if (count($boards) == 0) {
            $boards = $this->radix_coll->getAll();
        }

        // if image is set, get either the media_hash or media_id
        if ($input['image'] !== null && substr($input['image'], -2) !== '==') {
            $input['image'] .= '==';
        }

        if ($this->radix === null && !$this->preferences->get('foolfuuka.sphinx.global')) {
            throw new SearchRequiresSphinxException(_i('Sorry, the global search function has not been enabled.'));
        }

        if ($this->radix !== null && !$this->radix->sphinx) {
            throw new SearchRequiresSphinxException(_i('Sorry, this board does not have search enabled.'));
        }

        $sphinx = explode(':', $this->preferences->get('foolfuuka.sphinx.listen'));
        $conn = new SphinxConnnection();
        $conn->setParams([
            'host' => $sphinx[0],
            'port' => $sphinx[1],
            'options' => [MYSQLI_OPT_CONNECT_TIMEOUT => 5]
        ]);

        $indices = [];
        foreach ($boards as $radix) {
            if (!$radix->sphinx) {
                continue;
            }

            $indices[] = $radix->shortname.'_ancient';
            $indices[] = $radix->shortname.'_main';
            $indices[] = $radix->shortname.'_delta';
        }

        // establish connection
        try {
            $query = SphinxQL::create($conn)->select('id', 'board', 'tnum')->from($indices)
                ->setFullEscapeChars(['\\', '(', ')', '|', '-', '!', '@', '%', '~', '"', '&', '/', '^', '$', '='])
                ->setHalfEscapeChars(['\\', '(', ')', '!', '@', '%', '~', '&', '/', '^', '$', '=']);
        } catch (\Foolz\SphinxQL\Exception\ConnectionException $e) {
            throw new SearchSphinxOfflineException($this->preferences->get('foolfuuka.sphinx.custom_message', _i('The search backend is currently unavailable.')));
        }

        // process user input
        if ($input['subject'] !== null) {
            $query->match('title', $input['subject']);
        }

        if ($input['text'] !== null) {
            if (mb_strlen($input['text'], 'utf-8') < 1) {
                return [];
            }

            $query->match('comment', $input['text'], true);
        }

        if ($input['username'] !== null) {
            $query->match('name', $input['username']);
        }

        if ($input['tripcode'] !== null) {
            $query->match('trip', '"'.$input['tripcode'].'"');
        }

        if ($input['email'] !== null) {
            $query->match('email', $input['email']);
        }

        if ($input['capcode'] !== null) {
            switch ($input['capcode']) {
                case 'user':
                    $query->where('cap', ord('N'));
                    break;
                case 'mod':
                    $query->where('cap', ord('M'));
                    break;
                case 'dev':
                    $query->where('cap', ord('D'));
                    break;
                case 'admin':
                    $query->where('cap', ord('A'));
                    break;
            }
        }

        if ($input['uid'] !== null) {
            $query->match('pid', $input['uid']);
        }

        if ($input['country'] !== null) {
            $query->match('country', $input['country'], true);
        }

        if ($this->getAuth()->hasAccess('comment.see_ip') && $input['poster_ip'] !== null) {
            $query->where('pip', (int) Inet::ptod($input['poster_ip']));
        }

        if ($input['filename'] !== null) {
            $query->match('media_filename', $input['filename']);
        }

        if ($input['image'] !== null) {
            $query->match('media_hash', '"'.$input['image'].'"');
        }

        if ($input['deleted'] !== null) {
            switch ($input['deleted']) {
                case 'deleted':
                    $query->where('is_deleted', 1);
                    break;
                case 'not-deleted':
                    $query->where('is_deleted', 0);
                    break;
            }
        }

        if ($input['ghost'] !== null) {
            switch ($input['ghost']) {
                case 'only':
                    $query->where('is_internal', 1);
                    break;
                case 'none':
                    $query->where('is_internal', 0);
                    break;
            }
        }

        if ($input['filter'] !== null) {
            switch ($input['filter']) {
                case 'image':
                    $query->where('has_image', 0);
                    break;
                case 'text':
                    $query->where('has_image', 1);
                    break;
            }
        }

        if ($input['type'] !== null) {
            switch ($input['type']) {
                case 'sticky':
                    $query->where('is_sticky', 1);
                    break;
                case 'op':
                    $query->where('is_op', 1);
                    break;
                case 'posts':
                    $query->where('is_op', 0);
                    break;
            }
        }

        if ($input['start'] !== null) {
            $query->where('timestamp', '>=', intval(strtotime($input['start'])));
        }

        if ($input['end'] !== null) {
            $query->where('timestamp', '<=', intval(strtotime($input['end'])));
        }

        if ($input['results'] !== null && $input['results'] == 'thread') {
            $query->groupBy('tnum');
            $query->withinGroupOrderBy('is_op', 'desc');
        }

        if ($input['order'] !== null && $input['order'] == 'asc') {
            $query->orderBy('timestamp', 'ASC');
        } else {
            $query->orderBy('timestamp', 'DESC');
        }

        $max_matches = $this->preferences->get('foolfuuka.sphinx.max_matches', 5000);

        // set sphinx options
        $query->limit($limit)
            ->offset((($page * $limit) - $limit) >= $max_matches ? ($max_matches - 1) : ($page * $limit) - $limit)
            ->option('max_matches', (int) $max_matches)
            ->option('reverse_scan', ($input['order'] === 'asc') ? 0 : 1);

        // submit query
        try {
            $this->profiler->log('Start: SphinxQL: '.$query->compile()->getCompiled());
            $search = $query->execute();
            $this->profiler->log('Stop: SphinxQL');
        } catch(\Foolz\SphinxQL\Exception\DatabaseException $e) {
            $this->logger->error('Search Error: '.$e->getMessage());
            throw new SearchInvalidException(_i('The search backend returned an error.'));
        }

        // no results found
        if (!count($search)) {
            $this->comments_unsorted = [];
            $this->comments = [];

            throw new SearchEmptyResultException(_i('No results found.'));
        }

        $sphinx_meta = Helper::pairsToAssoc(Helper::create($conn)->showMeta()->execute());
        $this->total_count = $sphinx_meta['total'];
        $this->total_found = $sphinx_meta['total_found'];

        // populate sql array for full records
        $sql = [];

        foreach ($search as $doc => $result) {
            $board = $this->radix_coll->getById($result['board']);

            if ($input['results'] !== null && $input['results'] == 'thread') {
                $post = 'num = '.$this->dc->getConnection()->quote($result['tnum']).' AND subnum = 0';
            } else {
                $post = 'doc_id = '.$this->dc->getConnection()->quote($result['id']);
            }

            $sql[] = $this->dc->qb()
                ->select('*, '.$result['board'].' AS board_id')
                ->from($board->getTable(), 'r')
                ->leftJoin('r', $board->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                ->where($post)
                ->getSQL();
        }

        $result = $this->dc->getConnection()
            ->executeQuery(implode(' UNION ', $sql))
            ->fetchAll();

        // no results found IN DATABASE, but we might still get a search count from Sphinx
        if (!count($result)) {
            $this->comments_unsorted = [];
            $this->comments = [];
        } else {
            // process results
            foreach ($result as $key => $row) {
                $board = ($this->radix !== null ? $this->radix : $this->radix_coll->getById($row['board_id']));
                $bulk = new CommentBulk();
                $bulk->import($row, $board);
                $this->comments_unsorted[] = $bulk;
                unset($result[$key]);
            }
        }

        $this->comments[0]['posts'] = $this->comments_unsorted;

        return $this;
    }

    /**
     * Returns the total number of results found WITHOUT max_matches.
     *
     * @return  int
     */
    public function getTotalResults()
    {
        return $this->total_found;
    }
}
