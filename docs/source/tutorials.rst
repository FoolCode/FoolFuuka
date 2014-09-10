Tutorials
=========

FoolFuuka as a 4chan Archive
----------------------------

* `Requirements`_
* `Compiling Asagi`_
* `Configuring Asagi`_
* `Using Asagi`_
* `Configuring FoolFuuka`_

Requirements
************

* `Asagi`_
* `JDK 7`_
* Maven

Compiling Asagi
***************

If you wish to compile `Asagi`_ manually, you will need to use `Maven`_ to create an executable `jar` container.

	Instructions:

	* `git clone https://github.com/oohnoitz/asagi.git`
	* Run the command `mvn package assembly:single` to generate a standalone executable jar

Configuring Asagi
*****************

Asagi uses a JSON configuration file named `asagi.json`. An example configuration file is included in the git repository under the filename `asagi.json.example`.

	"default": {

		"path": "/var/www/asagi/boards/",
			`This is the absolute path to the directory where all media files are stored.`

		"useOldDirectoryStructure": false,
			`This setting should remain set to false.`

		"webserverGroup": "www-data",
			`This should be set to the same user running your webserver.`

		"thumbThreads": 5,
			`This is the number of threads per board that will be used to download thumbnails.`

		"mediaThreads": 5,
			`This is the number of threads per board that will be used to download full images.`

		"newThreadsThreads": 5,
			`This is the number of threads per board that will be used to parse threads.`

		"deletedThreadsThresholdPage": 8,
			`This is the threshold which is used to determine if the thread has been purge from 4chan by the OP or janitor.`

		"refreshDelay": 10,
			`This will set the interval used to check the API for all modified threads.`

		"throttleAPI": true,
			`This will cause the fetcher to throttle properly to match the rate-limit implemented.`

		"throttleURL": "api.4chan.org",
			`This is the hostname of the API/HTML being throttled.`

		"throttleMillisec": 1100,
			`This will set the interval used for throttling.`

		"threadRefreshRate": 3
			`This will set the interval used to forcibly refresh a thread.`
	},

	"<board>": {<settings>},
		`<board>` is the letter of the board you wish to archive.

		`<settings>` are the settings listed above set at a per-board level.

	"<board>": {<settings>}

Using Asagi
***********

In order to run Asagi, you must have `JDK 7`_ or higher installed on the server. Furthermore, you must ensure that your JSON configuration file is properly configured as well. The following command is used to run `Asagi`_:

	`java -Xmx256m -XX:+UseParNewGC -XX:MaxPermSize=24m -jar asagi.jar`

*Note: It is recommended that `screen` or `tmux` be used with `Asagi`_.*

Configuring FoolFuuka
*********************

After you have `Asagi`_ running within `screen` or `tmux`, it is time to confiugre FoolFuuka to interface with the archived boards and its contents. Furthermore, it is required that you run or restart `Asagi`_ with an updated `asagi.json` containing any new boards you wish to archive from 4chan before adding them to FoolFuuka.

* Access the Control Panel for FoolFuuka
* Navigate to `Boards > Preferences`
* Fill in the `Boards Database` field with the database name set in `asagi.json`
* Save your settings
* Navigate to `Boards > Add Board`
* Fill in the required fields and check the "Is this a 4chan archiving board?" checkbox
* Submit to create the board
* Repeat the last three steps until all archived boards are added

You should now be able to interact with the archived boards with FoolFuuka and browse the data.

*Warning: You must have `Asagi`_ do the initial table creation for each board before adding them to FoolFuuka.*/

Deploying a Server Environment
------------------------------

We recommend that you use the latest build for each of the following software to avoid any potential problems. The instructions listed below should only be used as an example.

* `Preparing System`_
* `OpenSSL`_
* `BIND`_
* `MySQL`_
* `nginx`_
* `PHP-FPM`_
* `Git`_
* `Sphinx`_
* `JDK 7`_

Preparing System
****************

	Debian::

		apt-get update && apt-get upgrade
		apt-get remove apache apache2
		apt-get install autoconf build-essentials screen tmux sudo

BIND
*****

	Debian::

		apt-get install bind

MySQL
*****

	Debian (MariaDB)::

		apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db
		echo "deb http://ftp.osuosl.org/pub/mariadb/repo/10.0/debian squeeze main" >> /etc/apt/sources.list
		echo "deb-src http://ftp.osuosl.org/pub/mariadb/repo/10.0/debian squeeze main" >> /etc/apt/sources.list
		apt-get update
		apt-get install mariadb-server mariadb-client

	Configure:

	* Modify `/etc/my.cnf`


nginx
*****

	Debian::

		apt-get install build-essential libpcre3-dev libssl-dev
		cd /var/tmp
		wget -q http://nginx.org/download/nginx-1.3.6.tar.gz
		tar zxf nginx-*
		cd nginx-*
		./configure --prefix=/usr/local/nginx --user=www-data --group=www-data --with-debug --with-ipv6 --with-http_realip_module --with-http_ssl_module
		make && make install
		wget https://raw.github.com/oohnoitz/nginx-installer/master/init.d/nginx-debian-ubuntu -O /etc/init.d/nginx
		chmod +x /etc/init.d/nginx
		mkdir /usr/local/nginx/cert
		mkdir /usr/local/nginx/sites-available
		/usr/sbin/update-rc.d -f nginx defaults

	Configure:

	* Modify `/usr/local/nginx/conf/nginx.conf`
	* Modify `/usr/local/nginx/sites-available/default`


PHP-FPM
*******

	Debian::

		apt-get install libbz2-dev libcurl4-dev libxml2-dev libjpeg-dev libpng-dev libtiff-dev libmcrypt-dev locales-all
		cd /var/tmp
		wget -q http://us.php.net/distributions/php-5.4.13.tar.gz
		tar zxf php-*
		cd php-*
		./configure --enable-fpm --enable-zip --enable-sockets --with-pdo-mysql --with-mysqli --with-mysql --with-gettext --with-gd --enable-ftp --enable-exif --with-curl --with-bz2 --with-openssl --with-mcrypt --enable-mbstring --with-jpeg-dir --with-png-dir --with-zlib --enable-bcmath
		make && make install
		cp sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
		cp php.ini-production /usr/local/lib/php.ini
		cp /usr/local/etc/php-fpm.conf.default /usr/local/etc/php-fpm.conf
		chmod 755 /etc/init.d/php-fpm
		update-rc.d php-fpm defaults

	Additional Components::

		pecl install APC-3.1.13

	Configure:

	* Modify `/usr/local/etc/php-fpm.conf` and replace all instances of `nobody` with `www-data`
	* Modify `/usr/local/lib/php.ini`

Git
***

	Debian::

		apt-get install git

Sphinx
******

	Debian::

		apt-get install libexpat1-dev
		cd /var/tmp
		wget -q http://sphinxsearch.com/files/sphinx-2.1.9-release.tar.gz
		tar zxf sphinx-*
		cd sphinx-*
		./configure --prefix=/usr/local/sphinx
		make install

JDK 7
*****

	Debian::

		apt-get install openjdk-7-jdk
		apt-get install ia32-libs


.. _Asagi: https://github.com/oohnoitz/asagi