FoolFuuka
=========

FoolFuuka was designed to fulfill the following two purposes:

  - host a self-contained image-based bulletin board
  - provide a front-end for [Asagi](https://github.com/eksopl/asagi), the 4chan archiver

_Note: This is just a Composer package. In order to install FoolFuuka, follow the instructions listed below._

Requirements
------------

  - Apache/nginx
  - Composer
  - Git
  - MySQL 5.5.3+
  - PHP 5.4+

Installation
------------

- Clone the [latest version](https://github.com/FoolCode/FoolFuuka-install) of the FoolFuuka Installer into a directory on your server

		$ git clone https://github.com/FoolCode/FoolFuuka-install.git

- In the root of the FoolFuuka-install folder, run `php composer.phar install -o` command to install all of the dependencies required for FoolFuuka

- Set the `public` folder as the public directory of the webserver

- Browse to the index page to begin the setup

Update
------

It is recommended that all updates by applied with Composer to avoid encountering any dependency issues. **Make sure you have a backup of the content before performing an update.**

- Take the site offline

- In the root directory of the FoolFuuka-install folder, run `git pull` to update the composer definitions

- In the root directory of the FoolFuuka-install folder, run `php composer.phar update` to update the code

- Take the site back online