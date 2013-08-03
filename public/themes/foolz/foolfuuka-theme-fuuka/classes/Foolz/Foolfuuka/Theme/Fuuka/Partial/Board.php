<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Partial;

use Foolz\Inet\Inet;
use Foolz\Foolframe\Model\Legacy\Preferences;

class Board extends \Foolz\Theme\View
{
    public function toString()
    {
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $board = $this->getParamManager()->getParam('board');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);

        if ($thread_id > 0) {
            echo \Form::open(array('enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit', 'id' => 'postform'));
            echo \Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token());
            echo \Form::hidden('id', 'postform');
            echo isset($backend_vars['last_limit']) ? Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '';
        }
        ?>

        <div class="content">
        <?php
        foreach ($board->getComments() as $key => $post) :
            if (isset($post['op'])) :
                $op = $post['op'];
        ?>
            <div id="<?= $op->num ?>">
                <?php if ($op->media !== null) : ?>
                    <?php if ($op->media->getMediaStatus() !== 'banned') : ?>
                    <span><?= _i('File:') . ' ' . \Num::format_bytes($op->media->media_size, 0) . ', ' . $op->media->media_w . 'x' . $op->media->media_h . ', ' . $op->media->getMediaFilenameProcessed() ?> <?= '<!-- ' . substr($op->media->media_hash, 0, -2) . '-->' ?></span>
                        <?php if ( !$op->radix->hide_thumbnails || Auth::has_access('maccess.mod')) : ?>
                            [<a href="<?= \Uri::create($op->radix->shortname . '/search/image/' . $op->media->getSafeMediaHash()) ?>"><?= _i('View Same') ?></a>]
                            [<a href="http://google.com/searchbyimage?image_url=<?= $op->media->getThumbLink() ?>">Google</a>]
                            [<a href="http://iqdb.org/?url=<?= $op->media->getThumbLink() ?>">iqdb</a>]
                            [<a href="http://saucenao.com/search.php?url=<?= $op->media->getThumbLink() ?>">SauceNAO</a>]
                        <?php endif; ?>
                    <br />
                    <?php endif; ?>
                    <?php if ($op->media->getMediaStatus() === 'banned') : ?>
                        <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png') ?>" width="150" height="150" class="thumb"/>
                    <?php elseif ($op->media->getMediaStatus() !== 'normal') : ?>
                        <a href="<?= ($op->media->getMediaLink()) ? $op->media->getMediaLink() : $op->media->getRemoteMediaLink() ?>" rel="noreferrer">
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" class="thumb"/>
                        </a>
                    <?php else: ?>
                        <a href="<?= ($op->media->getMediaLink()) ? $op->media->getMediaLink() : $op->media->getRemoteMediaLink() ?>" rel="noreferrer">
                            <?php if (!\Auth::has_access('maccess.mod') && $op->media->spoiler) : ?>
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/spoiler.png') ?>" width="100" height="100" class="thumb" alt="[SPOILER]" />
                            <?php else: ?>
                            <img src="<?= $op->media->getThumbLink() ?>" width="<?= $op->media->preview_w ?>" height="<?= $op->media->preview_h ?>" class="thumb" alt="<?= $op->num ?>" />
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <label>
                    <input type="checkbox" name="delete[]" value="<?= $op->doc_id ?>" />
                    <span class="filetitle"><?= $op->getTitleProcessed() ?></span>
                    <span class="postername<?= ($op->capcode == 'M') ? ' mod' : '' ?><?= ($op->capcode == 'A') ? ' admin' : '' ?><?= ($op->capcode == 'D') ? ' developer' : '' ?>"><?= (($op->email && $op->email !== 'noko') ? '<a href="mailto:' . rawurlencode($op->email) . '">' . $op->getNameProcessed() . '</a>' : $op->getNameProcessed()) ?></span>
                    <span class="postertrip<?= ($op->capcode == 'M') ? ' mod' : '' ?><?= ($op->capcode == 'A') ? ' admin' : '' ?><?= ($op->capcode == 'D') ? ' developer' : '' ?>"><?= $op->getTripProcessed() ?></span>
                    <span class="poster_hash"><?php if ($op->getPosterHashProcessed()) : ?>ID:<?= $op->getPosterHashProcessed() ?><?php endif; ?></span>
                    <?php if ($op->capcode == 'M') : ?>
                        <span class="postername mod">## <?= _i('Mod') ?></span>
                    <?php endif; ?>
                    <?php if ($op->capcode == 'A') : ?>
                        <span class="postername admin">## <?= _i('Admin') ?></span>
                    <?php endif; ?>
                    <?php if ($op->capcode == 'D') : ?>
                        <span class="postername admin">## <?= _i('Developer') ?></span>
                    <?php endif; ?>
                    <?= gmdate('D d M H:i:s Y', $op->getOriginalTimestamp()) ?>
                    <?php if ($op->poster_country !== null) : ?><span class="poster_country"><span title="<?= e($op->poster_country_name) ?>" class="flag flag-<?= strtolower($op->poster_country) ?>"></span></span><?php endif; ?>
                </label>

                <?php if ($thread_id == 0) : ?>
                    <a class="js" href="<?= \Uri::create(array($op->radix->shortname, $op->_controller_method, $op->num)).'#'.$op->num ?>">No.<?= $op->num ?></a>
                <?php else : ?>
                    <a class="js" href="<?= \Uri::create(array($op->radix->shortname, $op->_controller_method, $op->num)).'#'.$op->num ?>">No.</a><a class="js" href="javascript:replyQuote('>><?= $op->num ?>\n')"><?= $op->num ?></a>
                <?php endif; ?>

                <?php if (isset($op->media) && $op->media->spoiler == 1) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/spoiler-icon.png'); ?>" alt="[SPOILER]" title="<?= _i('The image in this post has been marked as a spoiler.') ?>"/><?php endif; ?>
                <?php if ($op->deleted == 1 && $op->timestamp_expired == 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This post was deleted before its lifetime expired.') ?>"/><?php endif ?>
                <?php if ($op->deleted == 1 && $op->timestamp_expired != 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This post was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $op->timestamp_expired)) ?>"/><?php endif ?>

                [<a href="<?= \Uri::create(array($op->radix->shortname, 'thread', $op->num)) ?>"><?= _i('Reply') ?></a>]
                <?php if (isset($post['omitted']) && $post['omitted'] > 50) : ?> [<a href="<?= \Uri::create($op->radix->shortname . '/last/50/' . $op->num) ?>"><?= _i('Last 50') ?></a>]<?php endif; ?>
                <?php if ($op->radix->archive) : ?> [<a href="//boards.4chan.org/<?= $op->radix->shortname . '/res/' . $op->num ?>"><?= _i('Original') ?></a>]<?php endif; ?>

                <div class="quoted-by" style="display: <?= $op->getBacklinks() ? 'block' : 'none' ?>">
                    <?= _i('Quoted By:') ?> <?= $op->getBacklinks() ? implode(' ', $op->getBacklinks()) : '' ?>
                </div>

                <blockquote><p><?= $op->getCommentProcessed() ?></p></blockquote>
                <?php if (isset($post['omitted']) && $post['omitted'] > 0) : ?>
                <span class="omitted">
                    <?php if (isset($post['images_omitted']) && $post['images_omitted'] > 0) : ?>
                    <?= $post['omitted'] + $post['images_omitted'] . ' ' . _n('post', 'posts', $post['omitted'] + $post['images_omitted']) ?>
                    <?= ' ' . _n('omitted', 'omitted', $post['omitted'] + $post['images_omitted']) ?>.
                    <?php else : ?>
                    <?= $post['omitted'] . ' ' . _n('post', 'posts', $post['omitted']) ?>
                    <?= ' ' . _n('omitted', 'omitted', $post['omitted'] + $post['images_omitted']) ?>.
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
                if (isset($post['posts'])) {
                    $post_counter = 0;
                    $image_counter = 0;

                    $board_comment_view = $this->getBuilder()->createPartial('post', 'board_comment');

                    foreach ($post['posts'] as $p) {
                        $post_counter++;
                        if ($p->media !== null)
                            $image_counter++;

                        if ($p->thread_num == 0)
                            $p->thread_num = $p->num;

                        $board_comment_view->getParamManager()->setParams([
                            'p' => $p,
                            'modifiers' => $this->getBuilderParamManager()->getParam('modifiers', false),
                            'post_counter' => $post_counter,
                            'image_counter' => $image_counter
                        ]);

                        // refreshes the string
                        $board_comment_view->doBuild();

                        echo $board_comment_view->build();
                    }
                }
            ?>

            <?php if ($thread_id > 0) : ?>
            <?= $this->getBuilder()->isPartial('tools_reply_box') ? $this->getBuilder()->getPartial('tools_reply_box')->build() : '' ?>
            <?php endif; ?>

            <br class="newthr" />
            <hr />
        <?php endforeach; ?>
        </div>

        <?php if ($thread_id > 0) echo \Form::close(); ?>
    <?php
    }
}
