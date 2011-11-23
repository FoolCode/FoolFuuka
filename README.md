FoOlFuuka
=========

FoOlFuuka is a modern PHP imageboard based on Ellislabs' CodeIgniter and our FoOlSlide framework.

While keeping the traditional format of an imageboard, it introduces several enhancements like a more graphic
administration panel, HTML5, automatic spam control, friendlier reporting tools and so on, that Fuuka didn't
provide.

[Fuuka](http://code.google.com/p/fuuka/) was originally a 4chan archiver that we've inherited. 
FoOlFuuka keeps this tradition and keeps working mainly as a mirror for threads that die out on 4chan.


ATTENTION
=========
We are still developing this application. It won't work without our customizations, so don't try installing it yet.

Installation
------------
1.  Copy everything in the archive in a public server folder
2.  Create a MySQL database
3.  Go to http://yourdomain.com/fuukafolder/install
4.  Insert database info and admin account info
5.  Create a new board and follow the instructions

Adapting from Fuuka
-------------------
FoOlFuuka is able to cohexist with the original Fuuka. All you have to do is pointing FoOlFuuka to the original Fuuka through the admin panel preferences.

We've made a table change. The _local tables are not needed anymore, but we keep filling them for backward compatibility.

You need the doc_id columns at the beginning of the board table from Fuuka r70.

You will also need a poster_id column at the beginning of the table. It's a simple INT(10) column, NULL not allowed.

Fetcher
-------
At this time we are still coding the Java fetcher for FoOlFuuka. The perl fetcher for Fuuka still works, but you need to make a change on line 416 of /Board/Mysql.pm and replace with

	sprintf "(0, NULL, %u,$location,%u,%u,%s,%d,%d,%s,%d,%d,%d,%s,%s,%d,%d,%s,%s,%s,%s,%s,%s,%s)",

We added a zero for the poster_id not to throw a MySQL error.


Troubleshooting
---------------

* If you are on an Nginx server, follow this [link](http://trac.foolrulez.com/foolslide/wiki/nginx_install)
* If you are on an Apache server and you can't reach "/install", follow this [link](http://trac.foolrulez.com/foolslide/wiki/apache_htaccess)
* No, there's no physical "/install" folder to upload. It will still work in your browser.
* In case, come looking for help on the [trac](http://trac.foolrulez.com/foolslide), where you can use the ticket system to expose your problem