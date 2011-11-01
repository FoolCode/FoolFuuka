<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update002 extends CI_Migration {

	function up() {

		$this->db->query(
				"INSERT INTO `" . $this->db->dbprefix('preferences') . "` (`name`, `value`, `group`) VALUES
						('fs_geo_blocked', '', 0),
						('fs_cron_autoupgrade', 0, 0),
						('fs_priv_maintenance', '', 0);"
		);
	}
}