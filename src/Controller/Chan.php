<?php

namespace Foolz\FoolFuuka\Controller;

use Foolz\FoolFrame\Controller\Common;
use Foolz\FoolFrame\Model\Config;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\FoolFrame\Model\Uri;
use Foolz\FoolFrame\Model\Util;
use Foolz\FoolFrame\Model\Validation\ActiveConstraint\Trim;
use Foolz\FoolFrame\Model\Validation\Validator;
use Foolz\FoolFrame\Model\Cookie;
use Foolz\FoolFuuka\Model\Ban;
use Foolz\FoolFuuka\Model\BanFactory;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\CommentFactory;
use Foolz\FoolFuuka\Model\CommentInsert;
use Foolz\FoolFuuka\Model\Media;
use Foolz\FoolFuuka\Model\MediaFactory;
use Foolz\FoolFuuka\Model\Radix;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFuuka\Model\Report;
use Foolz\FoolFuuka\Model\Search;
use Foolz\Inet\Inet;
use Foolz\Profiler\Profiler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\Constraints as Assert;


class Chan extends Common
{
    /**
     * The Theme object
     *
     * @var  \Foolz\Theme\Theme
     */
    protected $theme = null;

    /**
     * A builder object with some defaults appended
     *
     * @var  \Foolz\Theme\Builder
     */
    protected $builder = null;

    /**
     * The ParamManager object of the builder
     *
     * @var  \Foolz\Theme\ParamManager
     */
    protected $param_manager = null;

    /**
     *  The Request object
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request = null;

    /**
     * The Response object
     *
     * @var Response|StreamedResponse
     */
    protected $response = null;

    /**
     * The currently selected radix
     *
     * @var  Radix|null
     */
    protected $radix = null;

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

    public function before()
    {
        $this->config = $this->getContext()->getService('config');
        $this->preferences = $this->getContext()->getService('preferences');
        $this->uri = $this->getContext()->getService('uri');
        $this->profiler = $this->getContext()->getService('profiler');
        $this->radix_coll = $this->getContext()->getService('foolfuuka.radix_collection');
        $this->media_factory = $this->getContext()->getService('foolfuuka.media_factory');
        $this->comment_factory = $this->getContext()->getService('foolfuuka.comment_factory');

        // this has already been forged in the foolfuuka bootstrap
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');

        try {
            $theme_name = $this->getQuery('theme', $this->getCookie('theme')) ? : $this->preferences->get('foolfuuka.theme.default');

            $theme_name_exploded = explode('/', $theme_name);
            if (count($theme_name_exploded) >=2) {
                $theme_name = $theme_name_exploded[0].'/'.$theme_name_exploded[1];
            }

            $theme = $theme_instance->get($theme_name);
            if (!isset($theme->enabled) || !$theme->enabled) {
                throw new \OutOfBoundsException;
            }
            $this->theme = $theme;
        } catch (\OutOfBoundsException $e) {
            $theme_name = 'foolz/foolfuuka-theme-foolfuuka';
            $this->theme = $theme_instance->get('foolz/foolfuuka-theme-foolfuuka');
        }

        // TODO this is currently bootstrapped in the foolfuuka bootstrap because we need it running before the router.
        //$this->theme->bootstrap();
        $this->builder = $this->theme->createBuilder();
        $this->param_manager = $this->builder->getParamManager();
        $this->builder->createLayout('chan');

        if (count($theme_name_exploded) == 3) {
            try {
                $this->builder->setStyle($theme_name_exploded[2]);
            } catch (\OutOfBoundsException $e) {
                // just let it go with default on getStyle()
            }
        }

        $pass = $this->getCookie('reply_password', '');
        $name = $this->getCookie('reply_name');
        $email = $this->getCookie('reply_email');

        // KEEP THIS IN SYNC WITH THE ONE IN THE POSTS ADMIN PANEL
        $to_bind = [
            'context' => $this->getContext(),
            'request' => $this->getRequest(),
            'user_name' => $name,
            'user_email' => $email,
            'user_pass' => $pass,
            'disable_headers' => false,
            'is_page' => false,
            'is_thread' => false,
            'is_last50' => false,
            'order' => false,
            'modifiers' => [],
            'backend_vars' => [
                'user_name' => $name,
                'user_email' => $email,
                'user_pass' => $pass,
                'site_url'  => $this->uri->base(),
                'default_url'  => $this->uri->base(),
                'archive_url'  => $this->uri->base(),
                'system_url'  => $this->uri->base(),
                'api_url'   => $this->uri->base(),
                'cookie_domain' => $this->config->get('foolz/foolframe', 'config', 'config.cookie_domain'),
                'cookie_prefix' => $this->config->get('foolz/foolframe', 'config', 'config.cookie_prefix'),
                'selected_theme' => $theme_name,
                'csrf_token_key' => 'csrf_token',
                'images' => [
                    'banned_image' => $this->theme->getAssetManager()->getAssetLink('images/banned-image.png'),
                    'banned_image_width' => 150,
                    'banned_image_height' => 150,
                    'missing_image' => $this->theme->getAssetManager()->getAssetLink('images/missing-image.jpg'),
                    'missing_image_width' => 150,
                    'missing_image_height' => 150,
                ],
                'gettext' => [
                    'submit_state' => _i('Submitting'),
                    'thread_is_real_time' => _i('This thread is being displayed in real time.'),
                    'update_now' => _i('Update now'),
                    'ghost_mode' => _i('This thread has entered ghost mode. Your reply will be marked as a ghost post and will only affect the ghost index.')
                ]
            ]
        ];

        $this->param_manager->setParams($to_bind);

        $this->builder->createPartial('tools_modal', 'tools_modal');
        $this->builder->createPartial('tools_search', 'tools_search');
        $this->builder->createPartial('tools_advanced_search', 'advanced_search');
    }

