<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update005 extends CI_Migration
{
	function up()
	{

		// fix the fuckup from version 0.7 caching comic entries
		$all_files = get_dir_file_info('content/comics', FALSE);
		if (is_array($all_files))
			foreach ($all_files as $key => $file)
			{
				if (strtolower(substr($file["name"], -4) == ".zip"))
				{
					// remove every zip
					unlink($file["relative_path"] . $file["name"]);
				}
			}
		// remove also all the database entries
		$this->db->query("TRUNCATE TABLE `" . $this->db->dbprefix('archives') . "`");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('archives') . "` ADD INDEX ( `size` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('archives') . "` ADD INDEX ( `chapter_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('archives') . "` CHANGE `edited` `updated` DATETIME NOT NULL");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `comic_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `team_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `joint_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `chapter` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `subchapter` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `volume` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `language` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `stub` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `uniqid` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('chapters') . "` ADD INDEX ( `updated` )");


		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('comics') . "` ADD INDEX ( `stub` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('comics') . "` ADD INDEX ( `uniqid` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('comics') . "` ADD INDEX ( `hidden` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('comics') . "` ADD INDEX ( `created` )");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('joints') . "` ADD INDEX ( `joint_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('joints') . "` ADD INDEX ( `team_id` )");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('licenses') . "` ADD INDEX ( `comic_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('licenses') . "` ADD INDEX ( `nation` )");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `team_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `user_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `is_leader` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `accepted` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `requested` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `applied` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `created` )");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` CHANGE `edited` `updated` DATETIME NOT NULL");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('memberships') . "` ADD INDEX ( `updated` )");


		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('profiles') . "` ADD INDEX ( `user_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('profiles') . "` ADD INDEX ( `group_id` )");

		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('teams') . "` ADD INDEX ( `name` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('teams') . "` ADD INDEX ( `stub` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('teams') . "` ADD INDEX ( `created` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('teams') . "` ADD INDEX ( `updated` )");


		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('users') . "` ADD INDEX ( `username` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('users') . "` ADD INDEX ( `created` )");


		//create a new field in chapters for the custom chapter titles
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('comics') . "` ADD `customchapter` VARCHAR( 32 ) NOT NULL AFTER `thumbnail`");
	}


}