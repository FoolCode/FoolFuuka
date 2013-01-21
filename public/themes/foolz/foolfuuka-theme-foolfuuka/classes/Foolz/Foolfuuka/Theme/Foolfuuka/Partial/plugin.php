<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class Plugin extends \Foolz\Theme\View
{
	public function toString()
	{
		echo $this->getParamManager()->getParam('content');
	}
}