    public function router($method, $parameters)
    {
        $request = $this->getRequest();

        // create response object, store request object
        if ($request->isXmlHttpRequest()) {
            $this->response = new JsonResponse();
        } else {
            $this->response = new Response();
        }

        $this->request = $request;

        // let's see if we hit a radix route
        if ($request->attributes->get('radix_shortname') !== null) {
            // the radix for sure exists, we came here from the defined routes after all
            $this->radix = $this->radix_coll->getByShortname($request->attributes->get('radix_shortname'));
            $this->param_manager->setParam('radix', $this->radix);
            $backend_vars = $this->param_manager->getParam('backend_vars');
            $backend_vars['board_shortname'] = $this->radix->shortname;
            $this->param_manager->setParam('backend_vars', $backend_vars);
            $this->builder->getProps()->addTitle($this->radix->getValue('formatted_title'));

            // methods callable with a radix are prefixed with radix_
            if (method_exists($this, 'radix_'.$method)) {
                return [$this, 'radix_'.$method, $parameters];
            }

            // a board and no function means we're out of the street
            return [$this, 'action_404', []];
        }

        $this->radix = null;
        $this->param_manager->setParam('radix', null);
        $this->builder->getProps()->addTitle($this->preferences->get('foolframe.gen.website_title', $this->preferences->get('foolfuuka.gen.website_title')));

        if (method_exists($this, 'action_'.$method)) {
            return [$this, 'action_'.$method, $parameters];
        }

        return [$this, 'action_404', []];
    }

    public function setLastModified($timestamp = 0, $max_age = 0)
    {
        $time = new \DateTime('@'.$timestamp);
        $etag = md5($this->builder->getTheme()->getDir().$this->builder->getStyle().'|'.$timestamp.'|'.$max_age);

        $this->response->headers->addCacheControlDirective('must-revalidate', true);
        $this->response->setLastModified($time);
        $this->response->setEtag($etag);
        $this->response->setMaxAge($max_age);
    }

    public function action_index()
    {
        $this->param_manager->setParam('disable_headers', true);
        $this->builder->createPartial('body', 'index');

        return $this->response->setContent($this->builder->build());
    }

    public function action_404($error = null)
    {
        return $this->error($error === null ? _i('Page not found. You can use the search if you were looking for something!') : $error, 404);
    }

    protected function error($error = null, $code = 200)
    {
        $this->builder->createPartial('body', 'error')
            ->getParamManager()
            ->setParams(['error' => $error === null ? _i('We encountered an unexpected error.') : $error]);

        $this->response->setStatusCode($code);
        if ($this->response instanceof StreamedResponse) {
            $this->response->setCallback(function() {
                $this->builder->stream();
            });
        } else {
            $this->response->setContent($this->builder->build());
        }

        return $this->response;
    }

    protected function message($level = 'success', $message = null, $code = 200)
    {
        $this->builder->createPartial('body', 'message')
            ->getParamManager()
            ->setParams([
                'level' => $level,
                'message' => $message
            ]);

        return $this->response->setContent($this->builder->build())->setStatusCode($code);
    }

    public function action_theme($vendor = 'foolz', $theme = 'foolfuuka-theme-default', $style = '')
    {
        $this->builder->getProps()->addTitle(_i('Changing Theme Settings'));

        $theme = $vendor.'/'.$theme.'/'.$style;

        $this->response->headers->setCookie(new Cookie($this->getContext(), 'theme', $theme, 31536000));

        if ($this->getRequest()->headers->get('referer')) {
            $url = $this->getRequest()->headers->get('referer');
        } else {
            $url = $this->uri->base();
        }

        $this->builder->createLayout('redirect')
            ->getParamManager()
            ->setParam('url', $url);
        $this->builder->getProps()->addTitle(_i('Redirecting'));

        return $this->response->setContent($this->builder->build());
    }

    public function action_language($language = 'en_EN')
    {
        $this->response->headers->setCookie(new Cookie($this->getContext(), 'language', $language, 31536000));

        if ($this->getRequest()->headers->get('referer')) {
            $url = $this->getRequest()->headers->get('referer');
        } else {
            $url = $this->uri->base();
        }

        $this->builder->createLayout('redirect')
            ->getParamManager()
            ->setParam('url', $url);
        $this->builder->getProps()->addTitle(_i('Changing Language'));

        return $this->response->setContent($this->builder->build());
    }

    public function action_opensearch()
    {
        $this->builder->createLayout('open_search');

        return $this->response->setContent($this->builder->build());
    }

