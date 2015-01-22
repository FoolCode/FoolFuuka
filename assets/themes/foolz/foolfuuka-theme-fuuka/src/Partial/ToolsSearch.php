<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Partial;

class ToolsSearch extends \Foolz\Foolfuuka\View\View
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
        <div style="overflow:hidden;">
            <!--- Search Input -->
            <?php echo $form->open(['action' => $this->getUri()->create($search_radix.'/search')]); ?>
            <div id="simple-search" class="postspan" style="float:left">
                <?= _i('Text Search') ?>
                [<a class="tooltip" href="#">?<span>Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing the word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt></span></a>]

                <?php
                echo $form->input([
                    'name' => 'text',
                    'id' => 'text',
                    'size' => '24',
                    'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
                ]);
                ?>

                <?php
                echo $form->submit([
                    'name' => 'submit',
                    'value' => 'Go'
                ]);
                ?>
                <a href="<?php echo $this->getUri()->create($search_radix.'/search') ?>" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ <?= _i('Advanced') ?> ]</a>
            </div>
            <?php echo $form->close(); ?>

            <!--- Advanced Search Input -->
            <?php echo $form->open(['action' => $this->getUri()->create($search_radix.'/search')]); ?>
            <div id="advanced-search" class="postspan" style="float:left;display:none">
                <table style="float:left">
                    <tbody>
                        <tr>
                            <td colspan="2" class="theader"><?= _i('Advanced Search') ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Text Search') ?></td>
                            <td>
                                <?php echo $form->input(['name' => 'text', 'size' => '32', 'id' => 'text2', 'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Subject') ?></td>
                            <td>
                                <?php echo $form->input(['name' => 'subject', 'size' => '32', 'id' => 'subject', 'value' => (isset($search["subject"])) ? rawurldecode($search["subject"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Username') ?> <a class="tooltip" href="#">[?]<span><?= _i('Search for an <b>exact</b> username. Leave empty for any username.') ?></span></a></td>
                            <td>
                                <?php echo $form->input(['name' => 'username', 'size' => '32', 'id' => 'username', 'value' => (isset($search["username"])) ? rawurldecode($search["username"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Tripcode') ?> <a class="tooltip" href="#">[?]<span><?= _i('Search for an <b>exact</b> tripcode. Leave empty for any tripcode.') ?></span></a></td>
                            <td>
                                <?php echo $form->input(['name' => 'tripcode', 'size' => '32', 'id' => 'tripcode', 'value' => (isset($search["tripcode"])) ? rawurldecode($search["tripcode"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('E-mail') ?></td>
                            <td>
                                <?php echo $form->input(['name' => 'email', 'size' => '32', 'id' => 'email', 'value' => (isset($search["email"])) ? rawurldecode($search["email"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('From Date') ?> <a class="tooltip" href="#">[?]<span><?= _i('Enter the starting date for your search.') ?><br/><?= _i('Format: YYYY-MM-DD') ?></span></a></td>
                            <td>
                                <?php
                                echo $form->input(
                                    ['type' => 'date',
                                        'name' => 'start',
                                        'placeholder' => 'YYYY-MM-DD',
                                        'id' => 'date_start',
                                        'value' => (isset($search["date_start"])) ? rawurldecode($search["date_start"]) : ''
                                    ]
                                );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('To Date') ?> <a class="tooltip" href="#">[?]<span><?= _i('Enter the ending date for your search.') ?><br/><?= _i('Format: YYYY-MM-DD') ?></span></a></td>
                            <td>
                                <?php
                                echo $form->input(
                                    [
                                        'type' => 'date',
                                        'name' => 'end',
                                        'id' => 'date_end',
                                        'placeholder' => 'YYYY-MM-DD',
                                        'value' => (isset($search["date_start"])) ? rawurldecode($search["date_start"]) : ''
                                    ]
                                );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Filename') ?></td>
                            <td>
                                <?php echo $form->input(['name' => 'filename', 'size' => '32', 'id' => 'filename', 'value' => (isset($search["filename"])) ? rawurldecode($search["filename"]) : '']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Image Hash') ?></td>
                            <td>
                                <?php echo $form->input(['name' => 'image', 'size' => '32', 'id' => 'image', 'value' => (isset($search["image"])) ? rawurldecode($search["image"]) : '']); ?>
                            </td>
                        </tr>

                        <?php
                            $checkboxes = [
                                [
                                    'label' => _i('Deleted posts'),
                                    'name' => 'deleted',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('All')],
                                        ['value' => 'deleted', 'text' => _i('Only Deleted Posts')],
                                        ['value' => 'not-deleted', 'text' => _i('Only Non-Deleted Posts')]
                                    ]
                                ],
                                [
                                    'label' => _i('Ghost posts'),
                                    'name' => 'ghost',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('All')],
                                        ['value' => 'only', 'text' => _i('Only Ghost Posts')],
                                        ['value' => 'none', 'text' => _i('Only Non-Ghost Posts')]
                                    ]
                                ],
                                [
                                    'label' => _i('Show posts'),
                                    'name' => 'filter',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('All')],
                                        ['value' => 'text', 'text' => _i('Only Containing Images')],
                                        ['value' => 'image', 'text' => _i('Only Containing Text')]
                                    ]
                                ],
                                [
                                    'label' => _i('Results'),
                                    'name' => 'type',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('All')],
                                        ['value' => 'op', 'text' => _i('Only Opening Posts')],
                                        ['value' => 'posts', 'text' => _i('Only Reply Posts')]
                                    ]
                                ],
                                [
                                    'label' => _i('Capcode'),
                                    'name' => 'capcode',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('All')],
                                        ['value' => 'user', 'text' => _i('Only User Posts')],
                                        ['value' => 'mod', 'text' => _i('Only Moderator Posts')],
                                        ['value' => 'admin', 'text' => _i('Only Admin Posts')],
                                        ['value' => 'dev', 'text' => _i('Only Developer Posts')]
                                    ]
                                ],
                                [
                                    'label' => _i('Order'),
                                    'name' => 'order',
                                    'elements' => [
                                        ['value' => false, 'text' => _i('New Posts First')],
                                        ['value' => 'asc', 'text' => _i('Old Posts First')]
                                    ]
                                ]
                            ];

                            foreach ($checkboxes as $checkbox) :
                        ?>
                        <tr>
                            <td class="postblock"><?= e($checkbox['label']) ?></td>
                            <td>
                        <?php foreach ($checkbox['elements'] as $element) : ?>
                                <label>
                                <?= $form->radio($checkbox['name'], $element['value'] ? : '', isset($search[$checkbox['name']]) && $element['value'] === $search[$checkbox['name']]) ?>
                                <span><?= e($element['text']) ?></span>
                                </label><br />
                        <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <tr>
                            <td class="postblock"><?= _i('Action') ?></td>
                            <td>
                                <?php
                                echo $form->submit([
                                    'value' => 'Search',
                                    'name' => 'submit_search'
                                ]);

                                if ($this->getPreferences()->get('foolfuuka.sphinx.global')) :
                                    echo $form->submit([
                                        'value' => 'Global Search',
                                        'name' => 'submit_search_global'
                                    ]);
                                endif;
                                ?>
                                <a href="#" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ <?=_i('Simple') ?> ]</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php echo $form->close(); ?>

        <?php if ($this->getRadix()) : ?>
            <!--- Post Input -->
            <?php echo $form->open(['action' => $this->getRadix()->shortname . '/post']); ?>
            <div class="postspan" style="float:left">
                <?= _i('View Post') ?>

                <?php
                echo $form->input([
                    'name' => 'post',
                    'id' => 'post',
                    'size' => '9'
                ]);
                ?>

                <?php
                echo $form->submit([
                    'name' => 'submit',
                    'value' => 'View',
                    'onclick' => 'getPost(this.form); return false;'
                ]);
                ?>
            </div>
            <?php echo $form->close(); ?>

            <!--- Page Input -->
            <?php echo $form->open(['action' => $this->getRadix()->shortname . '/page']); ?>
            <div class="postspan" style="float:left">
                <?= _i('View Page') ?>

                <?php
                echo $form->input([
                    'name' => 'page',
                    'id' => 'page',
                    'size' => '6',
                    'value' => ((isset($page)) ? $page : 1)
                ]);
                ?>

                <?php
                echo $form->submit([
                    'name' => 'submit',
                    'value' => 'View',
                    'onclick' => 'location.href=\'' . $this->getUri()->create($this->getRadix()->shortname . '/page/') . '\' + this.form.page.value + \'/\'; return false;'
                ]);
                ?>

                <a class="tooltip" href="#">[?]<span><?= _i('In Ghost Mode, only threads that contain ghost posts will be listed.') ?></span></a>

                <input type="button" value="View in Ghost Mode" onclick="location.href='<?php echo $this->getUri()->create($this->getRadix()->shortname . '/ghost') ?>' + this.form.page.value + '/'; return false;" />
            </div>
            <?php echo $form->close(); ?>
        <?php endif; ?>
        </div>
        <?php endif;
    }
}
