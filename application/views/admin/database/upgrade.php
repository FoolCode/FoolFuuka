<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo '<div class="table"><h3>' . _('Update database') . '</h3>';

echo _("There's a newer version of the database available. Just update it by clicking on the update button.");
echo '<br/>';
echo sprintf(_("In case you're using a very large installation of FoOlSlide, to avoid timeouts while updating the database, you can use the command line, and enter this: %s"), '<br/><b><code>' . $CLI_code . '</code></b>');

echo '<br/><br/>';
echo buttoner(array(
	'text' => _("Upgrade"),
	'href' => site_url('/admin/database/do_upgrade'),
	'plug' => _("Do you really want to upgrade your database?")
));

echo '<br/><br/></div>';