    public function radix_page_mode($_mode = 'by_post')
    {
        $mode = $_mode === 'by_thread' ? 'by_thread' : 'by_post';
        $type = $this->radix->archive ? 'archive' : 'board';
        $this->response = new RedirectResponse($this->uri->create($this->radix->shortname));
        $this->response->headers->setCookie(new Cookie($this->getContext(), 'default_theme_page_mode_'.$type, $mode, 31536000));

        return $this->response;
    }

    public function radix_page($page = 1)
    {
        $order = $this->getCookie('default_theme_page_mode_'. ($this->radix->archive ? 'archive' : 'board')) === 'by_thread'
            ? 'by_thread' : 'by_post';

        $options = [
            'per_page' => $this->radix->getValue('threads_per_page'),
            'per_thread' => 5,
            'order' => $order
        ];

        return $this->latest($page, $options);
    }

    public function radix_ghost($page = 1)
    {
        $options = [
            'per_page' => $this->radix->getValue('threads_per_page'),
            'per_thread' => 5,
            'order' => 'ghost'
        ];

        return $this->latest($page, $options);
    }

    protected function latest($page = 1, $options = [])
    {
        $this->response = new StreamedResponse();
        $this->profiler->log('Controller Chan::latest Start');

        try {
            $board = Board::forge($this->getContext())
                ->getLatest()
                ->setRadix($this->radix)
                ->setPage($page)
                ->setOptions($options);

            // execute in case there's more exceptions to handle
            $board->getComments();
            $board->getCount();
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            $this->profiler->log('Controller Chan::latest End Prematurely');

            return $this->error($e->getMessage());
        }

        if ($page > 1) {
            switch($options['order']) {
                case 'by_post':
                    $order_string = _i('Threads by latest replies');
                    break;
                case 'by_thread':
                    $order_string = _i('Threads by creation');
                    break;
                case 'ghost':
                    $order_string = _i('Threads by latest ghost replies');
                    break;
            }

            $this->builder->getProps()->addTitle(_i('Page').' '.$page);
            $this->param_manager->setParam('section_title', $order_string.' - '._i('Page').' '.$page);
        }

        $this->builder->createPartial('body', 'board')
            ->getParamManager()->setParams([
                'board' => $board->getComments(),
                'posts_per_thread' => $options['per_thread'] - 1
            ]);

        $this->param_manager->setParams([
            'is_page' => true,
            'order' => $options['order'],
            'pagination' => [
                'base_url' => $this->uri->create([$this->radix->shortname, $options['order'] === 'ghost' ? 'ghost' :
                    'page']),
                'current_page' => $page,
                'total' => $board->getPages()
            ],
            'disable_image_upload' => $this->radix->getValue('op_image_upload_necessity') === 'never'
        ]);

        if (!$this->radix->archive) {
            $this->builder->createPartial('tools_new_thread_box', 'tools_reply_box');
        }

        $this->profiler->logMem('Controller Chan $this', $this);
        $this->profiler->log('Controller Chan::latest End');

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }

    public function radix_thread($num = 0)
    {
        return $this->thread($num);
    }

    public function radix_last50($num = 0)
    {
        return new RedirectResponse($this->uri->create($this->radix->shortname.'/last/50/'.$num));
    }

    public function radix_last($limit = 0, $num = 0)
    {
        if (!ctype_digit((string) $limit) || $limit < 1) {
            return $this->action_404();
        }

        return $this->thread($num, ['type' => 'last_x', 'last_limit' => $limit]);
    }

    protected function thread($num = 0, $options = [])
    {
        $this->profiler->log('Controller Chan::thread Start');
        $num = str_replace('S', '', $num);

        try {
            $board = Board::forge($this->getContext())
                ->getThread($num)
                ->setRadix($this->radix)
                ->setOptions($options);

            // get the current status of the thread
            $thread_status = $board->getThreadStatus();
            $last_modified = $thread_status['last_modified'];

            $this->setLastModified($last_modified);

            if (!$this->response->isNotModified($this->request)) {
                $this->response = new StreamedResponse();
                $this->setLastModified($last_modified);

                // execute in case there's more exceptions to handle
                $thread = $board->getComments();

                // get the latest doc_id and latest timestamp for realtime stuff
                $latest_doc_id = $board->getHighest('doc_id')->comment->doc_id;
                $latest_timestamp = $board->getHighest('timestamp')->comment->timestamp;

                $this->builder->getProps()->addTitle(_i('Thread').' #'.$num);
                $this->param_manager->setParams([
                    'thread_id' => $num,
                    'is_thread' => true,
                    'disable_image_upload' => $thread_status['disable_image_upload'],
                    'thread_dead' => $thread_status['dead'],
                    'latest_doc_id' => $latest_doc_id,
                    'latest_timestamp' => $latest_timestamp,
                    'thread_op_data' => $thread[$num]['op']
                ]);

                $this->builder->createPartial('body', 'board')
                    ->getParamManager()
                    ->setParams([
                        'board' => $board->getComments(),
                    ]);

                $backend_vars = $this->param_manager->getParam('backend_vars');
                $backend_vars['thread_id'] = $num;
                $backend_vars['latest_timestamp'] = $latest_timestamp;
                $backend_vars['latest_doc_id'] = $latest_doc_id;
                $backend_vars['board_shortname'] = $this->radix->shortname;

                if (isset($options['last_limit']) && $options['last_limit']) {
                    $this->param_manager->setParam('controller_method', 'last/'.$options['last_limit']);
                    $backend_vars['last_limit'] = $options['last_limit'];
                }

                $this->param_manager->setParam('backend_vars', $backend_vars);

                if (!$thread_status['closed']) {
                    $this->builder->createPartial('tools_reply_box', 'tools_reply_box');
                }

                $this->profiler->logMem('Controller Chan $this', $this);
                $this->profiler->log('Controller Chan::thread End');

                $this->response->setCallback(function() {
                    $this->builder->stream();
                });
            }
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            $this->profiler->log('Controller Chan::thread End Prematurely');
            return $this->error($e->getMessage(), 404);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            $this->profiler->log('Controller Chan::thread End Prematurely');
            return $this->error($e->getMessage());
        }

        return $this->response;
    }

