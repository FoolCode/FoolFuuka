<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Install extends CI_Migration
{
	function up()
	{

		if (!$this->db->table_exists($this->db->dbprefix('ci_sessions')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('ci_sessions') . "` (
                                          `session_id` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '0',
                                          `ip_address` varchar(16) COLLATE utf8_bin NOT NULL DEFAULT '0',
                                          `user_agent` varchar(120) COLLATE utf8_bin DEFAULT NULL,
                                          `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
                                          `user_data` text COLLATE utf8_bin NOT NULL,
                                          PRIMARY KEY (`session_id`),
                                          KEY `last_activity_idx` (`last_activity`)
                                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('preferences')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('preferences') . "` (
                                          `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
                                          `value` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
                                          `group` int(11) NOT NULL,
                                          PRIMARY KEY (`name`)
                                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);

			$this->db->query(
					"INSERT INTO `" . $this->db->dbprefix('preferences') . "` (`name`, `value`, `group`) VALUES
                                                ('fs_ads_bottom_banner', '', 0),
                                                ('fs_ads_bottom_banner_active', '', 0),
                                                ('fs_ads_bottom_banner_reload', '', 0),
                                                ('fs_ads_top_banner', '', 0),
                                                ('fs_ads_top_banner_active', '', 0),
                                                ('fs_ads_top_banner_reload', '', 0),
                                                ('fs_gen_anon_team_show', '1', 0),
                                                ('fs_gen_back_url', '', 0),
                                                ('fs_gen_default_lang', 'en', 0),
                                                ('fs_gen_default_team', 'Anonymous', 0),
                                                ('fs_gen_footer_text', 'All the manga featured in this website are property of their publishers. The translations are fanmade and meant to be a preview of material unavailable for western countries.\nDo not try to profit from this material.<br/>If you liked any of the manga you obtained here, consider buying the Japanese versions, or the local translation, where available. Thanks for your support.', 0),
                                                ('fs_gen_site_title', 'A fresh FoOlSlide', 0),
                                                ('fs_priv_version', '" . FOOLSLIDE_VERSION . "', 0),
                                                ('fs_srv_servers', '', 0),
												('fs_geo_blocked', '', 0),
												('fs_cron_autoupgrade', 0, 0),
												('fs_priv_maintenance', '', 0),
												('fs_reg_disabled', 0, 0),
												('fs_reg_recaptcha_public', '', 0),
												('fs_reg_recaptcha_secret', '', 0),
												('fs_reg_email_disabled', 0, 0),
												('fs_dl_archive_max', 350, 0),
												('fs_dl_enabled', 0, 0),
												('fs_cron_autoupgrade_version', 0, 0);"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('teams')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('teams') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
                                          `stub` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
                                          `url` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
                                          `forum` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
                                          `irc` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
                                          `twitter` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
                                          `facebook` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
                                          `facebookid` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
                                          `created` datetime NOT NULL,
                                          `lastseen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                                          `updated` datetime NOT NULL,
                                          `creator` int(11) NOT NULL,
                                          `editor` int(11) NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;"
			);

			$this->db->query(
					"INSERT INTO `" . $this->db->dbprefix('teams') . "` (`id`, `name`, `stub`, `url`, `forum`, `irc`, `twitter`, `facebook`, `facebookid`, `created`, `lastseen`, `updated`, `creator`, `editor`) VALUES
                                        (1, 'Anonymous', 'anonymous', '', '', '', '', '', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 1);"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('joints')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('joints') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `joint_id` int(11) NOT NULL,
                                          `team_id` int(11) NOT NULL,
                                          `created` datetime NOT NULL,
                                          `updated` datetime NOT NULL,
                                          `creator` int(11) NOT NULL,
                                          `editor` int(11) NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;"
			);
		}


		if (!$this->db->table_exists($this->db->dbprefix('memberships')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('memberships') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `team_id` int(11) NOT NULL,
                                          `user_id` int(11) NOT NULL,
                                          `is_leader` int(11) NOT NULL,
                                          `accepted` int(11) NOT NULL,
                                          `requested` int(11) NOT NULL,
                                          `applied` int(11) NOT NULL,
                                          `created` datetime NOT NULL,
                                          `edited` datetime NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('groups')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('groups') . "` (
                                          `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                                          `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                                          `description` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;"
			);

			$this->db->query(
					"INSERT INTO `" . $this->db->dbprefix('groups') . "` (`id`, `name`, `description`) VALUES
                                                (1, 'admin', 'Administrator'),
                                                (2, 'members', 'Users or Team member'),
                                                (3, 'mod', 'Moderator');"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('users')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('users') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `username` varchar(50) COLLATE utf8_bin NOT NULL,
                                          `password` varchar(255) COLLATE utf8_bin NOT NULL,
                                          `email` varchar(100) COLLATE utf8_bin NOT NULL,
                                          `activated` tinyint(1) NOT NULL DEFAULT '1',
                                          `banned` tinyint(1) NOT NULL DEFAULT '0',
                                          `ban_reason` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                                          `new_password_key` varchar(50) COLLATE utf8_bin DEFAULT NULL,
                                          `new_password_requested` datetime DEFAULT NULL,
                                          `new_email` varchar(100) COLLATE utf8_bin DEFAULT NULL,
                                          `new_email_key` varchar(50) COLLATE utf8_bin DEFAULT NULL,
                                          `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
                                          `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                          `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                          `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                          `updated` datetime NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('profiles')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('profiles') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `user_id` int(11) NOT NULL,
                                          `group_id` int(11) NOT NULL,
                                          `display_name` varchar(32) COLLATE utf8_bin NOT NULL,
                                          `twitter` varchar(32) COLLATE utf8_bin NOT NULL,
                                          `bio` text COLLATE utf8_bin NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('login_attempts')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('login_attempts') . "` (
                                          `id` int(11) NOT NULL AUTO_INCREMENT,
                                          `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
                                          `login` varchar(50) COLLATE utf8_bin NOT NULL,
                                          `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;"
			);
		}

		if (!$this->db->table_exists($this->db->dbprefix('user_autologin')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('user_autologin') . "` (
                                          `key_id` char(32) COLLATE utf8_bin NOT NULL,
                                          `user_id` int(11) NOT NULL DEFAULT '0',
                                          `user_agent` varchar(150) COLLATE utf8_bin NOT NULL,
                                          `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
                                          `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                          PRIMARY KEY (`key_id`,`user_id`)
                                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
			);
		}


		if (!$this->db->table_exists($this->db->dbprefix('boards')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('boards') . "` (
					  `id` int(11) NOT NULL auto_increment,
					  `shortname` varchar(32) NOT NULL,
					  `name` varchar(256) NOT NULL,
					  `url` varchar(256) NOT NULL,
					  `sphinx` int(11) NOT NULL,
					  `threads_refresh_rate` int(11) NOT NULL,
					  `threads_posts` int(11) NOT NULL,
					  `threads_media` int(11) NOT NULL,
					  `threads_thumb` int(11) NOT NULL,
					  `max_ancient_id` int(11) NOT NULL,
					  `max_indexed_id` int(11) NOT NULL,
					  PRIMARY KEY  (`id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
				");
		}








		// remove also all the database entries
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('joints') . "` ADD INDEX ( `joint_id` )");
		$this->db->query("ALTER TABLE `" . $this->db->dbprefix('joints') . "` ADD INDEX ( `team_id` )");

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
	}


}