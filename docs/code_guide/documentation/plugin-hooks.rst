Plugin Hooks
============

Plugin
------

**Foolz\\Plugin\\Plugin::execute#<plugin-name>**

.. code-block:: php

    ->setObject($plugin)
    ->setParam('context', $this->getContext())

**Foolz\\Foolframe\\Model\\Plugin::install#<plugin-name>**

.. code-block:: php

    ->setParam('context', $this->getContext())
    ->setParam('schema', $sm->getCodedSchema())


FoolFrame
---------

**Foolz\\Foolframe\\Controller\\Admin::before#var.sidebar**

.. code-block:: php

    ->setObject($this)
    ->setParam('sidebar', [])

**Foolz\\Foolframe\\Model\\Context::handleConsole#obj.app**

.. code-block:: php

    ->setObject($this)
    ->setParam('application', $application)

**Foolz\\Foolframe\\Model\\Context::handleWeb#obj.afterAuth**

.. code-block:: php

    ->setObject($this)
    ->setParam('route_collection', $this->route_collection)

**Foolz\\Foolframe\\Model\\Context::handleWeb#obj.routing**

.. code-block:: php

    ->setObject($this)
    ->setParam('route_collection', $this->route_collection)

**Foolz\\Foolframe\\Model\\Context::handleWeb#obj.context**

.. code-block:: php

    ->setObject($this)

**Foolz\\Foolframe\\Model\\Context::handleWeb#obj.request**

.. code-block:: php

    ->setObject($this)
    ->setParam('request', $request)

**Foolz\\Foolframe\\Model\\Context::handleWeb#obj.response**

.. code-block:: php

    ->setObject($this)
    ->setParam('request', $request)

**Foolz\\Foolframe\\Model\\Preferences::load#var.preferences**

.. code-block:: php

    ->setObject($this)
    ->setParam('preferences', $this->preferences)

**Foolz\\Foolframe\\Model\\SchemaManager::forge#var.ignorePrefix**

.. code-block:: php

    ->setObject(new static())
    ->setParam('prefixes', $prefixes)

**Foolz\\Foolframe\\Model\\SchemaManager::forge#var.tables**

.. code-block:: php

    ->setObject(new static())
    ->setParam('tables', $tables)

**Foolz\\Foolframe\\Model\\System::getEnvironment#var.environment**

.. code-block:: php

    ->setParam('environment', $environment)


FoolFuuka
---------

**Foolz\\Foolfuuka\\Model\\Comment::processComment#var.greentext**

.. code-block:: php

    ->setParam('html', $html)

**Foolz\\Foolfuuka\\Model\\Comment::processExternalLinks#var.link**

.. code-block:: php

    ->setObject($this)
    ->setParam('data', $data)
    ->setParam('build_href', $build_href)

**Foolz\\Foolfuuka\\Model\\Comment::processInternalLinks#var.link**

.. code-block:: php

    ->setObject($this)
    ->setParam('data', $data)
    ->setParam('build_url', $build_url)

**Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.captcha**

.. code-block:: php

    ->setObject($this)

**Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.afterInputCheck**

.. code-block:: php

    ->setObject($this)

**Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.comment**

.. code-block:: php

    ->setObject($this)

**Foolz\\Foolfuuka\\Model\\Context::loadRoutes#obj.beforeRouting**

.. code-block:: php

    ->setObject($this)
    ->setParam('route_collection', $route_collection)

**Foolz\\Foolfuuka\\Model\\Context::loadRoutes#var.collection**

.. code-block:: php

    ->setParam('default_suffix', page)
    ->setParam('suffix', page)
    ->setParam('controller', 'Foolz\\Foolfuuka\\Controller\\Chan::*')

**Foolz\\Foolfuuka\\Model\\Context::loadRoutes#obj.afterRouting**

.. code-block:: php

    ->setObject($this)
    ->setParam('route_collection', $route_collection)

**Foolz\\Foolfuuka\\Model\\Media::getLink#exec.beforeMethod**

.. code-block:: php

    ->setObject($this)
    ->setParam('thumbnail', $thumbnail)

**Foolz\\Foolfuuka\\Model\\Media::insert#var.media**

.. code-block:: php

    ->setParam('dimensions', $dimensions)
    ->setParam('file', $file)
    ->setParam('name', $name
    ->setParam('path', $path)
    ->setParam('hash', $hash)
    ->setParam('size', $size)
    ->setParam('time', $time)
    ->setParam('media_orig', $media_orig)
    ->setParam('preview_orig', $preview_orig)

**Foolz\\Foolfuuka\\Model\\Media::insert#exec.createThumbnail**

.. code-block:: php

    ->setObject($this)
    ->setParam('is_op', $is_op)
    ->setParam('media', $media)
    ->setParam('thumb', $thumb)
    ->setParam('thumb_width', $thumb_width)
    ->setParam('thumb_height', $thumb_height)
    ->setParam('exec', $exec)

**Foolz\\Foolfuuka\\Model\\MediaFactory::forgeFromUpload#var.config**

.. code-block:: php

    ->setParam('ext_whitelist', [])
    ->setParam('mime_whitelist', [])

**Foolz\\Foolfuuka\\Model\\RadixCollection::structure#var.structure**

.. code-block:: php

    ->setParam('structure', $structure)

**Foolz\\Foolfuuka\\Model\\RadixCollection::preload#var.radixes**

.. code-block:: php

    ->setObject($this)
    ->setParam('preloaded_radixes', $this->preloaded_radixes)