    public function radix_gallery($page = 1)
    {
        $this->response = new StreamedResponse();

        try {
            $board = Board::forge($this->getContext())
                ->getThreads()
                ->setRadix($this->radix)
                ->setPage($page)
                ->setOptions('per_page', 100);

            $comments = $board->getComments();

            $this->builder->createPartial('body', 'gallery')
                ->getParamManager()
                ->setParams([
                    'board' => $board->getComments()
                ]);

            $this->param_manager->setParams([
                'pagination' => [
                    'base_url' => $this->uri->create([$this->radix->shortname, 'gallery']),
                    'current_page' => $page,
                    'total' => $board->getPages()
                ]
            ]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->error($e->getMessage());
        }

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }

    public function radix_post($num = 0)
    {
        try {
            if ($this->getPost('post') || !Board::isValidPostNumber($num)) {
                // obtain post number and unset search string
                preg_match('/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/', $this->getPost('post') ? : $num, $post);
                unset($post[0]);

                return new RedirectResponse($this->uri->create([$this->radix->shortname, 'post', implode('_', $post)]));
            }

            $comment = Board::forge($this->getContext())
                ->getPost()
                ->setOptions('num', $num)
                ->setRadix($this->radix)
                ->getComment();

            $redirect =  $this->uri->create($this->radix->shortname.'/thread/'.$comment->comment->thread_num.'/');

            if (!$comment->comment->op) {
                $redirect .= '#'.$comment->comment->num.($comment->comment->subnum ? '_'.$comment->comment->subnum :'');
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', $redirect);
            $this->builder->getProps()->addTitle(_i('Redirecting'));
        } catch (\Foolz\FoolFuuka\Model\BoardMalformedInputException $e) {
            return $this->error(_i('The post number you submitted is invalid.'));
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            return $this->error(_i('The post you are looking for does not exist.'));
        }

        return $this->response->setContent($this->builder->build());
    }

    public function action_reports()
    {
        if (!$this->getAuth()->hasAccess('comment.reports')) {
            return $this->action_404();
        }

        $this->response = new StreamedResponse();

        $this->param_manager->setParam('section_title', _i('Reports'));

        /** @var Report[] $reports */
        $reports = $this->getContext()->getService('foolfuuka.report_collection')->getAll();

        $results = [];
        foreach ($reports as $report) {
            if (($result = $report->getComment()) !== null) {
                $results[0]['posts'][] = $result;
            }
        }

        $this->builder->createPartial('body', 'board')
            ->getParamManager()
            ->setParam('board', $results);
        $this->param_manager->setParams([
            'modifiers' => [
                'post_show_board_name' => true,
                'post_show_view_button' => true
            ]
        ]);

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }

    /**
     * Display all of the posts that contain the MEDIA HASH provided.
     * As of 2012-05-17, fetching of posts with same media hash is done via search system.
     * Due to backwards compatibility, this function will still be used for non-urlsafe and urlsafe hashes.
     */
    public function radix_image()
    {
        // support non-urlsafe hash
        $uri = array_filter(array_slice($this->uri->segments(), 5));

        $imploded_uri = rawurldecode(implode('/', $uri));
        if (mb_strlen($imploded_uri, 'utf-8') < 22) {
            return $this->error(_i('Your image hash is malformed.'));
        }

        // obtain actual media hash (non-urlsafe)
        $hash = mb_substr($imploded_uri, 0, 22, 'utf-8');
        if (strpos($hash, '/') !== false || strpos($hash, '+') !== false) {
            $hash = Media::urlsafe_b64encode(Media::urlsafe_b64decode($hash));
        }

        // Obtain the PAGE from URI.
        $page = 1;
        if (mb_strlen($imploded_uri, 'utf-8') > 28) {
            $page = mb_substr($imploded_uri, 28, null, 'utf-8');
        }

        // Fetch the POSTS with same media hash and generate the IMAGEPOSTS.
        $page = intval($page);
        return new RedirectResponse($this->uri->create([
            $this->radix->shortname, 'search', 'image', $hash, 'order', 'desc', 'page', $page]), 301);
    }

    public function radix_full_image($filename = null)
    {
        if ($filename === null) {
            return $this->action_404();
        }

        try {
            $bulk = $this->media_factory->getByFilename($this->radix, $filename);
        } catch (\Foolz\FoolFuuka\Model\MediaException $e) {
            return $this->action_404(_i('The image was never in our databases.'));
        }

        $media = new Media($this->getContext(), $bulk);
        if ($media->getMediaLink($this->getRequest())) {
            return new RedirectResponse($media->getMediaLink($this->getRequest()), 303);
        }

        return new RedirectResponse(
            $this->uri->create([$this->radix->shortname, 'search', 'image', rawurlencode(substr($media->media_hash, 0,
                -2))]));
    }

    public function radix_redirect($filename = null)
    {
        $redirect  = $this->uri->create([$this->radix->shortname]).$filename;

        if ($this->radix->archive) {
            $redirect  = ($this->radix->getValue('images_url') ? : '//images.4chan.org/'.$this->radix->shortname.'/src/').$filename;
        }

        $this->builder->createLayout('redirect')
            ->getParamManager()
            ->setParam('url', $redirect);
        $this->builder->getProps()->addTitle(_i('Redirecting'));

        return $this->response->setContent($this->builder->build());
    }

    public function radix_advanced_search()
    {
        return $this->action_advanced_search();
    }

    public function action_advanced_search()
    {
        $this->builder->createPartial('body', 'advanced_search')
            ->getParamManager()
            ->setParam('search_structure', Search::structure());
        $this->builder->getParamManager()->setParam('section_title', _i('Advanced search'));

        if ($this->radix !== null) {
            $this->builder->getPartial('body')
                ->getParamManager()
                ->setParam('search', ['board' => [$this->radix->shortname]]);
        }

        return $this->response->setContent($this->builder->build());
    }

    public function action_search()
    {
        return call_user_func_array([$this, 'radix_search'], func_get_args());
    }

    public function radix_search()
    {
        if ($this->getPost('submit_search_global')) {
            $this->radix = null;
        }

        $text = $this->getPost('text');

        if ($this->radix !== null && $this->getPost('submit_post')) {
            return $this->radix_post(str_replace(',', '_', $text));
        }

        $this->response = new StreamedResponse();

        // Check all allowed search modifiers and apply only these
        $modifiers = [
            'boards', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode', 'uid', 'country',
            'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end', 'results', 'order', 'page'
        ];

        if ($this->getAuth()->hasAccess('comment.see_ip')) {;
            $modifiers[] = 'poster_ip';
            $modifiers[] = 'deletion_mode';
        }

        // GET -> URL Redirection to provide URL presentable for sharing links.
        if ($this->getPost()) {
            if ($this->radix !== null) {
                $redirect_url = [$this->radix->shortname, 'search'];
            } else {
                $redirect_url = ['_', 'search'];
            }

            foreach ($modifiers as $modifier) {
                if ($this->getPost($modifier)) {
                    if ($modifier === 'boards') {
                        if ($this->getPost('submit_search_global')) {
                            // we don't need to do anything here
                        } elseif (count($this->getPost($modifier)) == 1) {
                            $boards = $this->getPost($modifier);
                            $redirect_url[0] = $boards[0];
                        } elseif (count($this->getPost($modifier)) > 1) {
                            $redirect_url[0] = '_';

                            // avoid setting this if we're just searching on all the boards
                            $sphinx_boards = [];
                            foreach ($this->radix_coll->getAll() as $k => $b) {
                                if ($b->sphinx) {
                                    $sphinx_boards[] = $b;
                                }
                            }

                            if (count($sphinx_boards) !== count($this->getPost($modifier))) {
                                array_push($redirect_url, $modifier);
                                array_push($redirect_url, rawurlencode(implode('.', $this->getPost($modifier))));
                            }
                        }
                    } elseif (trim($this->getPost($modifier)) !== '') {
                        array_push($redirect_url, $modifier);

                        if ($modifier === 'image') {
                            array_push($redirect_url,
                                rawurlencode(Media::urlsafe_b64encode(Media::urlsafe_b64decode($this->getPost($modifier))))
                            );
                        } else {
                            array_push($redirect_url, rawurlencode($this->getPost($modifier)));
                        }
                    }
                }
            }

            return new RedirectResponse($this->uri->create($redirect_url), 303);
        }

        $search = $this->uri->uri_to_assoc($this->request->getPathInfo(), 1, $modifiers);

        $this->param_manager->setParam('search', $search);

        // latest searches system
        if (!is_array($cookie_array = @json_decode($this->getCookie('search_latest_5'), true))) {
            $cookie_array = [];
        }

        // sanitize
        foreach($cookie_array as $item) {
            // all subitems must be array, all must have 'board'
            if (!is_array($item) || !isset($item['board'])) {
                $cookie_array = [];
                break;
            }
        }

        $search_opts = array_filter($search);

        $search_opts['board'] = $this->radix !== null ? $this->radix->shortname : false;
        unset($search_opts['page']);

        // if it's already in the latest searches, remove the previous entry
        foreach($cookie_array as $key => $item) {
            if ($item === $search_opts) {
                unset($cookie_array[$key]);
                break;
            }
        }

        // we don't want more than 5 entries for latest searches
        if (count($cookie_array) > 4) {
            array_pop($cookie_array);
        }

        array_unshift($cookie_array, $search_opts);
        $this->builder->getPartial('tools_search')
            ->getParamManager()
            ->setParam('latest_searches', $cookie_array);

        $this->response->headers->setCookie(
            new Cookie($this->getContext(), 'search_latest_5', json_encode($cookie_array), 60 * 60 * 24 * 30)
        );

        foreach ($search as $key => $value) {
            if ($value !== null) {
                $search[$key] = trim(rawurldecode($value));
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
                return $this->error(_i('The poster IP you inserted is not a valid IP address.'));
            }

            $search['poster_ip'] = Inet::ptod($search['poster_ip']);
        }

        try {
            $board = Search::forge($this->getContext())
                ->getSearch($search)
                ->setRadix($this->radix)
                ->setPage($search['page'] ? $search['page'] : 1);
            $board->getComments();
        } catch (\Foolz\FoolFuuka\Model\SearchException $e) {
            return $this->error($e->getMessage());
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->error($e->getMessage());
        }

        // Generate the $title with all search modifiers enabled.
        $title = [];

        if ($search['text'])
            array_push($title,
                sprintf(_i('that contain &lsquo;%s&rsquo;'),
                    e($search['text'])));
        if ($search['subject'])
            array_push($title,
                sprintf(_i('with the subject &lsquo;%s&rsquo;'),
                    e($search['subject'])));
        if ($search['username'])
            array_push($title,
                sprintf(_i('with the username &lsquo;%s&rsquo;'),
                    e($search['username'])));
        if ($search['tripcode'])
            array_push($title,
                sprintf(_i('with the tripcode &lsquo;%s&rsquo;'),
                    e($search['tripcode'])));
        if ($search['uid'])
            array_push($title,
                sprintf(_i('with the unique id &lsquo;%s&rsquo;'),
                    e($search['uid'])));
        if ($search['email'])
            array_push($title,
                sprintf(_i('with the email &lsquo;%s&rsquo;'),
                    e($search['email'])));
        if ($search['filename'])
            array_push($title,
                sprintf(_i('with the filename &lsquo;%s&rsquo;'),
                    e($search['filename'])));
        if ($search['image']) {
            array_push($title,
                sprintf(_i('with the image hash &lsquo;%s&rsquo;'),
                    e($search['image'])));
        }
        if ($search['country'])
            array_push($title,
                sprintf(_i('in &lsquo;%s&rsquo;'),
                    e($search['country'])));
        if ($search['deleted'] == 'deleted')
            array_push($title, _i('that have been deleted'));
        if ($search['deleted'] == 'not-deleted')
            array_push($title, _i('that has not been deleted'));
        if ($search['ghost'] == 'only')
            array_push($title, _i('that are by ghosts'));
        if ($search['ghost'] == 'none')
            array_push($title, _i('that are not by ghosts'));
        if ($search['type'] == 'sticky')
            array_push($title, _i('that were stickied'));
        if ($search['type'] == 'op')
            array_push($title, _i('that are only OP posts'));
        if ($search['type'] == 'posts')
            array_push($title, _i('that are only non-OP posts'));
        if ($search['filter'] == 'image')
            array_push($title, _i('that do not contain images'));
        if ($search['filter'] == 'text')
            array_push($title, _i('that only contain images'));
        if ($search['capcode'] == 'user')
            array_push($title, _i('that were made by users'));
        if ($search['capcode'] == 'mod')
            array_push($title, _i('that were made by mods'));
        if ($search['capcode'] == 'admin')
            array_push($title, _i('that were made by admins'));
        if ($search['start'])
            array_push($title, sprintf(_i('posts after %s'), e($search['start'])));
        if ($search['end'])
            array_push($title, sprintf(_i('posts before %s'), e($search['end'])));
        if ($search['order'] == 'asc')
            array_push($title, _i('in ascending order'));

        if (!empty($title)) {
            $title = sprintf(_i('Searching for posts %s.'),
                implode(' ' . _i('and') . ' ', $title));
        } else {
            $title = _i('Displaying all posts with no filters applied.');
        }

        if ($this->radix) {
            $this->builder->getProps()->addTitle($title);
        } else {
            $this->builder->getProps()->addTitle('Global Search &raquo; '.$title);
        }

        if ($board->getTotalResults() > 5000) {
            $search_title = sprintf(_i('%s <small>Returning only first %d of %d results found.</small>',
                $title, $this->preferences->get('foolfuuka.sphinx.max_matches', 5000), $board->getTotalResults()));
        } else {
            $search_title = sprintf(_i('%s <small>%d results found.</small>', $title, $board->getTotalResults()));
        }

        $this->param_manager->setParam('section_title', $search_title);
        $main_partial = $this->builder->createPartial('body', 'board');
        $main_partial->getParamManager()->setParam('board', $board->getComments());

        $pagination = $search;
        unset($pagination['page']);
        $pagination_arr = [];
        $pagination_arr[] = $this->radix !== null ?$this->radix->shortname : '_';
        $pagination_arr[] = 'search';
        foreach ($pagination as $key => $item) {
            if ($item || $item === 0) {
                $pagination_arr[] = rawurlencode($key);
                if (is_array($item)) {
                    $item = implode('.', $item);
                }

                if ($key == 'poster_ip') {
                    $item = Inet::dtop($item);
                }

                $pagination_arr[] = rawurlencode($item);
            }
        }

        $pagination_arr[] = 'page';
        $this->param_manager->setParam('pagination', [
            'base_url' => $this->uri->create($pagination_arr),
            'current_page' => $search['page'] ? : 1,
            'total' => ceil($board->getCount()/25),
        ]);

        $this->param_manager->setParam('modifiers', [
            'post_show_board_name' => $this->radix === null,
            'post_show_view_button' => true
        ]);

        $this->profiler->logMem('Controller Chan $this', $this);
        $this->profiler->log('Controller Chan::search End');

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }

    public function action_appeal_ban()
    {
        return call_user_func_array([$this, 'radix_appeal_ban'], func_get_args());
    }

    public function radix_appeal_ban()
    {
        try {
            /** @var BanFactory $ban_factory */
            $ban_factory = $this->getContext()->getService('foolfuuka.ban_factory');
            $bans = $ban_factory->getByIp(Inet::ptod($this->getRequest()->getClientIp()));
        } catch (\Foolz\FoolFuuka\Model\BanException $e) {
            return $this->error(_i('It doesn\'t look like you\'re banned.'));
        }

        // check for a global ban
        if (isset($bans[0])) {
            $title = _i('Appealing to a global ban.');
            $ban = $bans[0];
        } elseif (isset($bans[$this->radix->id])) {
            $title = _i('Appealing to a ban on %s', '/'.$this->radix->shortname.'/');
            $ban = $bans[$this->radix->id];
        } else {
            return $this->error(_i('It doesn\'t look like you\'re banned on this board.'));
        }

        if ($ban->appeal_status === Ban::APPEAL_PENDING) {
            return $this->message('success', _i('Your appeal is pending administrator review. Check again later.'));
        }

        if ($ban->appeal_status === Ban::APPEAL_REJECTED) {
            return $this->message('error', _i('Your appeal has been rejected.'));
        }

        if ($this->getPost('appeal')) {
            if (!$this->checkCsrfToken()) {
                return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
            } else {
                $validator = new Validator();
                $validator
                    ->add('appeal', _i('Appeal'),
                        [new Trim(), new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 4096])])
                    ->validate($this->getPost());

                if (!$validator->getViolations()->count()) {
                    $ban->appeal($validator->getFinalValues()['appeal']);
                    return $this->message('success', _i('Your appeal has been submitted!'));
                }
            }
        }

        $this->builder->createPartial('body', 'appeal')
            ->getParamManager()->setParam('title', $title);

        return $this->response->setContent($this->builder->build());
    }

