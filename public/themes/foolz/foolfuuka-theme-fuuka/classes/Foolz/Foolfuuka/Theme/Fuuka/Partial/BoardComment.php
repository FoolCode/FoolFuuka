<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Partial;

class BoardComment extends \Foolz\Foolfuuka\View\View
{

    public function toString()
    {
        $p = $this->getParamManager()->getParam('p');

        if ($this->getParamManager()->getParam('modifiers', false)) {
            $modifiers = $this->getParamManager()->getParam('modifiers');
        }
        ?>

        <table>
            <tbody>
                <tr>
                    <td class="doubledash">&gt;&gt;</td>
                    <td class="<?= ($p->subnum > 0) ? 'subreply' : 'reply' ?>" id="<?= $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
                        <label>
                            <input type="checkbox" name="delete[]" value="<?= $p->doc_id ?>"/>
                            <?php if (isset($modifiers['post_show_board_name']) &&  $modifiers['post_show_board_name']): ?><span class="post_show_board">/<?= $p->radix->shortname ?>/</span><?php endif; ?>
                            <span class="filetitle"><?= $p->getTitleProcessed() ?></span>
                            <span class="postername<?= ($p->capcode == 'M') ? ' mod' : '' ?><?= ($p->capcode == 'A') ? ' admin' : '' ?><?= ($p->capcode == 'D') ? ' developer' : '' ?>"><?= (($p->email && $p->email !== 'noko') ? '<a href="mailto:' . rawurlencode($p->email) . '">' . $p->getNameProcessed() . '</a>' : $p->getNameProcessed()) ?></span>
                            <span class="postertrip<?= ($p->capcode == 'M') ? ' mod' : '' ?><?= ($p->capcode == 'A') ? ' admin' : '' ?><?= ($p->capcode == 'D') ? ' developer' : '' ?>"><?= $p->getTripProcessed() ?></span>
                            <span class="poster_hash"><?php if ($p->getPosterHashProcessed()) : ?>ID:<?= $p->getPosterHashProcessed() ?><?php endif; ?></span>
                            <?php if ($p->capcode == 'M') : ?>
            <span class="postername mod">## <?= _i('Mod') ?></span>
            <?php endif ?>
                            <?php if ($p->capcode == 'A') : ?>
            <span class="postername admin">## <?= _i('Admin') ?></span>
            <?php endif ?>
                            <?php if ($p->capcode == 'D') : ?>
            <span class="postername admin">## <?= _i('Developers') ?></span>
            <?php endif ?>
                            <?= gmdate('D d M H:i:s Y', $p->getOriginalTimestamp()) ?>
                            <?php if ($p->poster_country !== null) : ?><span class="poster_country"><span title="<?= e($p->poster_country_name) ?>" class="flag flag-<?= strtolower($p->poster_country) ?>"></span></span><?php endif; ?>
                        </label>
                        <?php if (!$this->getBuilderParamManager()->getParam('thread_id', 0)) : ?>
                        <a class="js" href="<?= $this->getUri()->create([$p->radix->shortname, $p->_controller_method, $p->thread_num]) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.<?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
                        <?php else : ?>
                        <a class="js" href="<?= $this->getUri()->create([$p->radix->shortname, $p->_controller_method, $p->thread_num]) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.</a><a class="js" href="javascript:replyQuote('>><?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>\n')"><?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
                        <?php endif; ?>

                        <?php if ($p->subnum > 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/communicate-icon.png'); ?>" alt="[INTERNAL]" title="<?= _i('This post was submitted as a "ghost" reply.') ?>"/><?php endif ?>
                        <?php if (isset($p->media) && $p->media->spoiler == 1) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/spoiler-icon.png'); ?>" alt="[SPOILER]" title="<?= _i('The image in this post has been marked spoiler.') ?>"/><?php endif ?>
                        <?php if ($p->deleted == 1 && $p->timestamp_expired == 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This post was prematurely deleted.') ?>"/><?php endif ?>
                        <?php if ($p->deleted == 1 && $p->timestamp_expired != 0) : ?><img class="inline" src="<?= $this->getAssetManager()->getAssetLink('images/icons/file-delete-icon.png'); ?>" alt="[DELETED]" title="<?= _i('This post was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $p->timestamp_expired)) ?>"/><?php endif ?>

                        <?php if (isset($modifiers['post_show_view_button'])) : ?>[<a class="btnr" href="<?= $this->getUri()->create([$p->radix->shortname, 'thread', $p->thread_num]) . '#' . $p->num . (($p->subnum) ? '_' . $p->subnum : '') ?>">View</a>]<?php endif; ?>

                        <br/>
                        <?php if ($p->media !== null) : ?>
            <?php if ($p->media->getMediaStatus($this->getRequest()) !== 'banned') : ?>
                <span>
                                <?= _i('File:') . ' ' . \Num::format_bytes($p->media->media_size, 0) . ', ' . $p->media->media_w . 'x' . $p->media->media_h . ', ' . $p->media->getMediaFilenameProcessed(); ?>
                    <?= '<!-- ' . substr($p->media->media_hash, 0, -2) . '-->' ?>
                            </span>

                <?php if (!$p->radix->hide_thumbnails || $this->getAuth()->hasAccess('maccess.mod')) : ?>
                    [<a href="<?= $this->getUri()->create($p->radix->shortname . '/search/image/' . $p->media->getSafeMediaHash()) ?>"><?= _i('View Same') ?></a>]
                    [<a href="http://google.com/searchbyimage?image_url=<?= $p->media->getThumbLink($this->getRequest()) ?>">Google</a>]
                    [<a href="http://iqdb.org/?url=<?= $p->media->getThumbLink($this->getRequest()) ?>">iqdb</a>]
                    [<a href="http://saucenao.com/search.php?url=<?= $p->media->getThumbLink($this->getRequest()) ?>">SauceNAO</a>]
                    <?php endif; ?>
                <br />
                <?php endif; ?>
            <?php if ($p->media->getMediaStatus($this->getRequest()) === 'banned') : ?>
                <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png') ?>" width="150" height="150" class="thumb"/>
                <?php elseif ($p->media->getMediaStatus($this->getRequest()) !== 'normal'): ?>
                <a href="<?= ($p->media->getMediaLink($this->getRequest())) ? $p->media->getMediaLink($this->getRequest()) : $p->media->getRemoteMediaLink($this->getRequest()) ?>" rel="noreferrer">
                    <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" class="thumb"/>
                </a>
                <?php else: ?>
                <a href="<?= ($p->media->getMediaLink($this->getRequest())) ? $p->media->getMediaLink($this->getRequest()) : $p->media->getRemoteMediaLink($this->getRequest()) ?>" rel="noreferrer">
                    <?php if (!$this->getAuth()->hasAccess('maccess.mod') && $p->media->spoiler) : ?>
                    <img src="<?= $this->getAssetManager()->getAssetLink('images/spoiler.png') ?>" width="100" height="100" class="thumb" alt="[SPOILER]" />
                    <?php else: ?>
                    <img src="<?= $p->media->getThumbLink($this->getRequest()) ?>" alt="<?= $p->num ?>" width="<?= $p->media->preview_w ?>" height="<?= $p->media->preview_h ?>" class="thumb" />
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
                        <div class="quoted-by" style="display: <?= $p->getBacklinks() ? 'block' : 'none' ?>">
                            <?= _i('Quoted By:') ?> <?= $p->getBacklinks() ? implode(' ', $p->getBacklinks()) : '' ?>
                        </div>
                        <blockquote><p><?= $p->getCommentProcessed() ?></p></blockquote>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
