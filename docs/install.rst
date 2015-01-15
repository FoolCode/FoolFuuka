.. _install:

Installation
============


Install
+++++++


Via Composer
------------

.. code-block:: sh

    $ composer create-project foolz/foolfuuka foolfuuka
    $ composer dump-autoload --optimize


Via Source Code
---------------

.. code-block:: sh

    $ git clone https://github.com/FoolCode/FoolFuuka foolfuuka
    $ git checkout <version>
    $ composer install --optimize


Upgrading
+++++++++

.. code-block:: sh

    $ git fetch --all
    $ git checkout <version>
    $ composer update --optimize
