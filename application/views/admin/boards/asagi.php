<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo form_open();
if(exec_enabled() && java_enabled())
{
	echo form_submit(array('name' => 'run', 'value' => __('Run'), 'class' => 'btn btn-success')) . ' ';
	echo form_submit(array('name' => 'kill', 'value' => __('Kill'), 'class' => 'btn btn-danger')) . ' ';
if(!get_setting('fs_asagi_autorun_enabled'))
	echo form_submit(array('name' => 'enable_autorun', 'value' => __('Enable autorun'), 'class' => 'btn btn-primary')) . ' ';
if(get_setting('fs_asagi_autorun_enabled'))
	echo form_submit(array('name' => 'disable_autorun', 'value' => __('Disable autorun'), 'class' => 'btn btn-warning')) . ' ';
}
echo form_submit(array('name' => 'upgrade', 'value' => __('Upgrade/Reinstall'), 'class' => 'btn')) . ' ';
echo form_submit(array('name' => 'remove', 'value' => __('Remove'), 'class' => 'btn')) . ' ';
echo form_close();
?>

<br/>
<h3><?php echo __("Running Asagi manually"); ?></h3>
<?php echo __("You can run it on your server by typing in your command line:"); ?> 
<pre>java -jar <?php echo FCPATH . 'content/asagi/asagi.jar --settings-exec php ' . FCPATH . 'index.php cli asagi_get_settings';  ?></pre>
<?php echo __("But this would run it only once and would stop once you close the terminal. Use it to test if it's working fine."); ?>

<br/>
<br/>
<?php echo __("If you're on Linux, to have Asagi running as daemon (without your terminal needing to be open), you can use GNU Screen that creates a virtual terminal that will stay alive even if you close the window. 
You can usually install this on Debian/Ubuntu by typing <code>apt-get install screen</code> in your terminal. 
You can then type the following string in your terminal to have Asagi running under GNU Screen:");
?>
<br/>
<pre>screen -t "Asagi" -S "asagi" sh -c "cd <?php echo FCPATH . 'content/asagi/' ?>; while true; do java -jar asagi.jar <?php echo '--settings-exec php ' . FCPATH . 'index.php cli asagi_get_settings'; ?>; done"</pre>
<?php echo __('Then press CTRL+A and then CTRL+D to detach the screen. You can go back to it by writing:') ?> <code>screen -r asagi</code>
<br/>
<br/>
<h3><?php echo __("License") ?></h3>
<?php echo __('Asagi is released under the GNU General Public License, version 2, or later.') ?> <a href="https://raw.github.com/eksopl/asagi/master/LICENSE"><?php echo __('Click here to read the full license.') ?></a>