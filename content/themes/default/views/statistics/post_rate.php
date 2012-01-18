<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$data = json_decode($data, TRUE);?>

Posts in last hour: <?php echo $data[0]['count(*)']; ?>
<br/><br/>
Posts per minute: <?php echo $data[0]['count(*)/60']; ?>