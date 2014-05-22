<?php

namespace Foolz\Foolfuuka\View;

use Foolz\Foolfuuka\Model\Radix;
use Foolz\Foolfuuka\Model\RadixCollection;
use Foolz\Foolfuuka\Model\ReportCollection;

class View extends \Foolz\Foolframe\View\View
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