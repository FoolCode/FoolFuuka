<?php

namespace Foolz\Foolframe\Controller\Admin\Plugins;

use Foolz\Foolframe\Model\Validation\ActiveConstraint\Trim;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NginxCachePurge extends \Foolz\Foolframe\Controller\Admin
{
    public function before()
    {
        parent::before();

        $this->param_manager->setParam('controller_title', 'Nginx Cache Purge');
    }

    public function security()
    {
        return $this->getAuth()->hasAccess('maccess.admin');
    }

    function structure()
    {
        return [
            'open' => [
                'type' => 'open',
            ],
            'foolfuuka.plugins.nginx_cache_purge.urls' => [
                'type' => 'textarea',
                'preferences' => true,
                'label' => _i('Cache cleaning URLs'),
                'help' => _i('Insert the URLs that Nginx Cache Purge will have to contact and their eventual Basic Auth passwords. Make sure you "allow" only the IP from this server on the Nginx Cache Purge configuration block. The following is the format:') .
                '<pre style="margin-top:8px">http://0-cdn-archive.yourdomain.org/purge/:username1:yourpass
http://1-cdn-archive.yourdomain.org/purge/
http://2-cdn-archive.yourdomain.org/purge/:username2:password</pre>',
                'class' => 'span8',
                'validation' => [new Trim()]
            ],
            'separator-2' => [
                'type' => 'separator-short'
            ],
            'submit' => [
                'type' => 'submit',
                'class' => 'btn-primary',
                'value' => _i('Submit')
            ],
            'close' => [
                'type' => 'close'
            ],
        ];
    }

    function action_manage()
    {
        $this->param_manager->setParam('method_title', 'Manage');

        $data['form'] = $this->structure();

        $this->preferences->submit_auto($this->getRequest(), $data['form'], $this->getPost());

        // create a form
        $this->builder->createPartial('body', 'form_creator')
            ->getParamManager()->setParams($data);

        return new Response($this->builder->build());
    }
}
