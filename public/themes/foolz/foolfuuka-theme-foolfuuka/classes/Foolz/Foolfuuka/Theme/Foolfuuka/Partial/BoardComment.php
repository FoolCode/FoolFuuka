<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

use Foolz\Inet\Inet;
use Foolz\Foolframe\Model\Preferences;

class BoardComment extends \Foolz\Theme\View
{
    public static $permissions = null;

    public function __construct()
    {
        if (static::$permissions === null) {
            static::$permissions = [
                'maccess.mod' => \Auth::has_access('maccess.mod'),
                'media.see_hidden' => \Auth::has_access('media.see_hidden'),
                'media.see_banned' => \Auth::has_access('media.see_banned'),
                'comment.passwordless_deletion' => \Auth::has_access('comment.passwordless_deletion'),
                'foolfuuka.sphinx.global' => \Preferences::get('foolfuuka.sphinx.global')
            ];
        }
    }

    public function toString()
    {
        $p = $this->getParamManager()->getParam('p');

        if ($this->getParamManager()->getParam('modifiers', false)) {
            $modifiers = $this->getParamManager()->getParam('modifiers');
        }

        $perm = static::$permissions;

        $num = $p->num . ( $p->subnum ? '_' . $p->subnum : '' );

        ?>
        <div class="post stub stub_doc_id_<?= $p->doc_id ?>">
                <button class="btn-toggle-post" data-function="showPost" data-board="<?= $p->radix->shortname ?>"  data-doc-id="<?= $p->doc_id ?>" data-thread-num="<?= $p->thread_num ?>"><i class="icon-plus"></i></button>
                <?php if ($p->email && $p->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($p->email) ?>"><?php endif; ?><span class="post_author"><?= $p->getNameProcessed() ?></span><?= ($p->getNameProcessed() && $p->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $p->getTripProcessed() ?></span><?php if ($p->email && $p->email !== 'noko') : ?></a><?php endif ?>
        </div>
        <article class="post doc_id_<?= $p->doc_id ?><?php if ($p->subnum > 0) : ?> post_ghost<?php endif; ?><?php if ($p->thread_num === $p->num) : ?> post_is_op<?php endif; ?><?php if ( !is_null($p->media)) : ?> has_image<?php endif; ?>" id="<?= $num ?>">
            <div class="stub pull-left">
                <button class="btn-toggle-post" data-function="hidePost" data-board="<?= $p->radix->shortname ?>" data-doc-id="<?= $p->doc_id ?>"><i class="icon-minus"></i></button>
            </div>
            <div class="post_wrapper">
                <?php if ($p->media !== null) : ?>
                <div class="post_file">
                    <span class="post_file_controls">
                    <?php if ($p->media->getMediaStatus() !== 'banned' || $perm['media.see_hidden']) : ?>
                        <?php if ( !$p->radix->hide_thumbnails || $perm['media.see_hidden']) : ?>
                        <?php if ($p->media->total > 1) : ?><a href="<?= \Uri::create(((isset($modifiers['post_show_board_name']) && $modifiers['post_show_board_name']) ? '_' : $p->radix->shortname) . '/search/image/' . $p->media->getSafeMediaHash()) ?>" class="btnr parent"><?= _i('View Same') ?></a><?php endif; ?><a
                            href="http://google.com/searchbyimage?image_url=<?= $p->media->getThumbLink() ?>" target="_blank" class="btnr parent">Google</a><a
                            href="http://iqdb.org/?url=<?= $p->media->getThumbLink() ?>" target="_blank" class="btnr parent">iqdb</a><a
                            href="http://saucenao.com/search.php?url=<?= $p->media->getThumbLink() ?>" target="_blank" class="btnr parent">SauceNAO</a>
                        <?php endif; ?>
                    <?php endif ?>
                    </span>
                    <?php if ($p->media->getMediaStatus() !== 'banned' || $perm['media.see_banned']) : ?>
                    <?php if (mb_strlen($p->media->getMediaFilenameProcessed()) > 38) : ?>
                        <span class="post_file_filename" rel="tooltip" title="<?= htmlspecialchars($p->media->media_filename) ?>">
                            <?= mb_substr($p->media->getMediaFilenameProcessed(), 0, 32) . ' (...)' . mb_substr($p->media->getMediaFilenameProcessed(), mb_strrpos($p->media->getMediaFilenameProcessed(), '.')) . ', ' ?>
                        </span>
                    <?php else: ?>
                        <?= $p->media->getMediaFilenameProcessed() . ', ' ?>
                    <?php endif; ?>

                    <span class="post_file_metadata">
                        <?= \Num::format_bytes($p->media->media_size, 0) . ', ' . $p->media->media_w . 'x' . $p->media->media_h ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="thread_image_box">
                    <?php if ($p->media->getMediaStatus() === 'banned') : ?>
                        <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png') ?>" width="150" height="150" />
                    <?php elseif ($p->media->getMediaStatus() !== 'normal'): ?>
                        <a href="<?= ($p->media->getMediaLink()) ? $p->media->getMediaLink() : $p->media->getRemoteMediaLink() ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" />
                        </a>
                    <?php else: ?>
                        <a href="<?= ($p->media->getMediaLink()) ? $p->media->getMediaLink() : $p->media->getRemoteMediaLink() ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                            <?php if (!$perm['maccess.mod'] && !$p->radix->getValue('transparent_spoiler') && $p->media->spoiler) :?>
                            <div class="spoiler_box"><span class="spoiler_box_text"><?= _i('Spoiler') ?><span class="spoiler_box_text_help"><?= _i('Click to view') ?></span></div>
                            <?php elseif (isset($modifiers['lazyload']) && $modifiers['lazyload'] == true) : ?>
                            <img src="<?= \Uri::base() . $this->getAssetManager()->getAssetLink('images/transparent_pixel.png') ?>" data-original="<?= $p->media->getThumbLink() ?>" width="<?= $p->media->preview_w ?>" height="<?= $p->media->preview_h ?>" class="lazyload post_image<?= ($p->media->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media->media_hash ?>" />
                            <noscript>
                                <a href="<?= ($p->media->getMediaLink()) ? $p->media->getMediaLink() : $p->media->getRemoteMediaLink() ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                                    <img src="<?= $p->media->getThumbLink() ?>" style="margin-left: -<?= $p->media->preview_w ?>px" width="<?= $p->media->preview_w ?>" height="<?= $p->media->preview_h ?>" class="lazyload post_image<?= ($p->media->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media->media_hash ?>" />
                                </a>
                            </noscript>
                            <?php else : ?>
                            <img src="<?= $p->media->getThumbLink() ?>" width="<?= $p->media->preview_w ?>" height="<?= $p->media->preview_h ?>" class="lazyload post_image<?= ($p->media->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media->media_hash ?>" />
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <header>
                    <div class="post_data">
                        <?php if (isset($modifiers['post_show_board_name']) && $modifiers['post_show_board_name']) : ?>
                        <span class="post_show_board">/<?= $p->radix->shortname ?>/</span>
                        <?php endif; ?>

                        <?php if ($p->getTitleProcessed() !== '') : ?><h2 class="post_title"><?= $p->getTitleProcessed() ?></h2><?php endif; ?>
                        <span class="post_poster_data">
                            <?php if ($p->email && $p->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($p->email) ?>"><?php endif; ?><span class="post_author"><?= $p->getNameProcessed() ?></span><?= ($p->getNameProcessed() && $p->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $p->getTripProcessed() ?></span><?php if ($p->email && $p->email !== 'noko') : ?></a><?php endif ?>

                            <?php if ($p->getPosterHashProcessed()) : ?><span class="poster_hash">ID:<?= $p->getPosterHashProcessed() ?></span><?php endif; ?>
                            <?php if ($p->capcode != 'N') : ?>
                                <?php if ($p->capcode == 'M') : ?><span class="post_level post_level_moderator">## <?= _i('Mod') ?></span><?php endif ?>
                                <?php if ($p->capcode == 'A') : ?><span class="post_level post_level_administrator">## <?= _i('Admin') ?></span><?php endif ?>
                                <?php if ($p->capcode == 'D') : ?><span class="post_level post_level_developer">## <?= _i('Developer') ?></span><?php endif ?>
                            <?php endif; ?>
                        </span>
                        <span class="time_wrap">
                            <time datetime="<?= gmdate(DATE_W3C, $p->timestamp) ?>" <?php if ($p->radix->archive) : ?> title="<?= _i('4chan Time') . ': ' . $p->getFourchanDate() ?>"<?php endif; ?>><?= gmdate('D d M Y H:i:s', $p->timestamp) ?></time>
                        </span>
                        <a href="<?= \Uri::create([$p->radix->shortname, $p->_controller_method, $p->thread_num]) . '#'  . $num ?>" data-post="<?= $num ?>" data-function="highlight">No.</a><a href="<?= \Uri::create([$p->radix->shortname, $p->_controller_method, $p->thread_num]) . '#q' . $num ?>" data-post="<?= str_replace('_', ',', $num) ?>" data-function="quote"><?= str_replace('_', ',', $num) ?></a>

                        <span class="post_type">
                            <?php if ($p->poster_country !== null) : ?><span title="<?= e($p->poster_country_name) ?>" class="flag flag-<?= strtolower($p->poster_country) ?>"></span><?php endif; ?>
                            <?php if ($p->subnum > 0)   : ?><i class="icon-comment-alt" title="<?= htmlspecialchars(_i('This post was submitted as a "ghost" reply.')) ?>"></i><?php endif ?>
                            <?php if (isset($p->media) && $p->media->spoiler == 1) : ?><i class="icon-eye-close" title="<?= htmlspecialchars(_i('The image in this post has been marked as a spoiler.')) ?>"></i><?php endif ?>
                            <?php if ($p->deleted == 1 && $p->timestamp_expired == 0) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This post was deleted before its lifetime expired.')) ?>"></i><?php endif ?>
                            <?php if ($p->deleted == 1 && $p->timestamp_expired != 0) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This post was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $p->timestamp_expired))) ?>"></i><?php endif ?>
                        </span>

                        <span class="post_controls">
                            <?php if (isset($modifiers['post_show_view_button'])) : ?><a href="<?= \Uri::create($p->radix->shortname . '/thread/' . $p->thread_num) . '#' . $num ?>" class="btnr parent"><?= _i('View') ?></a><?php endif; ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= _i('Report') ?></a><?php if ($p->subnum > 0 || $perm['comment.passwordless_deletion'] || !$p->radix->archive) : ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= _i('Delete') ?></a><?php endif; ?>
                        </span>
                    </div>
                </header>
                <div class="backlink_list"<?= $p->getBacklinks() ? ' style="display:block"' : '' ?>>
                    <?= _i('Quoted By:') ?> <span class="post_backlink" data-post="<?= $p->num ?>"><?= $p->getBacklinks() ? implode(' ', $p->getBacklinks()) : '' ?></span>
                </div>
                <div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $p->getCommentProcessed())) echo ' shift-jis'; ?>">
                    <?= $p->getCommentProcessed() ?>
                </div>
                <?php if ($perm['maccess.mod']) : ?>
                <div class="btn-group" style="clear:both; padding:5px 0 0 0;">
                    <button class="btn btn-mini" data-function="activateModeration"><?= _i('Mod') ?><?php if ($p->poster_ip) echo ' ' .Inet::dtop($p->poster_ip) ?></button>
                </div>
                <div class="btn-group post_mod_controls" style="clear:both; padding:5px 0 0 5px;">
                    <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-board-url="<?= \Uri::create([$p->radix->shortname]) ?>" data-id="<?= $p->doc_id ?>" data-action="delete_post"><?= _i('Delete Post') ?></button>
                    <?php if ( !is_null($p->media)) : ?>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-action="delete_image"><?= _i('Delete Image') ?></button>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-action="ban_image_local"><?= _i('Ban Image') ?></button>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-action="ban_image_global"><?= _i('Ban Image Globally') ?></button>
                    <?php endif; ?>
                    <?php if ($p->poster_ip) : ?>
                        <button class="btn btn-mini" data-function="ban" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-board="<?= $p->radix->shortname ?>" data-ip="<?= Inet::dtop($p->poster_ip) ?>" data-action="ban_user"><?= _i('Ban IP:') . ' ' . Inet::dtop($p->poster_ip) ?></button>
                        <button class="btn btn-mini" data-function="searchUser" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= Inet::dtop($p->poster_ip) ?>"><?= _i('Search IP') ?></button>
                        <?php if ($perm['foolfuuka.sphinx.global']) : ?>
                            <button class="btn btn-mini" data-function="searchUserGlobal" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= Inet::dtop($p->poster_ip) ?>"><?= _i('Search IP Globally') ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($p->getReports()) : ?>
                    <?php foreach ($p->getReports() as $report) : ?>
                        <div class="report_reason"><?= '<strong>' . _i('Reported Reason:') . '</strong> ' . $report->getReasonProcessed() ?>
                            <br/>
                            <div class="ip_reporter">
                                <strong><?= _i('Info:') ?></strong>
                                <?= Inet::dtop($report->ip_reporter) ?>, <?= _i('Type:') ?> <?= $report->media_id !== null ? _i('media') : _i('post')?>, <?= _i('Time:')?> <?= gmdate('D M d H:i:s Y', $report->created) ?>
                                <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-action="delete_report"><?= _i('Delete Report') ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
}
