<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Logger;
use Foolz\Foolframe\Model\Preferences;
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

    public function __construct(\Foolz\Foolframe\Model\Context $context)
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
                'name' => 'uid',
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
                'label' => _i('Image hash'),
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
                'label' => _i('Show posts'),
                'name' => 'filter',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'text', 'text' => _i('Only With Images')],
                    ['value' => 'image', 'text' => _i('Only Without Images')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Deleted posts'),
                'name' => 'deleted',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'deleted', 'text' => _i('Only Deleted Posts')],
                    ['value' => 'not-deleted', 'text' => _i('Only Non-Deleted Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Ghost posts'),
                'name' => 'ghost',
                'elements' => [
                    ['value' => false, 'text' => _i('All')],
                    ['value' => 'only', 'text' => _i('Only Ghost Posts')],
                    ['value' => 'none', 'text' => _i('Only Non-Ghost Posts')]
                ]
            ],
            [
                'type' => 'radio',
                'label' => _i('Results'),
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
     * @return  \Foolz\Foolfuuka\Model\Search  The current object
     */
    protected function p_getSearch($arguments)
    {
        // prepare
        $this->setMethodFetching('getSearchComments')
            ->setMethodCounting('getSearchCount')
            ->setOptions([
                'args' => $arguments,
                'limit' => 25,
            ]);

        return $this;
    }

    /**
     * Gets the search results
     *
     * @return  \Foolz\Foolfuuka\Model\Search  The current object
     * @throws  SearchEmptyResultException     If there's no results to display
     * @throws  SearchRequiresSphinxException  If the search submitted requires Sphinx to run
     * @throws  SearchSphinxOfflineException   If the Sphinx server is unreachable
     * @throws  SearchInvalidException         If the values of the search weren't compatible with the domain
     */
    protected function p_getSearchComments()
    {
        $this->profiler->log('Board::getSearchComments Start');
        extract($this->options);

        // set all empty fields to null
        $search_fields = ['boards', 'subject', 'text', 'username', 'tripcode', 'email', 'capcode', 'uid', 'poster_ip',
            'filename', 'image', 'deleted', 'ghost', 'filter', 'type', 'start', 'end', 'results', 'order'];

        foreach ($search_fields as $field) {
            if (!isset($args[$field])) {
                $args[$field] = null;
            }
        }

        // populate an array containing all boards that would be searched
        $boards = [];

        if ($args['boards'] !== null) {
            foreach ($args['boards'] as $board) {
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
        if ($args['image'] !== null) {
            if (substr($args['image'], -2) !== '==') {
                $args['image'] .= '==';
            }

            // if board is set, retrieve media_id
            if ($this->radix !== null) {
                try {
                    $media = $this->media_factory->getByMediaHash($this->radix, $args['image']);
                } catch (MediaNotFoundException $e) {
                    $this->comments_unsorted = [];
                    $this->comments = [];

                    $this->profiler->log('Board::getSearchComments Ended Prematurely');
                    throw new SearchEmptyResultException(_i('No results found.'));
                }

                $args['image'] = $media->media_id;
            }
        }

        if ($this->radix === null && !$this->preferences->get('foolfuuka.sphinx.global')) {
            // global search requires sphinx
            throw new SearchRequiresSphinxException(_i('Sorry, this action requires the Sphinx to be installed and running.'));
        } elseif (($this->radix === null && $this->preferences->get('foolfuuka.sphinx.global')) || ($this->radix !== null && $this->radix->sphinx)) {
            // configure sphinx connection params
            $sphinx = explode(':', $this->preferences->get('foolfuuka.sphinx.listen'));
            $conn = new SphinxConnnection();
            $conn->setParams(['host' => $sphinx[0], 'port' => $sphinx[1], 'options' => [MYSQLI_OPT_CONNECT_TIMEOUT => 5]]);
            $conn->silenceConnectionWarning(true);

            // establish connection
            try {
                SphinxQL::forge($conn);
            } catch (\Foolz\SphinxQL\ConnectionException $e) {
                throw new SearchSphinxOfflineException(_i('The search backend is currently unavailable.'));
            }

            // determine if all boards will be used for search or not
            if ($this->radix == null) {
                $indexes = [];

                foreach ($boards as $radix) {
                    if (!$radix->sphinx) {
                        continue;
                    }

                    $indexes[] = $radix->shortname.'_ancient';
                    $indexes[] = $radix->shortname.'_main';
                    $indexes[] = $radix->shortname.'_delta';
                }
            } else {
                $indexes = [
                    $this->radix->shortname.'_ancient',
                    $this->radix->shortname.'_main',
                    $this->radix->shortname.'_delta'
                ];
            }

            // start search query
            $query = SphinxQL::forge()->select('id', 'board')->from($indexes);

            // parse search params
            if ($args['subject'] !== null) {
                $query->match('title', $args['subject']);
            }

            if ($args['text'] !== null) {
                if (mb_strlen($args['text'], 'utf-8') < 1) {
                    return [];
                }

                $query->match('comment', $args['text'], true);
            }

            if ($args['username'] !== null) {
                $query->match('name', $args['username']);
            }

            if ($args['tripcode'] !== null) {
                $query->match('trip', '"'.$args['tripcode'].'"');
            }

            if ($args['email'] !== null) {
                $query->match('email', $args['email']);
            }

            if ($args['capcode'] !== null) {
                if ($args['capcode'] === 'user') {
                    $query->where('cap', ord('N'));
                } elseif ($args['capcode'] === 'mod') {
                    $query->where('cap', ord('M'));
                } elseif ($args['capcode'] === 'admin') {
                    $query->where('cap', ord('A'));
                } elseif ($args['capcode'] === 'dev') {
                    $query->where('cap', ord('D'));
                }
            }

            if ($args['uid'] !== null) {
                $query->match('pid', $args['uid']);
            }

            if ($this->getAuth()->hasAccess('comment.see_ip') && $args['poster_ip'] !== null) {
                $query->where('pip', (int) Inet::ptod($args['poster_ip']));
            }

            if ($args['filename'] !== null) {
                $query->match('media_filename', $args['filename']);
            }

            if ($args['image'] !== null) {
                if ($this->radix !== null) {
                    $query->where('mid', (int) $args['image']);
                } else {
                    $query->match('media_hash', '"'.$args['image'].'"');
                }
            }

            if ($args['deleted'] !== null) {
                if ($args['deleted'] == 'deleted') {
                    $query->where('is_deleted', 1);
                }

                if ($args['deleted'] == 'not-deleted') {
                    $query->where('is_deleted', 0);
                }
            }

            if ($args['ghost'] !== null) {
                if ($args['ghost'] == 'only') {
                    $query->where('is_internal', 1);
                }

                if ($args['ghost'] == 'none') {
                    $query->where('is_internal', 0);
                }
            }

            if ($args['filter'] !== null) {
                if ($args['filter'] == 'image') {
                    $query->where('has_image', 0);
                }

                if ($args['filter'] == 'text') {
                    $query->where('has_image', 1);
                }
            }

            if ($args['type'] !== null) {
                if ($args['type'] == 'sticky') {
                    $query->where('is_sticky', 1);
                }

                if ($args['type'] == 'op') {
                    $query->where('is_op', 1);
                }

                if ($args['type'] == 'posts') {
                    $query->where('is_op', 0);
                }
            }

            if ($args['start'] !== null) {
                $query->where('timestamp', '>=', intval(strtotime($args['start'])));
            }

            if ($args['end'] !== null) {
                $query->where('timestamp', '<=', intval(strtotime($args['end'])));
            }

            if ($args['results'] !== null) {
                if ($args['results'] == 'op') {
                    $query->groupBy('thread_num');
                    $query->withinGroupOrderBy('is_op', 'desc');
                }

                if ($args['results'] == 'posts') {
                    $query->where('is_op', 0);
                }
            }

            if ($args['order'] !== null && $args['order'] == 'asc') {
                $query->orderBy('timestamp', 'ASC');
            } else {
                $query->orderBy('timestamp', 'DESC');
            }

            $max_matches = $this->preferences->get('foolfuuka.sphinx.max_matches', 5000);

            // set sphinx options
            $query->limit($limit)
                ->offset((($page * $limit) - $limit) >= $max_matches ? ($max_matches - 1) : ($page * $limit) - $limit)
                ->option('max_matches', (int) $max_matches)
                ->option('reverse_scan', ($args['order'] === 'asc') ? 0 : 1);

            // submit query
            try {
                $search = $query->execute();
            } catch(\Foolz\SphinxQL\DatabaseException $e) {
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
                $sql[] = $this->dc->qb()
                    ->select('*, '.$result['board'].' AS board_id')
                    ->from($board->getTable(), 'r')
                    ->leftJoin('r', $board->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
                    ->where('doc_id = '.$this->dc->getConnection()->quote($result['id']))
                    ->getSQL();
            }

            $result = $this->dc->getConnection()
                ->executeQuery(implode(' UNION ', $sql))
                ->fetchAll();
        } else {
            // this is not implemented yet, would require some sort of MySQL search
            throw new SearchRequiresSphinxException(_i('Sorry, this board does not have search enabled.'));
        }

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
    public function getSearchCount()
    {
        return $this->total_found;
    }
}
