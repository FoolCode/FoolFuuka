<?php

namespace Foolz\Foolfuuka\Controller\Admin;

use Foolz\Foolfuuka\Model\BanFactory;
use Foolz\Foolfuuka\Model\RadixCollection;
use Foolz\Foolfuuka\Model\ReportCollection;
use Foolz\Inet\Inet;
use Foolz\Theme\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Moderation extends \Foolz\Foolframe\Controller\Admin
{
    /**
     * @var RadixCollection
     */
    protected $radic_coll;

    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var BanFactory
     */
    protected $ban_factory;

    /**
     * Check that the user is an admin or a moderator and
     */
    public function before()
    {
        parent::before();

        $this->radix_coll = $this->getContext()->getService('foolfuuka.radix_collection');
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');
        $this->ban_factory = $this->getContext()->getService('foolfuuka.ban_factory');

        $this->param_manager->setParam('controller_title', _i('Moderation'));
    }

    public function security()
    {
        return $this->getAuth()->hasAccess('comment.reports');
    }

    /**
     * Selects the theme. Can be overridden so other controllers can use their own admin components
     *
     * @param Loader $theme_instance
     */
    public function setupTheme(Loader $theme_instance)
    {
        // we need to load more themes
        $theme_instance->addDir(VENDPATH.'foolz/foolfuuka/public/themes-admin');
        $this->theme = $theme_instance->get('foolz/foolfuuka-theme-admin');
    }

    /**
     * Lists the post moderation
     *
     * @return  Response
     */
    public function action_reports()
    {
        $this->param_manager->setParam('method_title', [_i('Manage'), _i('Reports')]);

        // this has already been forged in the foolfuuka bootstrap
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');

        $theme_name = 'foolz/foolfuuka-theme-foolfuuka';
        $this->theme = $theme = $theme_instance->get('foolz/foolfuuka-theme-foolfuuka');

        $reports = $this->report_coll->getAll();

        foreach ($reports as $key => $report) {
            foreach ($reports as $k => $r) {
                if ($key < $k && $report->doc_id === $r->doc_id && $report->board_id === $r->board_id) {
                    unset($reports[$k]);
                }
            }
        }

        // KEEP THIS IN SYNC WITH THE ONE IN THE CHAN CONTROLLER
        $backend_vars = [
            'context' => $this->getContext(),
            'request' => $this->getRequest(),
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
                'banned_image' => $this->uri->base().$this->theme->getAssetManager()->getAssetLink('images/banned-image.png'),
                'banned_image_width' => 150,
                'banned_image_height' => 150,
                'missing_image' => $this->uri->base().$this->theme->getAssetManager()->getAssetLink('images/missing-image.jpg'),
                'missing_image_width' => 150,
                'missing_image_height' => 150,
            ],
            'gettext' => [
                'submit_state' => _i('Submitting'),
                'thread_is_real_time' => _i('This thread is being displayed in real time.'),
                'update_now' => _i('Update now')
            ]
        ];

        $this->builder->createPartial('body', 'moderation/reports')
            ->getParamManager()->setParams([
                'backend_vars' => $backend_vars,
                'theme' => $theme,
                'reports' => $reports
            ]);
        return new Response($this->builder->build());
    }

    public function action_bans($page = 1)
    {
        $this->param_manager->setParam('method_title', [_i('Manage'), _i('Bans')]);

        if ($page < 1 || !ctype_digit((string) $page)) {
            $page = 1;
        }

        $bans = $this->ban_factory->getPagedBy('start', 'desc', $page);

        $this->builder->createPartial('body', 'moderation/bans')
            ->getParamManager()->setParams([
                'bans' => $bans,
                'page' => $page,
                'page_url' => $this->uri->create('admin/moderation/bans')
            ]);

        return new Response($this->builder->build());
    }

    public function action_appeals($page = 1)
    {
        $this->param_manager->setParam('method_title', [_i('Manage'), _i('Bans'), _i('Appeals')]);

        if ($page < 1 || !ctype_digit((string) $page)) {
            $page = 1;
        }

        $bans = $this->ban_factory->getAppealsPagedBy('start', 'desc', $page);

        $this->builder->createPartial('body', 'moderation/bans')
            ->getParamManager()->setParams([
                'bans' => $bans,
                'page' => $page,
                'page_url' => $this->uri->create('admin/moderation/bans')
            ]);

        return new Response($this->builder->build());
    }

    public function action_find_ban($ip = null)
    {
        $this->param_manager->setParam('method_title', [_i('Manage'), _i('Bans')]);

        if ($this->getPost('ip')) {
            return $this->redirect('admin/moderation/find_ban/'.trim($this->getPost('ip')));
        }

        if ($ip === null) {
            throw new NotFoundHttpException;
        }

        $ip = trim($ip);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new NotFoundHttpException;
        }

        try {
            $bans = $this->ban_factory->getByIp(Inet::ptod($ip));
        } catch (\Foolz\Foolfuuka\Model\BanException $e) {
            $bans = [];
        }

        $this->builder->createPartial('body', 'moderation/bans')
            ->getParamManager()->setParams([
                'bans' => $bans,
                'page' => false,
                'page_url' => $this->uri->create('admin/moderation/bans')
            ]);

        return new Response($this->builder->build());
    }

    public function action_ban_manage($action, $id)
    {
        try {
            $ban = $this->ban_factory->getById($id);
        } catch (\Foolz\Foolfuuka\Model\BanException $e) {
            throw new NotFoundHttpException;
        }

        if ($this->getPost() && !$this->checkCsrfToken()) {
            $this->notices->set('warning', _i('The security token wasn\'t found. Try resubmitting.'));
        } elseif ($this->getPost()) {
            switch ($action) {
                case 'unban':
                    $ban->delete();
                    $this->notices->setFlash('success', _i('The poster with IP %s has been unbanned.', Inet::dtop($ban->ip)));
                    return $this->redirect('admin/moderation/bans');
                    break;

                case 'reject_appeal':
                    $ban->appealReject();
                    $this->notices->setFlash('success', _i('The appeal of the poster with IP %s has been rejected.', Inet::dtop($ban->ip)));
                    return $this->redirect('admin/moderation/bans');
                    break;

                default:
                    throw new NotFoundHttpException;
            }
        }

        switch ($action) {
            case 'unban':
                $this->_views['method_title'] = _i('Unbanning').' '. Inet::dtop($ban->ip);
                $data['alert_level'] = 'warning';
                $data['message'] = _i('Do you want to unban this user?');
                break;

            case 'reject_appeal':
                $this->_views['method_title'] = _i('Rejecting appeal for').' '. Inet::dtop($ban->ip);
                $data['alert_level'] = 'warning';
                $data['message'] = _i('Do you want to reject the appeal of this user? He won\'t be able to appeal again.');
                break;

            default:
                throw new NotFoundHttpException;
        }

        $this->builder->createPartial('body', 'confirm')
            ->getParamManager()->setParams($data);

        return new Response($this->builder->build());
    }
}
