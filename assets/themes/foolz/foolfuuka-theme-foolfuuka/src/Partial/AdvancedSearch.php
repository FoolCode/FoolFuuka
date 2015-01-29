<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class AdvancedSearch extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $search = $this->getBuilderParamManager()->getParam('search', []);

        $form = $this->getForm();

        if (!isset($radix) && $this->getPreferences()->get('foolfuuka.sphinx.global')) {
            // search can work also without a radix selected
            $search_radix = '_';
        } elseif (isset($radix)) {
            $search_radix = $radix->shortname;
        }
        ?>

        <?php if (isset($search_radix)) : ?>
        <div class="advanced_search clearfix">
            <?= $form->open(['method' => 'POST', 'action' => $this->getUri()->create($search_radix.'/search')]); ?>

        <div class="comment_wrap">
            <?= $form->input([
            'name' => 'text',
            'id' => 'search_form_comment',
            'value' => (isset($search['text'])) ? rawurldecode($search['text']) : '',
            'placeholder' => ($search_radix  !== '_') ? _i('Search or insert post number') : _i('Search through all the boards'),
        ]);
            ?>
        </div>

        <div class="buttons clearfix">
            <?= $form->submit([
                'class' => 'btn btn-inverse',
                'value' => _i('Search'),
                'name' => 'submit_search',
            ]);
            ?>

            <?= $form->submit([
            'class' => 'btn btn-inverse',
            'value' => _i('Search on all boards'),
            'name' => 'submit_search_global',
        ]);
            ?>

            <?php if (isset($radix)) : ?>
            <?= $form->submit([
                'class' => 'btn btn-inverse',
                'value' => _i('Go to post number'),
                'name' => 'submit_post',
            ]);
            ?>
            <?php endif; ?>

            <?= $form->reset([
                'class' => 'btn btn-inverse pull-right',
                'value' => _i('Clear'),
                'name' => 'reset',
                'data-function' => 'clearSearch'
            ]);
            ?>
        </div>

            <?php $search_structure = \Search::structure(); ?>

        <div class="column">
            <?php
            foreach ($search_structure as $element) {
                if (isset($element['access']) && !$this->getAuth()->hasAccess($element['access'])) {
                    continue;
                }

                if ($element['type'] === 'input') {
                    if ($element['name'] === 'text') {
                        continue;
                    }

                    echo '<div class="input-prepend">';
                    echo '<label class="add-on" for="search_form_'.$element['name'].'">'.e($element['label']).'</label>';
                    echo $form->input([
                        'name' => $element['name'],
                        'id' => 'search_form_'.$element['name'],
                        'value' => (isset($search[$element['name']])) ? rawurldecode($search[$element['name']]) : '',
                        'placeholder' => (isset($element['placeholder'])) ? $element['placeholder'] : '',
                    ]);
                    echo '</div>';
                }

                if ($element['type'] === 'date') {
                    echo '<div class="input-prepend">';
                    echo '<label class="add-on" for="search_form_'.$element['name'].'">'.e($element['label']).'</label>';
                    echo $form->input(
                        ['type' => 'date',
                            'name' => $element['name'],
                            'placeholder' => 'YYYY-MM-DD',
                            'autocomplete' => 'off',
                            'value' => (isset($search[$element['name']])) ? rawurldecode($search[$element['name']]) : ''
                        ]
                    );
                    echo '</div>';
                }
            }
            ?>

            <?php if (!isset($radix) || $radix->sphinx) : ?>
            <div class="radixes">
                <?php
                $boards = (!empty($search) && $search['boards'] !== null) ? explode('.', $search['boards']) : (isset($radix) ? [$radix->shortname] : []);
                ?>
                <div>
                    <?php
                    $radixes = $this->getRadixColl()->getArchives();

                    foreach($radixes as $key => $r) {
                        if (!$r->sphinx) {
                            unset($radixes[$key]);
                        }
                    }

                    $uncheck = true;
                    if (!empty($boards)) {
                        foreach ($radixes as $r) {
                            if (!in_array($r->shortname, $boards)) {
                                $uncheck = false;
                                break;
                            }
                        }
                    }

                    if ($radixes) :
                        ?>
                        <div><h5><?= e(_i('On these archives')) ?></h5>
                            <button type="button" data-function="checkAll" class="btn btn-mini pull-right check"<?= $uncheck ? ' style="display:none"' : '' ?>><?= e(_i('Check all')) ?></button>
                            <button type="button" data-function="uncheckAll" class="btn btn-mini pull-right uncheck"<?= $uncheck ? ' style="display:block"' : '' ?>><?= e(_i('Uncheck all')) ?></button>
                        </div>
                        <?php
                        foreach ($radixes as $r) {
                            echo '<label>'.$form->checkbox('boards[]', $r->shortname, in_array($r->shortname, $boards) || empty($boards)).' /'.e($r->shortname).'/</label>';
                        }
                        ?>
                        <?php endif; ?>
                </div>

                <div style="clear:left; padding-top: 10px">
                    <?php
                    $radixes = $this->getRadixColl()->getBoards();

                    foreach($radixes as $key => $r) {
                        if (!$r->sphinx) {
                            unset($radixes[$key]);
                        }
                    }

                    $uncheck = true;
                    if (!empty($boards)) {
                        foreach ($radixes as $r) {
                            if (in_array($r->shortname, $boards)) {
                                $uncheck = false;
                                break;
                            }
                        }
                    }

                    if ($radixes):
                        ?>
                        <div>
                            <h5><?= e(_i('On these boards')) ?></h5>
                            <button type="button" data-function="checkAll" class="btn btn-mini pull-right check"<?= $uncheck ? ' style="display:none"' : '' ?>><?= e(_i('Check all')) ?></button>
                            <button type="button" data-function="uncheckAll" class="btn btn-mini pull-right uncheck"<?= $uncheck ? ' style="display:block"' : '' ?>><?= e(_i('Uncheck all')) ?></button>
                        </div>
                        <?php
                        foreach ($radixes as $r) {
                            echo '<label>'.$form->checkbox('boards[]', $r->shortname, in_array($r->shortname, $boards) || empty($boards)).' /'.e($r->shortname).'/</label>';
                        }
                        ?>
                        <?php endif; ?>
                </div>
            </div>
            <?php endif ?>

            <div class="latest_searches">
                <div>
                    <h5><?= e(_i('Your latest searches')) ?></h5>
                    <button type="button" data-function="clearLatestSearches" class="btn btn-mini pull-right"><?= e(_i('Clear')) ?></button>
                </div>
                <ul>
                    <?php
                    if (isset($latest_searches) || $latest_searches = @json_decode($this->getCookie('search_latest_5'), true)) {
                        // sanitization
                        foreach($latest_searches as $item) {
                            // all subitems must be array, all must have 'radix'
                            if (!is_array($item) || !isset($item['board'])) {
                                $latest_searches = [];
                                break;
                            }
                        }

                        foreach($latest_searches as $latest_search) {
                            $extra_text = '';
                            $extra_text_br = '';

                            $uri = ($latest_search['board'] === false ? '_' : $latest_search['board']) . '/search/';
                            $text = ($latest_search['board'] === false) ? '<strong>global:</strong> ' : '/<strong>' . e($latest_search['board']) . '</strong>/: ';
                            unset($latest_search['board']);

                            if (isset($latest_search['order']) && $latest_search['order'] === 'desc') {
                                unset($latest_search['order']);
                            }

                            foreach($latest_search as $k => $i) {
                                if ($k == 'text') {
                                    $text .= e(urldecode($latest_search['text'])) . ' ';
                                } else {
                                    $extra_text .= '<span class="options">[' . e($k) . '] ' . e(urldecode($i)) . ' </span>';
                                    $extra_text_br .= '<br/><span class="options">[' . e($k) . '] ' . e(urldecode($i)) . ' </span>';
                                }

                                $uri .= $k.'/'.$i.'/';
                            }

                            echo '<li title="' . strip_tags($text . $extra_text_br) . '" class="latest_search"><a href="' . htmlspecialchars($this->getUri()->create($uri)) . '">' . $text . ' ' . $extra_text . '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        <div class="column checkboxes"><table class="table"><tbody>
            <?php

            foreach ($search_structure as $element) :
                if (isset($element['access']) && !$this->getAuth()->hasAccess($element['access'])) {
                    continue;
                }

                if ($element['type'] === 'radio') : ?>
                <tr><td><?= e($element['label']) ?></td><td>
                    <?php foreach ($element['elements'] as $el) : ?>
                    <label>
                        <?= $form->radio(
                            $element['name'],
                            $el['value'] ? : '',
                            (isset($search[$element['name']]) && $el['value'] === $search[$element['name']]) || (!isset($search[$element['name']]) && $el['value'] === false)
                        ) ?>
                        <?= e($el['text']); ?>
                    </label>
                    <?php endforeach; ?>
                </td></tr>
                    <?php endif;

            endforeach; ?>
        </tbody></table></div>

            <?= $form->close() ?>

        </div>
        <?php endif;
    }
}
