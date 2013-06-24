<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class Error extends \Foolz\Theme\View
{
	public function toString()
	{
		$error = $this->getParamManager()->getParam('error');

		?>
	    <div class="alert" style="margin:15%;">
	        <h4 class="alert-heading"><?= _i('Error!') ?></h4>
			<?= $error ?>
	    </div>
		<?php
	}
}