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
						`ip_address` decimal(39,0) unsigned NOT NULL,
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
						`name` varchar(64) NOT NULL,
						`value` text NOT NULL,
						`group` int(11) NOT NULL,
						PRIMARY KEY (`name`)
					) ENGINE=InnoDB CHARSET=utf8;"
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
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
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
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
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
						PRIMARY KEY (`id`),
						KEY `username` (`username`),
						KEY `created` (`created`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
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
						PRIMARY KEY (`id`),
						KEY `user_id` (`user_id`),
						KEY `group_id` (`group_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
			);
		}


		if (!$this->db->table_exists($this->db->dbprefix('boards')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('boards') . "` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`shortname` varchar(32) CHARACTER SET latin1 NOT NULL,
						`name` varchar(256) CHARACTER SET latin1 NOT NULL,
						`archive` int(11) NOT NULL,
						`sphinx` int(11) NOT NULL,
						`hidden` int(11) NOT NULL,
						`hide_thumbnails` smallint(5) unsigned NOT NULL,
						`directory` varchar(512) COLLATE utf8_bin NOT NULL,
						`max_ancient_id` int(11) NOT NULL,
						`max_indexed_id` int(11) NOT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY `shortname` (`shortname`),
						KEY `hide_thumbnails` (`hide_thumbnails`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				");
		}
		
		if (!$this->db->table_exists($this->db->dbprefix('boards_preferences')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('boards_preferences') . "` (
						`board_preference_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`board_id` int(11) unsigned NOT NULL,
						`name` varchar(64) NOT NULL,
						`value` text,
						PRIMARY KEY (`board_preference_id`),
						KEY `board_id_name_index` (`board_id`,`name`)
						) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				");
		}
				

		
		if (!$this->db->table_exists($this->db->dbprefix('stopforumspam')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('stopforumspam') . "` (
						`ip` int(10) unsigned NOT NULL,
						UNIQUE KEY `ip` (`ip`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				");
		}
		
		
		if (!$this->db->table_exists($this->db->dbprefix('reports')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('reports') . "` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`board_id` int(11) NOT NULL,
						`doc_id` int(11) NOT NULL,
						`reason` text CHARACTER SET latin1 NOT NULL,
						`ip_reporter` decimal(39,0) unsigned NOT NULL,
						`status` int(11) NOT NULL,
						`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						PRIMARY KEY (`id`),
						KEY `board_id` (`board_id`,`doc_id`),
						KEY `status` (`status`),
						KEY `created` (`created`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				");
		}
		
		
		if (!$this->db->table_exists($this->db->dbprefix('posters')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('posters') . "` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`ip` decimal(39,0) NOT NULL,
						`banned` tinyint(1) NOT NULL,
						`banned_reason` varchar(256) DEFAULT NULL,
						`banned_start` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
						`banned_length` int(11) DEFAULT NULL,
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				");
		}
		
		
		if (!$this->db->table_exists($this->db->dbprefix('plugins')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('plugins') . "` (
						`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
						`slug` varchar(64) NOT NULL,
						`enabled` smallint(2) unsigned NOT NULL,
						`revision` int(10) unsigned DEFAULT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY `slug` (`slug`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				");
		}
		
		if (!$this->db->table_exists($this->db->dbprefix('banned_md5')))
		{
			$this->db->query("
					CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('banned_md5') . "` (
						`md5` varchar(64) NOT NULL,
						PRIMARY KEY (`md5`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				");
		}
	}

}