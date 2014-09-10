Getting Started
===============

Installation
------------

* `Install Composer`_
* `Install FoolFuuka`_

Install Composer
^^^^^^^^^^^^^^^^

FoolFuuka utilizes `Composer`_ to manage all of its dependencies and update the software.

First, you will need to install a local copy of `Composer`_ onto your server. This can be done by obtaining the `composer.phar` archive and placing it either in the root directory of FoolFuuka installation repository or in `/usr/local/bin` for a global installation.

*Note: We have included a copy of the `composer.phar` archive in the root directory of the FoolFuuka installation repository. However, it is recommended that you run `php composer.phar selfupdate` to ensure that it is the latest version.*

Install FoolFuuka
^^^^^^^^^^^^^^^^^

Once you have `Composer`_ installed either locally or globally, download or clone the `latest version`_ of the FoolFuuka install repository onto your server. However, it is recommended that you run `git clone https://github.com/FoolCode/FoolFuuka-install.git foolfuuka` to obtain a copy of the FoolFuuka installation repository. This will allow you any "core" files that still remains in the installer source with Git instead. Next, in the root directory of the install repository, run the `php composer.phar install -o` command to install all of the dependencies required to run FoolFuuka. Finally, access your FoolFuuka installation with a web browser and complete the steps shown to finish the installation.


Configuration
-------------

* `Apache`_
* `MySQL`_
* `nginx`_
* `Sphinx Search`_

Apache
^^^^^^

FoolFuuka should work as-is and should not require any additional configuration with the `.htaccess` configuration file provided. However, FoolFuuka requires a properly configured Apache with `modrewrite` working.

MySQL
^^^^^

We recommend switching to and using the MariaDB fork for MySQL. Furthermore, it is extremely important to apply the following changes to `etc/my.cnf` in order to store posts properly and efficiently::

	[mysqld]
	character_set_server      = utf8mb4
	innodb_data_file_path     = ibdata1:10M:autoextend
	innodb_file_per_table     = 1
	innodb_log_files_in_group = 2

*Note: We recommend that the `TokuDB` engine be used for every board table when dealing with large amounts of data. This mainly applies to 4chan archives.*

nginx
^^^^^

Here is a `basic example` server block template required to run FoolFuuka with nginx and PHP-FPM configured::

	server {
	    listen 80;
	    listen [::]:80;
	    listen      443 ssl spdy;
	    listen [::]:443 ssl spdy;

	    server_name domain.tld;
	    root /var/www/foolfuuka/public;
	    index index.php;


	    location / {
	        try_files $uri $uri/ /index.php;
	    }

	    location ~ \.php$ {
	        try_files      $uri =404;
	        include        fastcgi_params;
	        fastcgi_param  PATH_INFO $fastcgi_path_info;
	        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
	        fastcgi_pass   unix:/dev/shm/phpfpm.sock;
	    }
	}

*Note: Depending on your installation and configuration, you may be required to modify the server block.*

Sphinx Search
^^^^^^^^^^^^^

In order to enable and use FoolFuuka's integrated search, you are required to install `Sphinx`_ which is used as the full-text search engine. FoolFuuka will generate the necessary configuration file used to index all of the required content and to run the search daemon. You can locate the configuration file generator under the `Boards >> Search` interface in the administrative panel.

Contributing
------------

* `Coding Guidelines`_
* `Pull Requests`_

Coding Guidelines
^^^^^^^^^^^^^^^^^

FoolFuuka follows the `PSR-0`_ and `PSR-1`_ coding standards. In addition to these standards, below is a list of other coding standards that should be followed:

* Function and Control Structure opening `{` should be on a seperate line

Pull Requests
^^^^^^^^^^^^^

We will accept any valid pull requests submitted against the repository. If you wish to contribute, please submit them against the `master` branch.

.. _Composer: https://getcomposer.org/
.. _latest version: https://github.com/FoolCode/FoolFuuka-install
.. _Asagi: https://github.com/eksopl/asagi
.. _Sphinx: http://sphinxsearch.com/
.. _PSR-0: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
.. _PSR-1: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md