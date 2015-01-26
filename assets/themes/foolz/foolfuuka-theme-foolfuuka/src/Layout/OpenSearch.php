<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Layout;

class OpenSearch extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
    <LongName><?= $this->getPreferences()->get('foolframe.gen.website_title'); ?> Search</LongName>
    <ShortName><?= $this->getPreferences()->get('foolframe.gen.website_title'); ?> Search</ShortName>
    <Url type="text/html" template="<?= $this->getUri()->create('_/search/') ?>text/{searchTerms}" />
    <Description>Global Search <?= $this->getUri()->base() ?>.</Description>
    <Image height="16" width="16" type="image/vnd.microsoft.icon"><?= $this->getUri()->base() ?>favicon.ico</Image>
    <InputEncoding>UTF-8</InputEncoding>
    <OutputEncoding>UTF-8</OutputEncoding>
</OpenSearchDescription><?php
    }
}
