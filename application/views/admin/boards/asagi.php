<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo form_open();
if(exec_enabled() && !java_enabled())
{
	echo form_submit(array('name' => 'run', 'value' => _('Run'), 'class' => 'btn btn-success')) . ' ';
	echo form_submit(array('name' => 'kill', 'value' => _('Kill'), 'class' => 'btn btn-danger')) . ' ';
if(!get_setting('fs_asagi_autorun_enabled'))
	echo form_submit(array('name' => 'enable_autorun', 'value' => _('Enable autorun'), 'class' => 'btn btn-primary')) . ' ';
if(get_setting('fs_asagi_autorun_enabled'))
	echo form_submit(array('name' => 'disable_autorun', 'value' => _('Disable autorun'), 'class' => 'btn btn-warning')) . ' ';
}
echo form_submit(array('name' => 'upgrade', 'value' => _('Upgrade/Reinstall'), 'class' => 'btn')) . ' ';
echo form_submit(array('name' => 'remove', 'value' => _('Remove'), 'class' => 'btn')) . ' ';
echo form_close();
?>

<br/>
<h3><?php echo _("Running Asagi manually"); ?></h3>
<?php echo _("You can run it on your server by typing in your command line:"); ?> 
<pre>java -jar <?php echo FCPATH . 'content/asagi/asagi.jar --settings-exec php ' . FCPATH . 'index.php cli asagi_get_settings';  ?></pre>
<?php echo _("But this would run it only once and would stop once you close the terminal. Use it to test if it's working fine."); ?>

<br/>
<br/>
<?php echo _("If you're on Linux, to have Asagi running as daemon (without your terminal needing to be open), you can use GNU Screen that creates a virtual terminal that will stay alive even if you close the window. 
You can usually install this on Debian/Ubuntu by typing <code>apt-get install screen</code> in your terminal. 
You can then type the following string in your terminal to have Asagi running under GNU Screen:");
?>
<br/>
<pre>screen -t "Asagi" -S "asagi" sh -c "cd <?php echo FCPATH . 'content/asagi/' ?>; while true; do java -jar asagi.jar <?php echo '--settings-exec php ' . FCPATH . 'index.php cli asagi_get_settings'; ?>; done"</pre>
<?php echo _('Then press CTRL+A and then CTRL+D to detach the screen. You can go back to it by writing:') ?> <code>screen -r asagi</code>
<br/>
<br/>
<h3><?php echo _("License") ?></h3>
<?php echo _('Asagi is released under the GNU General Public License, version 2, or later.') ?> <a href="https://raw.github.com/eksopl/asagi/master/LICENSE"><?php echo _('Click here to read the full license.') ?></a>