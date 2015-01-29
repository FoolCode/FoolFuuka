<?php

namespace Foolz\FoolFuuka\Controller\Api;

use Foolz\FoolFrame\Controller\Common;
use Foolz\FoolFrame\Model\Config;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\FoolFrame\Model\Uri;
use Foolz\FoolFuuka\Model\BanFactory;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\CommentFactory;
use Foolz\FoolFuuka\Model\Media;
use Foolz\FoolFuuka\Model\MediaFactory;
use Foolz\FoolFuuka\Model\Radix;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\FoolFuuka\Model\Search;
use Foolz\Inet\Inet;
use Foolz\Profiler\Profiler;
use Foolz\Theme\Builder;
use Foolz\Theme\Theme;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Chan extends Common
{
    /**
     * @var Radix
     */
    protected $radix;

    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @var Builder
     */
    protected $builder = null;

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var Response
     */
    protected $response = null;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    /**
     * @var CommentFactory
     */
    protected $comment_factory;

    /**
     * @var BanFactory
     */
    protected $ban_factory;

    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var Comment
     */
    protected $comment_obj;

    /**
     * @var Media
     */
    protected $media_obj;

    public function before()
    {
        $this->config = $this->getContext()->getService('config');
        $this->preferences = $this->getContext()->getService('preferences');
        $this->uri = $this->getContext()->getService('uri');
        $this->profiler = $this->getContext()->getService('profiler');
        $this->radix_coll = $this->getContext()->getService('foolfuuka.radix_collection');
        $this->media_factory = $this->getContext()->getService('foolfuuka.media_factory');
        $this->comment_factory = $this->getContext()->getService('foolfuuka.comment_factory');
        $this->ban_factory = $this->getContext()->getService('foolfuuka.ban_factory');
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');

        // this has already been forged in the foolfuuka bootstrap
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');

        if ($this->getQuery('theme')) {
            try {
                $theme_name = $this->getQuery('theme', $this->getCookie('theme')) ? : $this->preferences->get('foolfuuka.theme.default');
                $theme = $theme_instance->get($theme_name);
                if (!isset($theme->enabled) || !$theme->enabled) {
                    throw new \OutOfBoundsException;
                }
                $this->theme = $theme;
            } catch (\OutOfBoundsException $e) {
                $theme_name = 'foolz/foolfuuka-theme-foolfuuka';
                $this->theme = $theme_instance->get($theme_name);
            }

            $this->builder = $this->theme->createBuilder();
            $this->builder->getParamManager()->setParams([
                'context' => $this->getContext(),
                'request' => $this->getRequest()
            ]);
        }

        // convenience objects for saving some RAM
        $this->comment_obj = new Comment($this->getContext());
        $this->media_obj = new Media($this->getContext());
    }

    public function router($method)
    {
        // create response object, store request object
        $this->response = new JsonResponse();

        // enforce CORS on application level
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Credentials', 'true');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->headers->set('Access-Control-Max-Age', '604800');

        $request = $this->getRequest();
        if ($request->getMethod() == 'GET' && method_exists($this, 'get_'.$method)) {
            return [$this, 'get_'.$method, []];
        }

        if ($request->getMethod() == 'POST' && method_exists($this, 'post_'.$method)) {
            return [$this, 'post_'.$method, []];
        }

        return [$this, 'get_404', []];
    }

    public function setLastModified($timestamp = 0, $max_age = 0)
    {
        $this->response->headers->addCacheControlDirective('must-revalidate', true);
        $this->response->setLastModified(new \DateTime('@'.$timestamp));
        $this->response->setMaxAge($max_age);
    }

    /**
     * Commodity to check that the shortname is not wrong and return a coherent error
     */
    protected function check_board()
    {
        $board = $this->getQuery('board', $this->getPost('board', null));

        if ($board === null) {
            return false;
        }

        if (!$this->radix = $this->radix_coll->getByShortname($board)) {
            return false;
        }

        return true;
    }

    public function apify($bulk, $controller_method = 'thread')
    {
        $this->comment_obj->setBulk($bulk);
        $comment_force = [
            'getTitleProcessed',
            'getNameProcessed',
            'getEmailProcessed',
            'getTripProcessed',
            'getPosterHashProcessed',
            'getOriginalTimestamp',
            'getFourchanDate',
            'getCommentSanitized',
            'getCommentProcessed', // this is necessary also to get backlinks parsed
            'getPosterCountryNameProcessed'
        ];

        foreach ($comment_force as $value) {
            $this->comment_obj->$value();
        }

        $m = null;
        if ($bulk->media !== null) {
            $media_force = [
                'getMediaFilenameProcessed',
                'getMediaLink',
                'getThumbLink',
                'getRemoteMediaLink',
                'getMediaStatus',
                'getSafeMediaHash'
            ];

            $this->media_obj->setBulk($bulk);
            $m = $this->media_obj;

            foreach ($media_force as $value) {
                $this->media_obj->$value($this->getRequest());
            }
        }

        if ($this->builder) {
            $this->builder->getParamManager()->setParam('controller_method', $controller_method);
            $partial = $this->builder->createPartial('board_comment', 'board_comment');
            $partial->getParamManager()
                ->setParam('p', $this->comment_obj)
                ->setParam('p_media', $m);

            $bulk->comment->formatted = $partial->build();
            $partial->clearBuilt();
        }
    }

    public function get_404()
    {
        return $this->response->setData(['error' => _i('Requested resource does not exist.')])->setStatusCode(404);
    }

    public function get_index()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }

        $page = $this->getQuery('page');

        if (!$page) {
            return $this->response->setData(['error' => _i('The "page" parameter is missing.')])->setStatusCode(422);
        }

        if (!ctype_digit((string) $page)) {
            return $this->response->setData(['error' => _i('The value for "page" is invalid.')])->setStatusCode(422);
        }

        $page = intval($page);

        try {
            $options = [
                'per_page' => $this->radix->getValue('threads_per_page'),
                'per_thread' => 5,
                'order' => 'by_thread'
            ];

            $board = Board::forge($this->getContext())
                ->getLatest()
                ->setRadix($this->radix)
                ->setPage($page)
                ->setOptions($options);

            foreach ($board->getCommentsUnsorted() as $comment) {
                $this->apify($comment);
            }

            $this->response->setData($board->getComments());
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_search()
    {
        // check all allowed search modifiers and apply only these
        $modifiers = [
            'boards', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode', 'uid', 'country',
            'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end', 'results', 'order', 'page'
        ];

        if ($this->getAuth()->hasAccess('comment.see_ip')) {;
            $modifiers[] = 'poster_ip';
        }

        $search = [];

        foreach ($modifiers as $modifier) {
            $search[$modifier] = $this->getQuery($modifier, null);
        }

        foreach ($search as $key => $value) {
            if (in_array($key, $modifiers) && $value !== null) {
                if (trim($value) !== '') {
                    $search[$key] = rawurldecode(trim($value));
                } else {
                    unset($search[$key]);
                }
            }
        }

        if ($search['boards'] !== null) {
            $search['boards'] = explode('.', $search['boards']);
        }

        if ($search['image'] !== null) {
            $search['image'] = base64_encode(Media::urlsafe_b64decode($search['image']));
        }

        if ($this->getAuth()->hasAccess('comment.see_ip') && $search['poster_ip'] !== null) {
            if (!filter_var($search['poster_ip'], FILTER_VALIDATE_IP)) {
                return $this->response->setData(['error' => _i('The poster IP you inserted is not a valid IP address.')]);
            }

            $search['poster_ip'] = Inet::ptod($search['poster_ip']);
        }

        try {
            $board = Search::forge($this->getContext())
                ->getSearch($search)
                ->setRadix($this->radix)
                ->setPage($search['page'] ? $search['page'] : 1);

            foreach ($board->getCommentsUnsorted() as $comment) {
                $this->apify($comment);
            }

            $comments = $board->getComments();

            $this->response->setData($comments);
        } catch (\Foolz\FoolFuuka\Model\SearchException $e) {
            return $this->response->setData(['error' => $e->getMessage()]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return $this->response;
    }

    /**
     * Returns a thread
     *
     * Available filters: num (required)
     *
     * @author Woxxy
     */
    public function get_thread()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }

        $num = $this->getQuery('num', null);
        $latest_doc_id = $this->getQuery('latest_doc_id', null);

        if ($num === null) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(422);
        }

        if (!ctype_digit((string) $num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(422);
        }

        $num = intval($num);

        try {
            // build an array if we have more specifications
            if ($latest_doc_id !== null && $latest_doc_id > 0) {
                if (!ctype_digit((string) $latest_doc_id)) {
                    return $this->response->setData(['error' => _i('The value for "latest_doc_id" is malformed.')])->setStatusCode(422);
                }

                $board = Board::forge($this->getContext())
                    ->getThread($num)
                    ->setRadix($this->radix)
                    ->setOptions([
                        'type' => 'from_doc_id',
                        'latest_doc_id' => $latest_doc_id
                    ]);

                foreach ($board->getCommentsUnsorted() as $comment) {
                    $this->apify($comment, ctype_digit((string) $this->getQuery('last_limit')) ? 'last/'.$this->getQuery('last_limit') : 'thread');
                }

                $comments = $board->getComments();

                if (!count($comments)) {
                    $this->response->setData([])->setStatusCode(204);
                } else {
                    $this->response->setData($comments);
                }

            } else {
                $options = [
                    'type' => 'thread',
                ];

                $board = Board::forge($this->getContext())
                    ->getThread($num)
                    ->setRadix($this->radix)
                    ->setOptions($options);

                $thread_status = $board->getThreadStatus();
                $last_modified = $thread_status['last_modified'];

                $this->setLastModified($last_modified);

                if (!$this->response->isNotModified($this->request)) {
                    $bulks = $board->getCommentsUnsorted();
                    foreach ($bulks as $bulk) {
                        $this->apify($bulk, ctype_digit((string) $this->getQuery('last_limit')) ? 'last/'.$this->getQuery('last_limit') : 'thread');
                    }

                    $this->response->setData($board->getComments());
                }
            }
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_post()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        $num = $this->getQuery('num');

        if (!$num) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(422);
        }

        if (!Board::isValidPostNumber($num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(422);
        }

        try {
            $comment = Board::forge($this->getContext())
                ->getPost($num)
                ->setRadix($this->radix)
                ->getComment();

            $this->apify($comment);
            $this->setLastModified($comment->comment->timestamp_expired ?: $comment->comment->timestamp);

            if (!$this->response->isNotModified($this->request)) {
                $this->response->setData($comment);
            }
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            return $this->response->setData(['error' => _i('Post not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return $this->response;
    }

    public function post_user_actions()
    {
        if (!$this->checkCsrfToken()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        if ($this->getPost('action') === 'report') {
            try {
                $this->report_coll->add(
                    $this->radix,
                    $this->getPost('doc_id'),
                    $this->getPost('reason'),
                    Inet::ptod($this->getRequest()->getClientIp())
                );
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('You have successfully submitted a report for this post.')]);
        }

        /*
        if ($this->getPost('action') === 'report_media') {
            try {
                $this->report_coll->add($this->radix, $this->getPost('media_id'), $this->getPost('reason'), null, 'media_id');
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('This media was reported.')]);
        }
        */

        if ($this->getPost('action') === 'delete') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('doc_id'))
                    ->setCommentOptions('clean', false)
                    ->setRadix($this->radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                $comment->delete($this->getPost('password'));
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            } catch (\Foolz\FoolFuuka\Model\CommentDeleteWrongPassException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('This post was deleted.')]);
        }
    }

    public function post_mod_actions()
    {
        if (!$this->checkCsrfToken()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->getAuth()->hasAccess('comment.mod_capcode')) {
            return $this->response->setData(['error' => _i('Access Denied.')])->setStatusCode(403);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        if ($this->getPost('action') === 'delete_report') {
            try {
                $this->report_coll->delete($this->getPost('id'));
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('The report was deleted.')]);
        }

        if ($this->getPost('action') === 'delete_post') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('id'))
                    ->setRadix($this->radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                $comment->delete();
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This post was deleted.')]);
        }

        if ($this->getPost('action') === 'delete_image') {
            try {
                $media = $this->media_factory->getByMediaId($this->radix, $this->getPost('id'));
                $media = new Media($this->getContext(), CommentBulk::forge($this->radix, null, $media));
                $media->delete(true, true, true);
            } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This image was deleted.')]);
        }

        if ($this->getPost('action') === 'ban_image_local' || $this->getPost('action') === 'ban_image_global') {
            $global = false;
            if ($this->getPost('action') === 'ban_image_global') {
                $global = true;
            }

            try {
                $media = $this->media_factory->getByMediaId($this->radix, $this->getPost('id'));
                $media = new Media($this->getContext(), CommentBulk::forge($this->radix, null, $media));
                $media->ban($global);
            } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This image was banned.')]);
        }

        if ($this->getPost('action') === 'ban_user') {
            try {
                $this->ban_factory->add(Inet::ptod($this->getPost('ip')),
                    $this->getPost('reason'),
                    $this->getPost('length'),
                    $this->getPost('board_ban') === 'global' ? array() : array($this->radix->id)
                );
            } catch (\Foolz\FoolFuuka\Model\BanException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This user was banned.')]);
        }

        if ($this->getPost('action') === 'toggle_sticky') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('id'))
                    ->setRadix($this->radix)
                    ->getComment();

                $thread = Board::forge($this->getContext())
                    ->getThread($comment->comment->thread_num)
                    ->setRadix($this->radix)
                    ->setOptions(['type' => 'thread'])
                    ->getThreadStatus();

                $comment = new Comment($this->getContext(), $comment);
                $comment->setSticky((int) !$thread['sticky']);
            } catch (\Foolz\FoolFuuka\Model\CommentUpdateException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(422);
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('The sticky status of this post has been updated.')]);
        }

        if ($this->getPost('action') === 'toggle_locked') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('id'))
                    ->setRadix($this->radix)
                    ->getComment();

                $thread = Board::forge($this->getContext())
                    ->getThread($comment->comment->thread_num)
                    ->setRadix($this->radix)
                    ->setOptions(['type' => 'thread'])
                    ->getThreadStatus();

                $comment = new Comment($this->getContext(), $comment);
                $comment->setLocked((int) !$thread['closed']);
            } catch (\Foolz\FoolFuuka\Model\CommentUpdateException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(422);
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('The locked status of this post has been updated.')]);
        }
    }
}
