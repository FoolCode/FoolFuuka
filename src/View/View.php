<?php

namespace Foolz\FoolFuuka\View;

use Foolz\FoolFuuka\Model\Radix;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFuuka\Model\ReportCollection;

class View extends \Foolz\FoolFrame\View\View
{
    /**
     * @return Radix
     */
    public function getRadix()
    {
        return $this->getBuilderParamManager()->getParam('radix');
    }

    /**
     * @return RadixCollection
     */
    public function getRadixColl()
    {
        return $this->getBuilderParamManager()->getParam('context')->getService('foolfuuka.radix_collection');
    }

    /**
     * @return ReportCollection
     */
    public function getReportColl()
    {
        return $this->getBuilderParamManager()->getParam('context')->getService('foolfuuka.report_collection');
    }
}
