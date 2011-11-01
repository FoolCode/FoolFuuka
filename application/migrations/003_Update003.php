<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update003 extends CI_Migration {

	function up() {

		$this->db->query(
				"INSERT INTO `" . $this->db->dbprefix('preferences') . "` (`name`, `value`, `group`) VALUES
						('fs_reg_disabled', 0, 0),
						('fs_reg_recaptcha_public', '', 0),
						('fs_reg_recaptcha_secret', '', 0);"
		);
	}

}