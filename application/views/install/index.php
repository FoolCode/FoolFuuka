<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo __("This is all the needed information to install and run " . FOOL_NAME . ". Don't worry: you can change this information later in case you need it changed.");
?>
<br/><br/>

<?php
echo form_open();
echo $table;
echo form_close();
?>