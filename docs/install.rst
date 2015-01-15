.. _install:

Installation
============


Install
+++++++


Via Composer
------------

.. code-block:: sh

    $ composer create-project foolz/foolfuuka foolfuuka
    $ cd foolfuuka
    $ composer dump-autoload --optimize


Via Source Code
---------------

.. code-block:: sh

    $ git clone https://github.com/FoolCode/FoolFuuka foolfuuka
    $ cd foolfuuka
    $ git checkout <version>
    $ composer install --optimize


Upgrading
+++++++++

.. code-block:: sh

    $ git fetch --all
    $ git checkout <version>
    $ composer update --optimize

.. note::

    The commands provided above will only upgrade the FoolFuuka code. You may be required to complete
    some additional steps to completely upgrade FoolFuuka to the next version. Please consult the upgrade
    guides to ensure that the upgrade process is done properly.

.. seealso:: :doc:`/user_guide/upgrade/index`
