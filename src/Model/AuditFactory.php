<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\Model;

class AuditFactory extends Model
{
    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
    }

        /**
     * Converts an array into a Ban object
     *
     * @param   array  $array  The array from database
     *
     * @return  \Foolz\FoolFuuka\Model\Ban
     */
    public function fromArray($array)
    {
        $new = new Audit($this->getContext());
        foreach ($array as $key => $item) {
            $new->$key = $item;
        }

        return $new;
    }

    /**
     * Takes an array of arrays to create Ban objects
     *
     * @param   array  $array  The array from database
     * @param   boolean  $merge  Use the board_id as keys or not
     *
     * @return  array  An array of \Foolz\FoolFuuka\Model\Ban with as key the board_id
     */
    public function fromArrayDeep($array)
    {
        $new = [];
        foreach ($array as $item) {
            $new[] = $this->fromArray($item);
        }

        return $new;
    }

    public function getPagedBy($order_by, $order, $page, $per_page = 30)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('audit_log'), 'l')
            ->orderBy($order_by, $order)
            ->setMaxResults($per_page)
            ->setFirstResult(($page * $per_page) - $per_page)
            ->execute()
            ->fetchAll();

        return $this->fromArrayDeep($result);
    }

    public function log($type, $data)
    {
        $this->dc->getConnection()
            ->insert($this->dc->p('audit_log'), [
                'timestamp' => time(),
                'user' => $this->getAuth()->getUser()->getId(),
                'type' => $type,
                'data' => json_encode($data),
            ]);
    }
}
