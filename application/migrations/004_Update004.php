<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update004 extends CI_Migration {

	function up() {

		$this->db->query(
				"INSERT INTO `" . $this->db->dbprefix('preferences') . "` (`name`, `value`, `group`) VALUES
						('fs_reg_email_disabled', 0, 0),
						('fs_dl_archive_max', 350, 0),
						('fs_dl_enabled', 0, 0),
						('fs_cron_autoupgrade_version', 0, 0);"
		);

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('archives') . "` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `chapter_id` int(11) NOT NULL,
						  `filename` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
						  `size` int(11) NOT NULL,
						  `created` datetime NOT NULL,
						  `edited` datetime NOT NULL,
						  `lastdownload` datetime NOT NULL,
						  PRIMARY KEY (`id`)
						) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
						");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `created` )");
	}

}