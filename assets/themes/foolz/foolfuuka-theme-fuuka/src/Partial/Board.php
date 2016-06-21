<?php

namespace Foolz\FoolFuuka\Theme\Fuuka\Partial;

use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\Media;
use Rych\ByteSize\ByteSize;

class Board extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $board = $this->getParamManager()->getParam('board');
        $controller_method = $this->getBuilderParamManager()->getParam('controller_method', 'thread');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);
        $form = $this->getForm();

        if ($thread_id > 0) {
            echo $form->open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit', 'id' => 'postform']);
            echo $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken());
            echo $form->hidden('id', 'postform');
            echo isset($backend_vars['last_limit']) ? $form->hidden('reply_last_limit', $backend_vars['last_limit'])  : '';
        }
        ?>

        <div class="content">
        <?php
        foreach ($board as $key => $post) :
            if (isset($post['op'])) :
                $op_bulk = $post['op'];
                $op = new Comment($this->getContext(), $op_bulk);
                $op->setControllerMethod($controller_method);
                if ($op_bulk->media !== null) {
                    $op_media = new Media($this->getContext(), $op_bulk);
                } else {
                    $op_media = null;
                }
        ?>
            <div id="<?= $op->num ?>">
                <?php if ($op_media !== null) : ?>
                    <?php if ($op_media->getMediaStatus($this->getRequest()) !== 'banned') : ?>
                    <span><?= _i('File:') . ' ' . ByteSize::formatBinary($op_media->media_size, 0) . ', ' . $op_media->media_w . 'x' . $op_media->media_h . ', ' . $op_media->getMediaFilenameProcessed() ?> <?= '<!-- ' . substr($op_media->media_hash, 0, -2) . '-->' ?></span>
                        <?php if ( !$op->radix->hide_thumbnails || $this->getAuth()->hasAccess('maccess.mod')) : ?>
                            [<a href="<?= $this->getUri()->create($op->radix->shortname . '/search/image/' . $op_media->getSafeMediaHash()) ?>"><?= _i('View Same') ?></a>]
                            [<a href="http://google.com/searchbyimage?image_url=<?= $op_media->getThumbLink($this->getRequest()) ?>">Google</a>]
			    [<a href="http://imgops.com/<?= $op_media->getThumbLink($this->getRequest()) ?>">ImgOps</a>]
                            [<a href="http://iqdb.org/?url=<?= $op_media->getThumbLink($this->getRequest()) ?>">iqdb</a>]
                            [<a href="http://saucenao.com/search.php?url=<?= $op_media->getThumbLink($this->getRequest()) ?>">SauceNAO</a>]
                        <?php endif; ?>
                    <br />
                    <?php endif; ?>
                    <?php if ($op_media->getMediaStatus($this->getRequest()) === 'banned') : ?>
                        <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png') ?>" width="150" height="150" class="thumb"/>
                    <?php elseif ($op_media->getMediaStatus($this->getRequest()) !== 'normal') : ?>
                        <a href="<?= ($op_media->getMediaLink($this->getRequest())) ? $op_media->getMediaLink($this->getRequest()) : $op_media->getRemoteMediaLink($this->getRequest()) ?>" rel="noreferrer">
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" class="thumb"/>
                        </a>
                    <?php else: ?>
                        <a href="<?= ($op_media->getMediaLink($this->getRequest())) ? $op_media->getMediaLink($this->getRequest()) : $op_media->getRemoteMediaLink($this->getRequest()) ?>" rel="noreferrer">
                            <?php if (!$this->getAuth()->hasAccess('maccess.mod') && $op_media->spoiler) : ?>
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/spoiler.png') ?>" width="100" height="100" class="thumb" alt="[SPOILER]" />
                            <?php else: ?>
                            <img src="<?= $op_media->getThumbLink($this->getRequest()) ?>" width="<?= $op_media->preview_w ?>" height="<?= $op_media->preview_h ?>" class="thumb" alt="<?= $op->num ?>" />
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
                    <a class="js" href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $op->num)).'#'.$op->num ?>">No.<?= $op->num ?></a>
                <?php else : ?>
                    <a class="js" href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $op->num)).'#'.$op->num ?>">No.</a><a class="js" href="javascript:replyQuote('>><?= $op->num ?>\n')"><?= $op->num ?></a>
                <?php endif; ?>

                <?php if (isset($op_media) && $op_media->spoiler == 1) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/spoiler-icon.png'); ?>" alt="[SPOILER]" title="<?= _i('The image in this post has been marked spoiler.') ?>"><?php endif; ?>
                <?php if ($op->deleted == 1 && $op->timestamp_expired == 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This thread was prematurely deleted.') ?>"><?php endif ?>
                <?php if ($op->deleted == 1 && $op->timestamp_expired != 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This thread was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $op->timestamp_expired)) ?>"><?php endif ?>
                <?php if ($op->sticky == 1) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/sticky-icon.png') ?>" alt="[STICKY]" title="<?= _i('This thread has been stickied.') ?>"><?php endif; ?>
                <?php if ($op->locked == 1) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/locked-icon.png') ?>" alt="[LOCKED]" title="<?= _i('This thread has been locked.') ?>"><?php endif; ?>

                [<a href="<?= $this->getUri()->create(array($op->radix->shortname, 'thread', $op->num)) ?>"><?= _i('Reply') ?></a>]
                <?php if (isset($post['omitted']) && $post['omitted'] > 50) : ?> [<a href="<?= $this->getUri()->create($op->radix->shortname . '/last/50/' . $op->num) ?>"><?= _i('Last 50') ?></a>]<?php endif; ?>
                <?php if ($op->radix->archive) : ?> [<a href="//boards.4chan.org/<?= $op->radix->shortname . '/thread/' . $op->num ?>"><?= _i('Original') ?></a>]<?php endif; ?>

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

                    // reusable Comment object not to create one every loop
                    $comment = new Comment($this->getContext());
                    $comment->setControllerMethod($controller_method);
                    $media_obj = new Media($this->getContext());

                    foreach ($post['posts'] as $p) {
                        /** @var CommentBulk $p */

                        $post_counter++;
                        if ($p->media !== null) {
                            $image_counter++;
                        }

                        $comment->setBulk($p);
                        // set the $media to null and leave the Media object in existence
                        if ($p->media !== null) {
                            $media_obj->setBulk($p);
                            $media = $media_obj;
                        } else {
                            $media = null;
                        }

                        $board_comment_view->getParamManager()->setParams([
                            'p' => $comment,
                            'p_media' => $media,
                            'modifiers' => $this->getBuilderParamManager()->getParam('modifiers', false),
                            'post_counter' => $post_counter,
                            'image_counter' => $image_counter
                        ]);

                        // refreshes the string
                        $board_comment_view->doBuild();

                        echo $board_comment_view->build();

                        // remove extra strings from the objects
                        $board_comment_view->clearBuilt();
                        $p->comment->clean();
                        if ($p->media !== null) {
                            $p->media->clean();
                        }
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

        <?php if ($thread_id > 0) echo $form->close(); ?>
    <?php
    }
}
