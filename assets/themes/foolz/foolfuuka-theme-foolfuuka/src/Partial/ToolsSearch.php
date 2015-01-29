<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class ToolsSearch extends \Foolz\FoolFuuka\View\View
{

    public function toString()
    {
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $search = $this->getBuilderParamManager()->getParam('search', []);
        $form = $this->getForm();

        if (is_null($radix) && $this->getPreferences()->get('foolfuuka.sphinx.global')) {
            // search can work also without a radix selected
            $search_radix = '_';
        } elseif (!is_null($radix)) {
            $search_radix = $radix->shortname;
        }

        if (isset($search_radix)) : ?>

        <ul class="nav pull-right">
        <?= $form->open([
            'class' => 'navbar-search',
            'method' => 'POST',
            'action' => $this->getUri()->create($search_radix.'/search')
        ]);
        ?>

        <li>
        <?= $form->input([
            'name' => 'text',
            'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
            'class' => 'search-query',
            'placeholder' => ($search_radix  !== '_') ? _i('Search or insert post number') : _i('Search through all the boards')
        ]); ?>
        </li>
        <?= $form->close() ?>
        </ul>
        <?php endif;
    }
}