    public function radix_submit()
    {
        // adapter
        if (!$this->getPost()) {
            return $this->error(_i('You aren\'t sending the required fields for creating a new message.'));
        }

        if (!$this->checkCsrfToken()) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->response->setData(['error' => _i('The security token wasn\'t found. Try resubmitting.')]);
            }

            return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
        }

        // Determine if the invalid post fields are populated by bots.
        if (isset($post['name']) && mb_strlen($post['name'], 'utf-8') > 0) {
            return $this->error();
        }

        if (isset($post['reply']) && mb_strlen($post['reply'], 'utf-8') > 0) {
            return $this->error();
        }

        if (isset($post['email']) && mb_strlen($post['email'], 'utf-8') > 0) {
            return $this->error();
        }

        $data = [];

        $post = $this->getPost();

        if (isset($post['reply_numero'])) {
            $data['thread_num'] = $post['reply_numero'];
        }

        if (isset($post['reply_bokunonome'])) {
            $data['name'] = $post['reply_bokunonome'];
            $this->response->headers->setCookie(new Cookie($this->getContext(), 'reply_name', $data['name'], 60*60*24*30));
        }

        if (isset($post['reply_elitterae'])) {
            $data['email'] = $post['reply_elitterae'];
            $this->response->headers->setCookie(new Cookie($this->getContext(), 'reply_email', $data['email'], 60*60*24*30));
        }

        if (isset($post['reply_talkingde'])) {
            $data['title'] = $post['reply_talkingde'];
        }

        if (isset($post['reply_chennodiscursus'])) {
            $data['comment'] = $post['reply_chennodiscursus'];
        }

        if (isset($post['reply_nymphassword'])) {
            // get the password needed for the reply field if it's not set yet
            if (!$post['reply_nymphassword'] || strlen($post['reply_nymphassword']) < 3) {
                $post['reply_nymphassword'] = Util::randomString(7);
            }

            $data['delpass'] = $post['reply_nymphassword'];
            $this->response->headers->setCookie(new Cookie($this->getContext(), 'reply_password', $data['delpass'], 60*60*24*30));
        }

        if (isset($post['reply_gattai_spoilered']) || isset($post['reply_spoiler'])) {
            $data['spoiler'] = true;
        }

        if (isset($post['reply_postas'])) {
            $data['capcode'] = $post['reply_postas'];
        }

        if (isset($post['reply_last_limit'])) {
            $data['last_limit'] = $post['reply_last_limit'];
        }

        if (isset($post['recaptcha_challenge_field']) && isset($post['recaptcha_response_field'])) {
            $data['recaptcha_challenge'] = $post['recaptcha_challenge_field'];
            $data['recaptcha_response'] = $post['recaptcha_response_field'];
        }

        $media = null;

        if ($this->getRequest()->files->count()) {
            try {
                $media = $this->media_factory->forgeFromUpload($this->getRequest(), $this->radix);
                $media->media->spoiler = isset($data['spoiler']) && $data['spoiler'];
            } catch (\Foolz\FoolFuuka\Model\MediaUploadNoFileException $e) {
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return $this->response->setData(['error' => $e->getMessage()]);
                } else {
                    return $this->error($e->getMessage());
                }
            } catch (\Foolz\FoolFuuka\Model\MediaUploadException $e) {
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return $this->response->setData(['error' => $e->getMessage()]);
                } else {
                    return $this->error($e->getMessage());
                }
            }
        }

        return $this->submit($data, $media);
    }

    public function submit($data, $media)
    {
        // some beginners' validation, while through validation will happen in the Comment model
        $validator = new Validator();
        $validator
            ->add('thread_num', _i('Thread Number'), [new Assert\NotBlank()])
            ->add('name', _i('Name'), [new Assert\Length(['max' => 64])])
            ->add('email', _i('Email'), [new Assert\Length(['max' => 64])])
            ->add('title', _i('Title'), [new Assert\Length(['max' => 64])])
            ->add('delpass', _i('Deletion pass'), [new Assert\Length(['min' => 3, 'max' => 32])]);

        // no empty posts without images
        if ($media === null) {
            $validator->add('comment', _i('Comment'), [new Assert\NotBlank(), new Assert\Length(['min' => 3])]);
        }

        // this is for redirecting, not for the database
        $limit = false;
        if (isset($data['last_limit'])) {
            $limit = intval($data['last_limit']);
            unset($data['last_limit']);
        }

        $validator->validate($data);
        if (!$validator->getViolations()->count()) {
            try {
                $data['poster_ip'] = Inet::ptod($this->getRequest()->getClientIp());
                $bulk = new CommentBulk();
                $bulk->import($data, $this->radix);
                $comment = new CommentInsert($this->getContext(), $bulk);
                $comment->insert($media, $data);
            } catch (\Foolz\FoolFuuka\Model\CommentSendingRequestCaptchaException $e) {
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return $this->response->setData(['captcha' => true]);
                } else {
                    return $this->error(_i('Your message looked like spam. Make sure you have JavaScript enabled to display the reCAPTCHA to submit the comment.'));
                }
            } catch (\Foolz\FoolFuuka\Model\CommentSendingException $e) {
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return $this->response->setData(['error' => $e->getMessage()]);
                } else {
                    return $this->error($e->getMessage());
                }
            }
        } else {
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->response->setData(['error' => $validator->getViolations()->getText()]);
            } else {
                return $this->error($validator->getViolations()->getHtml());
            }
        }

        if ($this->request->isXmlHttpRequest()) {
            $latest_doc_id = $this->getPost('latest_doc_id');

            if ($latest_doc_id && ctype_digit((string) $latest_doc_id)) {
                try {
                    $board = Board::forge($this->getContext())
                        ->getThread($comment->comment->thread_num)
                        ->setRadix($this->radix)
                        ->setOptions([
                            'type' => 'from_doc_id',
                            'latest_doc_id' => $latest_doc_id
                        ]);

                    $comments = $board->getComments();
                } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
                    return $this->error(_i('Thread not found.'));
                } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                    return $this->error(_i('Unknown error.'));
                }

                $comment_obj = new Comment($this->getContext());
                $comment_obj->setControllerMethod($limit ? 'last/'.$limit : 'thread');
                $media_obj = new Media($this->getContext());
                $m = null;
                foreach($board->getCommentsUnsorted() as $bulk) {
                    $comment_obj->setBulk($bulk, $this->radix);
                    if ($bulk->media) {
                        $media_obj->setBulk($bulk, $this->radix);
                        $m = $media_obj;
                    } else {
                        $m = null;
                    }

                    if ($this->builder) {
                        $this->param_manager->setParam('controller_method', $limit ? 'last/'.$limit : 'thread');
                        $partial = $this->builder->createPartial('board_comment', 'board_comment');
                        $partial->getParamManager()
                            ->setParam('p', $comment_obj)
                            ->setParam('p_media', $m);

                        $bulk->comment->formatted = $partial->build();
                        $partial->clearBuilt();
                    }
                }

                $this->response->setData(['success' => _i('Message sent.')] + $comments);
            } else {

                if ($this->builder) {
                    $this->param_manager->setParam('controller_method', $limit ? 'last/'.$limit : 'thread');
                    $partial = $this->builder->createPartial('board_comment', 'board_comment');
                    $partial->getParamManager()
                        ->setParam('p', new Comment($this->getContext(), $comment->bulk))
                        ->setParam('p_media', new Media($this->getContext(), $comment->bulk));

                    $bulk->comment->formatted = $partial->build();
                    $partial->clearBuilt();
                }

                $this->response->setData([
                        'success' => _i('Message sent.'),
                        'thread_num' => $comment->comment->thread_num,
                        $comment->comment->thread_num => ['posts' => [$comment->bulk]],
                    ]);
            }
        } else {
            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', $this->uri->create([$this->radix->shortname, ! $limit ? 'thread' : 'last/'.$limit, $comment->comment->thread_num]).'#'.$comment->comment->num);
            $this->builder->getProps()->addTitle(_i('Redirecting'));

            $this->response->setContent($this->builder->build());
        }

        return $this->response;
    }
}
