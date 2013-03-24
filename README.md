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

#### Install Composer
FoolFuuka utilizes [Composer](https://getcomposer.org/) to manage all dependencies used by the software. You will need to download a copy of `composer.phar` or update your existing `composer.phar` file with `php composer.phar selfupdate`.

#### Using the FoolFuuka Installer
Once you have Composer installed, download the [latest version](https://github.com/FoolCode/FoolFuuka-install) of the FoolFuuka Installer and extract its contents into a directory on your server. Next, in the root of the FoolFuuka Installer folder, run the `php composer.phar install` command to install all of the dependencies required for FoolFuuka.

Update
------

#### Via Composer
It is recommended that all updates by applied with Composer to avoid encountering any dependency issues. In order to do this, run the `php composer.phar update` command in the root of your FoolFuuka Installer folder to begin the updating process. It should take at least a minute to finish the process.

#### Via Git
Since Composer relies on Git for the install and update process, it is possible to manually update each dependency and package to the latest stable version or development version. In order to do this, you will need to `cd` into the directory containing the package inside the vendor folder and use `git` commands to update it.

__THIS IS NOT RECOMMENDED.__

##### List of Packages

  - doctrine/common
  - doctrine/dbal
  - foolz/cache
  - foolz/foolframe
  - foolz/foolfuuka
  - foolz/inet
  - foolz/package
  - foolz/plugin
  - foolz/sphinxql-query-builder
  - foolz/theme
  - leafo/lessphp