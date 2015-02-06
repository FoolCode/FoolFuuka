<?php

namespace Foolz\FoolFuuka\Controller\Admin;

use Foolz\FoolFuuka\Model\BanFactory;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\Inet\Inet;
use Foolz\Theme\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Moderation extends \Foolz\FoolFrame\Controller\Admin
{
    /**
     * @var Audit
     */
    protected $audit;

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

        $this->audit = $this->getContext()->getService('foolfuuka.audit_factory');
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
        $theme_instance->addDir(ASSETSPATH.'themes-admin');
        $this->theme = $theme_instance->get('foolz/foolfuuka-theme-admin');
    }

    public function action_logs($page = 1)
    {
        if (!$this->getAuth()->hasAccess('maccess.admin')) {
            return $this->redirectToAdmin();
        }

        $this->param_manager->setParam('method_title', [_i('Audit'), _i('Logs')]);

        if ($page < 1 || !ctype_digit((string) $page)) {
            $page = 1;
        }

        $logs = $this->audit->getPagedBy('id', 'desc', $page);

        $this->builder->createPartial('body', 'moderation/audit_log')
            ->getParamManager()->setParams([
                'logs' => $logs,
                'page' => $page,
                'page_url' => $this->uri->create('admin/moderation/logs')
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
        } catch (\Foolz\FoolFuuka\Model\BanException $e) {
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
        } catch (\Foolz\FoolFuuka\Model\BanException $e) {
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
