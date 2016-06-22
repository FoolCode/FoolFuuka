<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\Media;
use Foolz\Inet\Inet;
use Rych\ByteSize\ByteSize;

class Board extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $board = $this->getParamManager()->getParam('board');
        $controller_method = $this->getBuilderParamManager()->getParam('controller_method', 'thread');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);

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

                $num =  $op->num.($op->subnum ? '_'.$op->subnum : '');
                ?>
        <?php if ($thread_id === 0) : ?>
        <div class="thread stub stub_doc_id_<?= $op->doc_id ?>">
            <button class="btn-toggle-post" data-function="showThread" data-board="<?= $op->radix->shortname ?>" data-doc-id="<?= $op->doc_id ?>" data-thread-num="<?= $op->thread_num ?>"><i class="icon-plus"></i></button>
            <?php if ($op->email && $op->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($op->email) ?>"><?php endif; ?><span class="post_author"><?= $op->getNameProcessed() ?></span><?= ($op->getNameProcessed() && $op->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $op->getTripProcessed() ?></span><?php if ($op->email && $op->email !== 'noko') : ?></a><?php endif ?>
            (<?= ($post['omitted'] + 5).' '._i('replies') ?>)
        </div>
        <?php endif; ?>
        <article id="<?= $num ?>" class="clearfix thread doc_id_<?= $op->doc_id ?> board_<?= $op->radix->shortname ?>" data-doc-id="<?= $op->doc_id ?>" data-thread-num="<?= $op->thread_num ?>">
                <?php if ($thread_id === 0) : ?>
                <div class="stub pull-left">
                    <button class="btn-toggle-post" data-function="hideThread" data-board="<?= $op->radix->shortname ?>" data-doc-id="<?= $op->doc_id ?>"><i class="icon-minus"></i></button>
                </div>
                <?php endif; ?>
                <?php \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_op_open')->setObject($this)->setParam('board', $op->radix)->execute(); ?>
                <?php if ($op_media !== null) : ?>
                <div class="thread_image_box">
                    <?php if ($op_media->getMediaStatus($this->getRequest()) === 'banned') : ?>
                    <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png')?>" width="150" height="150" />
                    <?php elseif ($op_media->getMediaStatus($this->getRequest()) !== 'normal') : ?>
                    <a href="<?= ($op_media->getMediaLink($this->getRequest())) ? $op_media->getMediaLink($this->getRequest()) : $op_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                        <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" />
                    </a>
                    <?php else : ?>
                    <a href="<?= ($op_media->getMediaLink($this->getRequest())) ? $op_media->getMediaLink($this->getRequest()) : $op_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                        <?php if (!$this->getAuth()->hasAccess('maccess.mod') && !$op->radix->getValue('transparent_spoiler') && $op_media->spoiler) :?>
                        <div class="spoiler_box"><span class="spoiler_box_text"><?= _i('Spoiler') ?><span class="spoiler_box_text_help"><?= _i('Click to view') ?></span></div>
                        <?php else : ?>
                        <img src="<?= $op_media->getThumbLink($this->getRequest()) ?>" width="<?= $op_media->preview_w ?>" height="<?= $op_media->preview_h ?>" class="thread_image<?= ($op_media->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $op_media->media_hash ?>" />
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($op_media->getMediaStatus($this->getRequest()) !== 'banned') : ?>
                    <div class="post_file" style="padding-left: 2px;<?php if ($op_media->preview_w > 149) echo 'max-width:'.$op_media->preview_w .'px;'; ?>">
                        <?= ByteSize::formatBinary($op_media->media_size, 0) . ', ' . $op_media->media_w . 'x' . $op_media->media_h . ', '; ?><a class="post_file_filename" href="<?= ($op_media->getMediaLink($this->getRequest())) ? $op_media->getMediaLink($this->getRequest()) : $op_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank"><?= $op_media->getMediaFilenameProcessed(); ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="post_file_controls">
                        <?php if ($op_media->getMediaStatus($this->getRequest()) !== 'banned' || $this->getAuth()->hasAccess('media.see_banned')) : ?>
                        <?php if ( !$op->radix->hide_thumbnails || $this->getAuth()->hasAccess('maccess.mod')) : ?>
                            <a href="<?= $this->getUri()->create($op->radix->shortname . '/search/image/' . $op_media->getSafeMediaHash()) ?>" class="btnr parent"><?= _i('View Same') ?></a><a
                                href="http://google.com/searchbyimage?image_url=<?= $op_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                class="btnr parent">Google</a><a
                                href="http://imgops.com/<?= $op_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                class="btnr parent">ImgOps</a><a
                                href="http://iqdb.org/?url=<?= $op_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                class="btnr parent">iqdb</a><a
                                href="http://saucenao.com/search.php?url=<?= $op_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                class="btnr parent">SauceNAO</a><?php if (!$op->radix->archive || $op->radix->getValue('archive_full_images')) : ?><a
                                href="<?= $op_media->getMediaDownloadLink($this->getRequest()) ?>" download="<?= $op_media->getMediaFilenameProcessed() ?>"
                                class="btnr parent"><i class="icon-download-alt"></i></a><?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <header>
                    <div class="post_data">
                        <?php if ($op->getTitleProcessed() !== '') : ?><h2 class="post_title"><?= $op->getTitleProcessed() ?></h2><?php endif; ?>
                        <span class="post_poster_data">
                            <?php if ($op->email && $op->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($op->email) ?>"><?php endif; ?><span class="post_author"><?= $op->getNameProcessed() ?></span><?= ($op->getNameProcessed() && $op->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $op->getTripProcessed() ?></span><?php if ($op->email && $op->email !== 'noko') : ?></a><?php endif ?>

                            <?php if ($op->getPosterHashProcessed()) : ?><span class="poster_hash">ID:<?= $op->getPosterHashProcessed() ?></span><?php endif; ?>
                            <?php if ($op->capcode !== 'N') : ?>
                            <?php if ($op->capcode === 'M') : ?><span class="post_level post_level_moderator">## <?= _i('Mod') ?></span><?php endif ?>
                            <?php if ($op->capcode === 'A') : ?><span class="post_level post_level_administrator">## <?= _i('Admin') ?></span><?php endif ?>
                            <?php if ($op->capcode === 'D') : ?><span class="post_level post_level_developer">## <?= _i('Developer') ?></span><?php endif ?>
                            <?php endif; ?>
                        </span>
                        <span class="time_wrap">
                            <time datetime="<?= gmdate(DATE_W3C, $op->timestamp) ?>" class="show_time" <?php if ($op->radix->archive) : ?> title="<?= _i('4chan Time') . ': ' . $op->getFourchanDate() ?>"<?php endif; ?>><?= gmdate('D d M H:i:s Y', $op->timestamp) ?></time>
                        </span>
                        <a href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $op->thread_num)) . '#'  . $num ?>" data-post="<?= $num ?>" data-function="highlight">No.</a><a href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $op->thread_num)) . '#q' . $num ?>" data-post="<?= $num ?>" data-function="quote"><?= $num ?></a>

                        <span class="post_type">
                            <?php if ($op->poster_country !== null) : ?><span title="<?= e($op->poster_country_name) ?>" class="flag flag-<?= strtolower($op->poster_country) ?>"></span><?php endif; ?>
                            <?php if (isset($op_media) && $op_media->spoiler) : ?><i class="icon-eye-close" title="<?= htmlspecialchars(_i('The image in this post has been marked spoiler.')) ?>"></i><?php endif ?>
                            <?php if ($op->deleted && !$op->timestamp_expired) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This thread was prematurely deleted.')) ?>"></i><?php endif ?>
                            <?php if ($op->deleted && $op->timestamp_expired) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This thread was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $op->timestamp_expired))) ?>"></i><?php endif ?>
                            <?php if ($op->sticky) : ?><i class="icon-pushpin" title="<?= _i('This thread has been stickied.') ?>"></i><?php endif; ?>
                            <?php if ($op->locked) : ?><i class="icon-lock" title="<?= _i('This thread has been locked.') ?>"></i><?php endif; ?>
                        </span>

                        <span class="post_controls">
                <a href="<?= $this->getUri()->create(array($op->radix->shortname, 'thread', $num)) ?>" class="btnr parent"><?= _i('View') ?></a><a href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $num)) . '#reply' ?>" class="btnr parent"><?= _i('Reply') ?></a><?= (isset($post['omitted']) && $post['omitted'] > 50) ? '<a href="' . $this->getUri()->create($op->radix->shortname . '/last/50/' . $num) . '" class="btnr parent">' . _i('Last 50') . '</a>' : '' ?><?= ($op->radix->archive) ? '<a href="//boards.4chan.org/' . $op->radix->shortname . '/thread/' . $num . '" class="btnr parent">' . _i('Original') . '</a>' : '' ?><a href="#" class="btnr parent" data-post="<?= $op->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($op->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= _i('Report') ?></a><?php if ($this->getAuth()->hasAccess('maccess.mod') || !$op->radix->archive) : ?><a href="#" class="btnr parent" data-post="<?= $op->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($op->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= _i('Delete') ?></a><?php endif; ?>
            </span>

                        <div class="backlink_list"<?= $op->getBacklinks() ? ' style="display:block"' : '' ?>>
                            <?= _i('Quoted By:') ?> <span class="post_backlink" data-post="<?= $num ?>"><?= $op->getBacklinks() ? implode(' ', $op->getBacklinks()) : '' ?></span>
                        </div>

                        <?php if ($this->getAuth()->hasAccess('maccess.mod')) : ?>
                        <div class="btn-group" style="clear:both; padding:5px 0 0 0;">
                            <button class="btn btn-mini" data-function="activateModeration"><?= _i('Mod') ?><?php if ($op->poster_ip) echo ' ' .Inet::dtop($op->poster_ip) ?></button>
                        </div>
                        <div class="btn-group post_mod_controls" style="clear:both; padding:5px 0 0 0;">
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="toggle_sticky"><?= _i('Toggle Sticky') ?></button>
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="toggle_locked"><?= _i('Toggle Locked') ?></button>
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="delete_post"><?= _i('Delete Thread') ?></button>
                            <?php if ( !is_null($op_media)) : ?>
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op_media->media_id ?>" data-doc-id="<?= $op->doc_id ?>" data-action="delete_image"><?= _i('Delete Image') ?></button>
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op_media->media_id ?>" data-doc-id="<?= $op->doc_id ?>" data-action="ban_image_local"><?= _i('Ban Image') ?></button>
                            <button class="btn btn-mini" data-function="mod" data-board="<?= $op->radix->shortname ?>" data-id="<?= $op_media->media_id ?>" data-doc-id="<?= $op->doc_id ?>" data-action="ban_image_global"><?= _i('Ban Image Globally') ?></button>
                            <?php endif; ?>
                            <?php if ($op->poster_ip) : ?>
                            <button class="btn btn-mini" data-function="ban" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-board="<?= $op->radix->shortname ?>" data-ip="<?= Inet::dtop($op->poster_ip) ?>" data-action="ban_user"><?= _i('Ban IP:') . ' ' . Inet::dtop($op->poster_ip) ?></button>
                            <button class="btn btn-mini" data-function="searchUser" data-board="<?= $op->radix->shortname ?>" data-board-url="<?= $this->getUri()->create(array($op->radix->shortname)) ?>" data-id="<?= $op->doc_id ?>" data-poster-ip="<?= Inet::dtop($op->poster_ip) ?>"><?= _i('Search IP') ?></button>
                            <?php if ($this->getPreferences()->get('foolfuuka.sphinx.global')) : ?>
                                <button class="btn btn-mini" data-function="searchUserGlobal" data-board="<?= $op->radix->shortname ?>" data-board-url="<?= $this->getUri()->create(array($op->radix->shortname)) ?>" data-id="<?= $op->doc_id ?>" data-poster-ip="<?= Inet::dtop($op->poster_ip) ?>"><?= _i('Search IP Globally') ?></button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $op->getCommentProcessed())) echo ' shift-jis'; ?>">
                    <?= $op->getCommentProcessed() ?>
                </div>
                <div class="thread_tools_bottom">
                    <?php if (isset($post['omitted']) && $post['omitted'] > 0) : ?>
        <span class="omitted">
            <a style="display:inline-block" href="<?= $this->getUri()->create(array($op->radix->shortname, $controller_method, $op->thread_num)) ?>" data-function="expandThread" data-thread-num="<?= $op->thread_num ?>"><i class="icon icon-resize-full"></i></a>
                    <span class="omitted_text">
                <span class="omitted_posts"><?= $post['omitted'] ?></span> <?= _n('post', 'posts', $post['omitted']) ?>
                        <?php if (isset($post['images_omitted']) && $post['images_omitted'] > 0) : ?>
                        <?= _i('and') ?> <span class="omitted_images"><?= $post['images_omitted'] ?></span> <?= _n('image', 'images', $post['images_omitted']) ?>
                        <?php endif; ?>
                        <?= _n('omitted', 'omitted', $post['omitted'] + $post['images_omitted']) ?>
        </span>
                    <?php endif; ?>
                </div>

                <?php if ($op->getReports()) : ?>
                <?php foreach ($op->getReports() as $report) : ?>
                    <div class="report_reason"><?= '<strong>' . _i('Reported Reason:') . '</strong> ' . $report->getReasonProcessed() ?>
                        <br/>
                        <div class="ip_reporter">
                            <strong><?= _i('Info:') ?></strong>
                            <?= Inet::dtop($report->ip_reporter) ?>, <?= _i('Type:') ?> <?= $report->media_id !== null ? _i('media') : _i('post')?>, <?= _i('Time:')?> <?= gmdate('D M d H:i:s Y', $report->created) ?>
                            <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= htmlspecialchars($op->radix->shortname) ?>" data-action="delete_report"><?= _i('Delete Report') ?></button>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php endif; ?>
                <?php elseif (isset($post['posts'])) : ?>
        <article class="clearfix thread">
                    <?php \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_headless_open')->setObject($this)->setParam('board', array(isset($radix) ? $radix : null))->execute(); ?>
                <?php endif; ?>

            <aside class="posts">
                <?php
                if (isset($post['posts'])) :
                    $post_counter = 0;
                    $image_counter = 0;

                    $board_comment_view = $this->getBuilder()->createPartial('post', 'board_comment');

                    // reusable Comment object not to create one every loop
                    $comment = new Comment($this->getContext());
                    $comment->setControllerMethod($controller_method);
                    $media_obj = new Media($this->getContext());

                    $search = array(
                        '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
                        '/[^\S ]+\</s',  // strip whitespaces before tags, except space
                        '/(\s)+/s'       // shorten multiple whitespace sequences
                    );

                    $replace = array(
                        '>',
                        '<',
                        '\\1'
                    );

                    foreach ($post['posts'] as $p) {
                        /** @var CommentBulk $p */

                        $post_counter++;
                        if ($p->media !== null)
                            $image_counter++;

                        if ($image_counter == 150) {
                            $modifiers['lazyload'] = true;
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

                        echo preg_replace($search, $replace, $board_comment_view->build());

                        // remove extra strings from the objects
                        $board_comment_view->clearBuilt();
                        $p->comment->clean();
                        if ($p->media !== null) {
                            $p->media->clean();
                        }

                        $this->flush();
                    }

                endif; ?>
            </aside>

            <?php if ($thread_id !== 0) : ?>
            <div class="js_hook_realtimethread"></div>
            <?= $this->getBuilder()->isPartial('tools_reply_box') ? $this->getBuilder()->getPartial('tools_reply_box')->build() : '' ?>
            <?php endif; ?>
            <?php if (isset($post['op']) || isset($post['posts'])) : ?>
        </article>
        <?php endif; ?>
            <?php endforeach; ?>
        <article class="clearfix thread backlink_container">
            <div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
        </article>
        <?php
    }
}
