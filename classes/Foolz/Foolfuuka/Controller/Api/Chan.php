<?php

namespace Foolz\Foolfuuka\Controller\Api;

use \Foolz\Inet\Inet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Chan
{
    protected $_radix = null;
    protected $_theme = null;
    protected $format = 'json';

    protected $request = null;
    protected $response = null;

    public function before(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 604800');

        // this has already been forged in the foolfuuka bootstrap
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');

        if (\Input::get('theme')) {
            try {
                $theme_name = \Input::get('theme', \Cookie::get('theme')) ? : \Preferences::get('foolfuuka.theme.default');
                $theme = $theme_instance->get($theme_name);
                if (!isset($theme->enabled) || !$theme->enabled) {
                    throw new \OutOfBoundsException;
                }
                $this->_theme = $theme;
            } catch (\OutOfBoundsException $e) {
                $theme_name = 'foolz/foolfuuka-theme-foolfuuka';
                $this->_theme = $theme_instance->get($theme_name);
            }
        }
    }

    public function router(Request $request, $method)
    {
        // create response object, store request object
        $this->response = new JsonResponse();
        $this->request = $request;

        if ($request->getMethod() == 'GET') {
            return [$this, 'get_'.$method, []];
        }

        if ($request->getMethod() == 'POST') {
            return [$this, 'post_'.$method, []];
        }
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
        $board = \Input::get('board');

        if (!$board) {
            $board = \Input::post('board');
        }

        if (!$board) {
            //$this->response->setData(['error' => _i('You didn\'t select a board')]);
            return false;
        }

        if (!$this->_radix = \Radix::setSelectedByShortname($board)) {
            //$this->response->setData(['error' => _i('The board you selected doesn\'t exist')]);
            return false;
        }

        return true;
    }

    public function get_404()
    {
        return $this->response->setData(['error' => _i('Invalid Method.')])->setStatusCode(404);
    }

    public function get_index()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(404);
        }

        $page = \Input::get('page');

        if (!$page) {
            return $this->response->setData(['error' => _i('The "page" parameter is missing.')])->setStatusCode(404);
        }

        if (!ctype_digit((string) $page)) {
            return $this->response->setData(['error' => _i('The value for "page" is invalid.')])->setStatusCode(404);
        }

        $page = intval($page);

        try {
            $options = [
                'per_page' => $this->_radix->getValue('threads_per_page'),
                'per_thread' => 5,
                'order' => 'by_thread'
            ];

            $board = \Board::forge()
                ->getLatest()
                ->setRadix($this->_radix)
                ->setPage($page)
                ->setApi(['theme' => $this->_theme, 'board' => false])
                ->setOptions($options);

            $this->response->setData($board->getComments());
        } catch (\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_search()
    {
        // check all allowed search modifiers and apply only these
        $modifiers = [
            'boards', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode', 'uid',
            'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end', 'order', 'page'
        ];

        if (\Auth::has_access('comment.see_ip')) {;
            $modifiers[] = 'poster_ip';
        }

        $search = [];

        foreach ($modifiers as $modifier) {
            $search[$modifier] = \Input::get($modifier);
        }

        foreach ($search as $key => $value) {
            if (in_array($key, $modifiers) && $value !== null) {
                $search[$key] = trim(rawurldecode($value));
            }
        }

        if ($search['boards'] !== null) {
            $search['boards'] = explode('.', $search['boards']);
        }

        if ($search['image'] !== null) {
            $search['image'] = base64_encode(\Media::urlsafe_b64decode($search['image']));
        }

        if ($search['poster_ip'] !== null) {
            if (!filter_var($search['poster_ip'], FILTER_VALIDATE_IP)) {
                return $this->error(_i('The poster IP you inserted is not a valid IP address.'));
            }

            $search['poster_ip'] = \Foolz\Inet\Inet::ptod($search['poster_ip']);
        }

        try {
            $board = \Search::forge()
                ->getSearch($search)
                ->setRadix($this->_radix)
                ->setPage($search['page'] ? $search['page'] : 1)
                ->setApi(['theme' => $this->_theme, 'board' => true]);

            $comments = $board->getComments();

            $this->response->setData($comments);
        } catch (\Foolz\Foolfuuka\Model\SearchException $e) {
            return $this->response->setData(['error' => $e->getMessage()]);
        } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
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
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(404);
        }

        $num = \Input::get('num');
        $latest_doc_id = \Input::get('latest_doc_id');

        if (!$num) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(404);
        }

        if (!ctype_digit((string) $num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(404);
        }

        $num = intval($num);

        try {
            // build an array if we have more specifications
            if ($latest_doc_id) {
                if (!ctype_digit((string) $latest_doc_id)) {
                    return $this->response->setData(['error' => _i('The value for "latest_doc_id" is malformed.')])->setStatusCode(404);
                }

                $options = [
                    'type' => 'from_doc_id',
                    'latest_doc_id' => $latest_doc_id,
                    'realtime' => true,
                    'controller_method' =>
                        ctype_digit((string) \Input::get('last_limit')) ? 'last/'.\Input::get('last_limit') : 'thread'
                ];

                $board = \Board::forge()
                    ->getThread($num)
                    ->setRadix($this->_radix)
                    ->setApi(['theme' => $this->_theme, 'board' => false])
                    ->setOptions($options);

                $comments = $board->getComments();

                if (!count($comments)) {
                    $this->response->setData([])->setStatusCode(204);
                }

                $this->response->setData($comments);
            } else {
                $options = [
                    'type' => 'thread',
                ];

                $board = \Board::forge()
                    ->getThread($num)
                    ->setRadix($this->_radix)
                    ->setApi(['theme' => $this->_theme, 'board' => false])
                    ->setOptions($options);

                $thread_status = $board->getThreadStatus();
                $last_modified = $thread_status['last_modified'];

                $this->setLastModified($last_modified);

                if (!$this->response->isNotModified($this->request)) {
                    $this->response->setData($board->getComments());
                }
            }
        } catch (\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_post()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(404);
        }

        $num = \Input::get('num');

        if (!$num) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(404);
        }

        if (!\Board::isValidPostNumber($num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(404);
        }

        try {
            $board = \Board::forge()
                ->getPost($num)
                ->setRadix($this->_radix)
                ->setApi(['board' => false, 'theme' => $this->_theme]);

            $comment = current($board->getComments());

            $last_modified = $comment->timestamp_expired ?: $comment->timestamp;

            $this->setLastModified($last_modified);

            if (!$this->response->isNotModified($this->request)) {
                $this->response->setData($comment);
            }
        } catch (\Foolz\Foolfuuka\Model\BoardPostNotFoundException $e) {
            return $this->response->setData(['error' => _i('Post not found.')]);
        } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response;
    }

    public function post_user_actions()
    {
        if (!\Security::check_token()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(404);
        }

        if (\Input::post('action') === 'report') {
            try {
                \Report::add($this->_radix, \Input::post('doc_id'), \Input::post('reason'));
            } catch (\Foolz\Foolfuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('You have successfully submitted a report for this post.')]);
        }

        if (\Input::post('action') === 'report_media') {
            try {
                \Report::add($this->_radix, \Input::post('media_id'), \Input::post('reason'), null, 'media_id');
            } catch (\Foolz\Foolfuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('This media was reported.')]);
        }

        if (\Input::post('action') === 'delete') {
            try {
                $comments = \Board::forge()
                    ->getPost()
                    ->setOptions('doc_id', \Input::post('doc_id'))
                    ->setCommentOptions('clean', false)
                    ->setRadix($this->_radix)
                    ->getComments();

                $comment = current($comments);
                $comment->delete(\Input::post('password'));
            } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            } catch (\Foolz\Foolfuuka\Model\CommentDeleteWrongPassException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('This post was deleted.')]);
        }
    }

    public function post_mod_actions()
    {
        if (!\Security::check_token()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!\Auth::has_access('comment.mod_capcode')) {
            return $this->response->setData(['error' => _i('Access Denied.')])->setStatusCode(403);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(404);
        }

        if (\Input::post('action') === 'delete_report') {
            try {
                \Report::delete(\Input::post('id'));
            } catch (\Foolz\Foolfuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('The report was deleted.')]);
        }

        if (\Input::post('action') === 'delete_post') {
            try {
                $comments = \Board::forge()
                    ->getPost()
                    ->setOptions('doc_id', \Input::post('id'))
                    ->setRadix($this->_radix)
                    ->getComments();

                $comment = current($comments);
                $comment->delete();
            } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This post was deleted.')]);
        }

        if (\Input::post('action') === 'delete_image') {
            try {
                \Media::getByMediaId($this->_radix, \Input::post('id'))->delete(true, true, true);
            } catch (\Foolz\Foolfuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This image was deleted.')]);
        }

        if (\Input::post('action') === 'ban_image_local' || \Input::post('action') === 'ban_image_global') {
            $global = false;
            if (\Input::post('action') === 'ban_image_global') {
                $global = true;
            }

            try {
                \Media::getByMediaId($this->_radix, \Input::post('id'))->ban($global);
            } catch (\Foolz\Foolfuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This image was banned.')]);
        }

        if (\Input::post('action') === 'ban_user') {
            try {
                \Ban::add(\Foolz\Inet\Inet::ptod(\Input::post('ip')),
                    \Input::post('reason'),
                    \Input::post('length'),
                    \Input::post('board_ban') === 'global' ? array() : array($this->_radix->id)
                );
            } catch (\Foolz\Foolfuuka\Model\BanException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            }

            return $this->response->setData(['success' => _i('This user was banned.')]);
        }
    }
}
