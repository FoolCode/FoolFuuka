Asagi
=====

Requirements
------------

* `Asagi`_
* JDK 7
* Maven

Compiling Asagi
---------------

.. code-block:: sh

    $ git clone https://github.com/FoolCode/asagi.git
    $ mvn package assembly:single


Configuring Asagi
-----------------

Asagi uses a JSON configuration file named ``asagi.json``. An example configuration file is included within
thegit repository as ``asagi.json.example``.

.. warning::

    If you are using MySQL/MariaDB as your database server, you must set the ``character_set_server`` setting
    under the ``[mysqld]`` section to ``utf8mb4`` in your ``my.cnf`` file. This will allow you to properly
    store multi-byte unicode characters properly.

.. note::

    We recommend that you configure `Asagi`_ to use its own database and configure your database server to
    allow the account created for FoolFuuka to have full access to the `Asagi`_ database as well.


Running Asagi
-------------

.. code-block:: sh

    $ java -Xmx256m -XX:+UseParNewGC -XX:MaxPermSize=24m -jar asagi.jar

.. note::

    We strongly recommend the usage of ``screen`` or ``tmux`` with `Asagi`_. Also, you may be required to
    adjust the ``Xmx`` and ``XX:MaxPermSize`` values accordingly.


Configuring FoolFuuka
---------------------

.. warning::

    It is very crucial that you configure and run/restart `Asagi`_ before adding the board to FoolFuuka.
    This will allow `Asagi`_ to create the board tables properly with some additional steps that aren't
    included in FoolFuuka. If this is not done, the board tables will not be populated properly.

You must first configure FoolFuuka to use the `Asagi`_ database created in the previous steps. This can
be done by following the steps listed below:

1) Access the FoolFuuka Administrative Panel
2) Navigate to ``Preferences`` under the ``Boards`` section
3) Set the ``Boards Database`` field to the same database name used in the ``asagi.json`` config file
4) Save your changes

.. note::

    The steps listed above only need to be completed once.

In order to access the boards being archived with `Asagi`_, you will need to add the boards to FoolFuuka
by following the steps listed below:

1) Access the FoolFuuka Administrative Panel
2) Navigate to ``Manage`` under the ``Boards`` section
3) Click "Add Board"
4) Fill out the required fields properly
5) Check the "Is this an archived board?" checkbox
6) Click "Submit" to add the board to the database

.. note::

    You will need to repeat the steps listed above each time you wish to add a board archived by `Asagi`_.


.. _Asagi: https://github.com/FoolCode/asagi
