Plugin Hooks
============

FoolFrame
---------

Foolz\\Foolframe\\Model\\System::getEnvironment#var.environment::

    setParam environment $environment

Foolz\\Foolframe\\Model\\SchemaManager::forge#var.ignorePrefix::

    setObject new static()
    setParam prefixes $prefixes

Foolz\\Foolframe\\Model\\SchemaManager::forge#var.tables::

    setObject new static()
    setParam tables $tables

Foolz\\Foolframe\\Model\\Preferences::load#var.preferences::

    setObject $this
    setparam preferences $this->preferences

Foolz\\Foolframe\\Model\\Context::handleConsole#obj.app::

    setObject $this
    setParam application $application

Foolz\\Foolframe\\Model\\Context::handleWeb#obj.afterAuth::

    setObject $this
    setParam route_collection $this->route_collection

Foolz\\Foolframe\\Model\\Context::handleWeb#obj.routing::

    setObject $this
    setParam route_collection $this->route_collection

Foolz\\Foolframe\\Model\\Context::handleWeb#obj.context::

    setObject $this

Foolz\\Foolframe\\Model\\Context::handleWeb#obj.request::

    setObject $this
    setParam request $request

Foolz\\Foolframe\\Model\\Context::handleWeb#obj.response::

    setObject $this
    setParam request $request

Foolz\\Foolframe\\Controller\\Admin::before#var.sidebar::

    setObject $this
    setParam sidebar []

Foolz\\Plugin\\Plugin::execute#<plugin-name>::

    setObject $plugin
    setParam context $this->getContext()

Foolz\\Foolframe\\Model\\Plugin::install#<plugin-name>::

    setParam context $this->getContext()
    setParam schema $sm->getCodedSchema()


FoolFuuka
---------

Foolz\\Foolfuuka\\Model\\Comment::processComment#var.greentext::

    setParam html $html

Foolz\\Foolfuuka\\Model\\Comment::processExternalLinks#var.link::

    setObject $this
    setParam data $data
    setParam build_href $build_href

Foolz\\Foolfuuka\\Model\\Comment::processInternalLinks#var.link::

    setObject $this
    setParam data $data
    setParam build_url $build_url

Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.captcha::

    setObject $this

Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.afterInputCheck::

    setObject $this

Foolz\\Foolfuuka\\Model\\CommentInsert::insert#obj.comment::

    setObject $this

Foolz\\Foolfuuka\\Model\\Context::loadRoutes#obj.beforeRouting::

    setObject $this
    setParam route_collection $route_collection

Foolz\\Foolfuuka\\Model\\Context::loadRoutes#var.collection::

    setParam default_suffix page
    setParam suffix page
    setParam controller 'Foolz\\Foolfuuka\\Controller\\Chan::*'

Foolz\\Foolfuuka\\Model\\Context::loadRoutes#obj.afterRouting::

    setObject $this
    setParam route_collection $route_collection

Foolz\\Foolfuuka\\Model\\Media::getLink#exec.beforeMethod::

    setObject $this
    setParam thumbnail $thumbnail

Foolz\\Foolfuuka\\Model\\Media::insert#var.media::

    setParam dimensions
    setParam file
    setParam name
    setParam path
    setParam hash
    setParam size
    setParam time
    setParam media_orig
    setParam preview_orig

Foolz\\Foolfuuka\\Model\\Media::insert#exec.createThumbnail::

    setObject $this
    setParam thumb_width
    setParam thumb_height
    setParam exec
    setParam is_op
    setParam media
    setParam thumb

Foolz\\Foolfuuka\\Model\\MediaFactory::forgeFromUpload#var.config::

    setParam ext_whitelist []
    setParam mime_whitelist []
Foolz\\Foolfuuka\\Model\\RadixCollection::structure#var.structure::

    setParam structure $structure

Foolz\\Foolfuuka\\Model\\RadixCollection::preload#var.radixes::

    setObject $this
    setParam preloaded_radixes $this->preloaded_radixes
