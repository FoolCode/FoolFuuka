FoOlFuuka
=========

__Notice: FoOlFuuka is not yet ready to be installed__


About
-----

FoOlFuuka is an imageboard written in PHP and based on the CodeIgniter framework.

Unlike most imageboards, it is built like a CMS and looks modern.

FoOlFuuka also works as frontend for the original [Fuuka](http://code.google.com/p/fuuka/) that automatically archives the 4chan boards. All the features from Fuuka's interface have been reimplemented and the original interface has been cloned as a separate theme.

Features
--------

* Support for both archives and regular imageboards
* Modern "Default" theme and classic "Fuuka" theme
* Real time threads, quick reply, on-hover backlinks
* Sorting by latest posts, latest threads, gallery
* Search with several filters (Sphinx-search compatible)
* Find similar images internally, search with external services
* Report buttons, report management system in admin panel
* Transparent thumbnails and GIF mostly correctly handled
* Statistics for posting rates, postcounts etc.
* Admin panel to manage boards, check server status, manage staff etc.
* APIs for connecting to external services
* Cron
* More nice things that you won't like because you don't like nice things

Requirements
------------

The requirements of FoOlFuuka are relatively high if you want the most out of it. This really means improved security rather than more features, which means you really should have the suggested versions.

* PHP 5.3, __PHP 5.3.10+ suggested__
* MySQL 5, __MySQL 5.5+ suggested__

FoOlFuuka is really light, and will run on any server meeting the requirements. Your first worry will be rather bandwidth and hard disk space.

Unlike other imageboards, FoOlFuuka keeps the threads stored forever, which means load will rise (extremely slowly) over time. This is mostly due to the MySQL fulltext search not having good performance.

Optional
--------

* Sphinx-search: blazing fast search, but needs lots of extra resources (RAM and HD space). Suggested for imageboards with several millions of posts.

Using with Fuuka Fetcher
------------------------

FoOlFuuka can be used for the archival of 4chan imageboards.

Just compile the needed fields in the admin panel to connect to the separate database tables and folders.

