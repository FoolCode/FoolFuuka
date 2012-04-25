<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| FoOlSlide constants
|--------------------------------------------------------------------------
|
| Version and all the functions that are supported by the framework
|
*/

define('FOOL_VERSION', '0.7.0-dev-2');
define('FOOL_NAME', 'FoOlFuuka');
define('FOOL_MANUAL_INSTALL_URL', 'http://ask.foolrulez.com');
define('FOOL_GIT_TAGS_URL', 'https://api.github.com/repos/foolrulez/foolfuuka/tags');
define('FOOL_GIT_CHANGELOG_URL', 'https://raw.github.com/foolrulez/FoOlFuuka/master/CHANGELOG.md');
define('FOOL_REQUIREMENT_PHP', '5.3.0');
define('FOOL_PLUGIN_DIR', 'content/plugins/');
define('FOOL_PROTECTED_RADIXES', serialize(array('content', 'assets', 'admin', 'install', 'feeds', 'api', 'cli', 'functions', 'search')));

// preferences from get_setting('value', FOOL_PREF_ETC);
define('FOOL_PREF_SYS_SUBDOMAIN', FALSE);
define('FOOL_PREF_SPHINX_LISTEN', '127.0.0.1:9306');
define('FOOL_PREF_SPHINX_LISTEN_MYSQL', '127.0.0.1:9306');
define('FOOL_PREF_SPHINX_DIR', '/usr/local/sphinx/var');
define('FOOL_PREF_SPHINX_MIN_WORD', 3);
define('FOOL_PREF_SPHINX_MEMORY', 2047);

define('FOOL_PREF_SERV_JAVA_PATH', 'java');

define('FOOL_THEME_DEFAULT', 'default');
define('FOOL_PREF_THEMES_THEME_DEFAULT_ENABLED', TRUE);
define('FOOL_PREF_THEMES_THEME_FUUKA_ENABLED', TRUE);
define('FOOL_PREF_THEMES_THEME_YOTSUBA_ENABLED', FALSE);

/*
|--------------------------------------------------------------------------
| FoOlFuuka specific constants
|--------------------------------------------------------------------------
|
|
*/

define('FOOLFUUKA_BOARDS_DIRECTORY', 'content/boards');

define(
	'FOOLFUUKA_SECURE_TRIPCODE_SALT', '
	FW6I5Es311r2JV6EJSnrR2+hw37jIfGI0FB0XU5+9lua9iCCrwgkZDVRZ+1PuClqC+78FiA6hhhX
	U1oq6OyFx/MWYx6tKsYeSA8cAs969NNMQ98SzdLFD7ZifHFreNdrfub3xNQBU21rknftdESFRTUr
	44nqCZ0wyzVVDySGUZkbtyHhnj+cknbZqDu/wjhX/HjSitRbtotpozhF4C9F+MoQCr3LgKg+CiYH
	s3Phd3xk6UC2BG2EU83PignJMOCfxzA02gpVHuwy3sx7hX4yvOYBvo0kCsk7B5DURBaNWH0srWz4
	MpXRcDletGGCeKOz9Hn1WXJu78ZdxC58VDl20UIT9er5QLnWiF1giIGQXQMqBB+Rd48/suEWAOH2
	H9WYimTJWTrK397HMWepK6LJaUB5GdIk56ZAULjgZB29qx8Cl+1K0JWQ0SI5LrdjgyZZUTX8LB/6
	Coix9e6+3c05Pk6Bi1GWsMWcJUf7rL9tpsxROtq0AAQBPQ0rTlstFEziwm3vRaTZvPRboQfREta0
	9VA+tRiWfN3XP+1bbMS9exKacGLMxR/bmO5A57AgQF+bPjhif5M/OOJ6J/76q0JDHA=='
);

/* End of file constants.php */
/* Location: ./application/config/constants.php */