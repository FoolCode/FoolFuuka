<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Layout;

class OpenSearch extends \Foolz\Theme\View
{
    public function toString()
    {
        ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
    <LongName><?= \Preferences::get('foolframe.gen.website_title'); ?> Search</LongName>
    <ShortName><?= \Preferences::get('foolframe.gen.website_title'); ?> Search</ShortName>
    <Url type="text/html" template="<?= \Uri::create('_/search/') ?>text/{searchTerms}" />
    <Description>Global Search <?= \Uri::base() ?>.</Description>
    <Image height="16" width="16" type="image/vnd.microsoft.icon"><?= \Uri::base() ?>favicon.ico</Image>
    <InputEncoding>UTF-8</InputEncoding>
    <OutputEncoding>UTF-8</OutputEncoding>
</OpenSearchDescription><?php
    }
}
