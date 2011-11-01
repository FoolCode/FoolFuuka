<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * This is the base configuration file
 * Here we store the data necessary to start FoOlSlide at all
 * 
 * You can edit this file manually
 * 
 * Notice that not all database types need all the data
 * 
 */

// Database type (in almost all cases it's MySQL)
$db['default']['dbdriver'] = '';

// Database hostname (in almost all cases it's localhost)
$db['default']['hostname'] = 'localhost';

// The username of user with access to the database
$db['default']['username'] = '';

// The password of the MySQL user
$db['default']['password'] = '';

// The name of the Slide database
$db['default']['database'] = '';

// The prefix for the tables in the database
$db['default']['dbprefix'] = 'fs_';

// Session encryption: just tap randomly on your keyboard to make a random key
$config['encryption_key'] = '';




// Leave this alone, it's an automation
$config['db_table_prefix'] = $db['default']['dbprefix'];
