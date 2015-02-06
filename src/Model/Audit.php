<?php

namespace Foolz\FoolFuuka\Model;

class AuditException extends \Exception {}
class AuditNotFoundException extends AuditException {}

use Foolz\FoolFrame\Model\Model;

class Audit extends Model
{
    public $id = 0;
    public $timestamp = 0;
    public $user = null;
    public $type = null;
    public $data = null;

    protected $dc;
    protected $users;

    const AUDIT_BAN_FILE = 1;
    const AUDIT_DEL_FILE = 2;
    const AUDIT_DEL_POST = 3;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->users = $this->getContext()->getService('users');
    }

    public function getTime()
    {
        return date('Y/m/d H:m:s', $this->timestamp);
    }

    public function getAction()
    {
        switch ($this->type) {
            case 1: return 'FILE_BAN';
            case 2: return 'FILE_DEL';
            case 3: return 'POST_DEL';
        }
    }

    public function getUser()
    {
        if ($this->user > 0) {
            return $this->users->getUserBy('id', $this->user)->username;
        }

        return '[POSTER]';
    }

    public function getMessage()
    {
        return $this->data;
    }
}
