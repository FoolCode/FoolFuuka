<?php

namespace Foolz\FoolFuuka\Theme\Fuuka\Partial;

class ToolsReplyBox extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $backend_vars = $this->getBuilderParamManager()->getParam('backend_vars');
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $user_name = $this->getBuilderParamManager()->getParam('user_name');
        $user_pass = $this->getBuilderParamManager()->getParam('user_pass');
        $user_email = $this->getBuilderParamManager()->getParam('user_email');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);
        $reply_errors = $this->getBuilderParamManager()->getParam('reply_errors', false);
        $form = $this->getForm();

        if (!$thread_id && !$radix->archive) : ?>
            <?= $form->open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $this->getUri()->create([$radix->shortname, 'submit']), 'id' => 'postform']) ?>
            <?= $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken()); ?>
            <?= isset($backend_vars['last_limit']) ? $form->hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>
        <table style="margin-left: auto; margin-right: auto">
            <tbody>
            <tr>
                <td class="subreply">
                    <div class="theader">
                        <?= _i('Create New Thread') ?>
                    </div>
                    <?php if (isset($reply_errors)) : ?>
                    <span style="color:red"><?= $reply_errors ?></span>
                    <?php endif; ?>
                    <table>
                        <tbody>
                        <tr>
                            <td class="postblock"><?= _i('Name') ?></td>
                            <td><?php echo $form->input(['name' => 'NAMAE', 'size' => 63, 'value' => $user_name]) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('E-mail') ?></td>
                            <td><?php echo $form->input(['name' => 'MERU', 'size' => 63, 'value' => $user_email]) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Subject') ?></td>
                            <td><?php echo $form->input(['name' => 'subject', 'size' => 63]) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Comment') ?></td>
                            <td><?php echo $form->textarea(['name' => 'KOMENTO', 'cols' => 48, 'rows' => 4]) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('File') ?></td>
                            <td><?php echo $form->file(['name' => 'file_image', 'id' => 'file_image']) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Spoiler') ?></td>
                            <td><?php echo $form->checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]) ?></td>
                        </tr>
                        <tr>
                            <td class="postblock"><?= _i('Password') ?> <a class="tooltip" href="#">[?] <span><?= _i('This is used for file and post deletion.') ?></span></a></td>
                            <td><?php echo $form->password(['name' => 'delpass', 'size' => 24, 'value' => $user_pass]) ?></td>
                        </tr>
                            <?php if ($this->getPreferences()->get('foolframe.auth.recaptcha_public', false)) : ?>
                        <tr id="recaptcha_widget">
                            <td class="postblock"><?= _i('Verification') ?><br/>(<?= _i('Optional') ?>)</td>
                            <td>
                                <script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>"></script>
                                <noscript>
                                    <iframe src="//www.google.com/recaptcha/api/noscript?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>" height="300" width="500" frameborder="0"></iframe><br/>
                                    <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
                                    <input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
                                </noscript>
                            </td>
                        </tr>
                            <?php endif; ?>
                            <?php
                            $postas = ['N' => _i('User')];

                            if ($this->getAuth()->hasAccess('comment.mod_capcode')) $postas['M'] = _i('Moderator');
                            if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['A'] = _i('Moderator');
                            if ($this->getAuth()->hasAccess('comment.dev_capcode')) $postas['D'] = _i('Developer');
                            if (count($postas) > 1) :
                                ?>
                            <tr>
                                <td class="postblock"><?= _i('Post As') ?></td>
                                <td>
                                    <?= $form->select('reply_postas', 'User', $postas, ['id' => 'reply_postas']); ?>
                                </td>
                            </tr>
                                <?php endif; ?>
                        <tr>
                            <td class="postblock"><?= _i('Action') ?></td>
                            <td>
                                <?php
                                echo $form->hidden('parent', 0);
                                echo $form->hidden('MAX_FILE_SIZE', 3072);
                                echo $form->submit([
                                    'name' => 'reply_action',
                                    'value' => 'Submit'
                                ]);
                                ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
            <?= $form->close() ?>

        <hr/>
        <?php endif; ?>

    <?php if ($thread_id) : ?>
    <table>
        <tbody>
        <tr>
            <td class="doubledash">&gt;&gt;</td>
            <td class="subreply">
                <div class="theader">
                    <?= _i('Reply to Thread') ?> <a class="tooltip-red" href="#">[?] <span><?= _i("Don't expect anything heroic. This post will not be posted to any other board.") ?></span></a>
                </div>
                <?php if (isset($reply_errors)) : ?>
                <span style="color:red"><?= $reply_errors ?></span>
                <?php endif; ?>
                <span><?= (!$this->getRadix()->archive && isset($thread_dead) && $thread_dead) ? _i('This thread has entered ghost mode. Your reply will be marked as a ghost post and will only affect the ghost index.') : '' ?></span>
                <table>
                    <tbody>
                    <tr>
                        <td class="postblock"><?= _i('Name') ?></td>
                        <td><?php echo $form->input(['name' => 'NAMAE', 'size' => 63, 'value' => $user_name]); ?></td>
                    </tr>
                    <tr>
                        <td class="postblock"><?= _i('E-mail') ?></td>
                        <td><?php echo $form->input(['name' => 'MERU', 'size' => 63, 'value' => $user_email]); ?></td>
                    </tr>
                    <tr>
                        <td class="postblock"><?= _i('Subject') ?></td>
                        <td><?php echo $form->input(['name' => 'subject', 'size' => 63]); ?></td>
                    </tr>
                    <tr>
                        <td class="postblock"><?= _i('Comment') ?></td>
                        <td><?php echo $form->textarea(['name' => 'KOMENTO', 'cols' => 48, 'rows' => 4]); ?></td>
                    </tr>
                        <?php if (!$this->getRadix()->archive) : ?>
                    <tr>
                        <td class="postblock"><?= _i('File') ?></td>
                        <td><?php echo $form->file(['name' => 'file_image', 'id' => 'file_image']); ?></td>
                    </tr>
                    <tr>
                        <td class="postblock"><?= _i('Spoiler') ?></td>
                        <td><?php echo $form->checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]); ?></td>
                    </tr>
                        <?php endif; ?>
                    <tr>
                        <td class="postblock"><?= _i('Password') ?> <a class="tooltip" href="#">[?] <span><?= _i('This is used for file and post deletion.') ?></span></a></td>
                        <td><?php echo $form->password(['name' => 'delpass', 'size' => 24, 'value' => $user_pass]); ?></td>
                    </tr>
                        <?php if ($this->getPreferences()->get('foolframe.auth.recaptcha_public', false)) : ?>
                    <tr id="recaptcha_widget">
                        <td class="postblock"><?= _i('Verification') ?><br/>(<?= _i('Optional') ?>)</td>
                        <td>
                            <script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>"></script>
                            <noscript>
                                <iframe src="//www.google.com/recaptcha/api/noscript?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>" height="300" width="500" frameborder="0"></iframe><br/>
                                <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
                                <input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
                            </noscript>
                        </td>
                    </tr>
                        <?php endif; ?>
                        <?php
                        $postas = ['N' => _i('User')];

                        if ($this->getAuth()->hasAccess('comment.mod_capcode')) $postas['M'] = _i('Moderator');
                        if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['A'] = _i('Moderator');
                        if ($this->getAuth()->hasAccess('comment.dev_capcode')) $postas['D'] = _i('Developer');
                        if (count($postas) > 1) :
                            ?>
                        <tr>
                            <td class="postblock"><?= _i('Post As') ?></td>
                            <td>
                                <?= $form->select('reply_postas', 'User', $postas, ['id' => 'reply_postas']); ?>
                            </td>
                        </tr>
                            <?php endif; ?>
                    <tr>
                        <td class="postblock"><?= _i('Action') ?></td>
                        <td>
                            <?php
                            echo $form->hidden('parent', $thread_id);
                            echo $form->hidden('MAX_FILE_SIZE', 3072);
                            echo $form->submit([
                                'name' => 'reply_action',
                                'value' => 'Submit'
                            ]);
                            echo $form->submit([
                                'name' => 'reply_delete',
                                'value' => 'Delete Selected Posts'
                            ]);
                            echo $form->submit([
                                'name' => 'reply_report',
                                'value' => 'Report Selected Posts'
                            ]);
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
    <?php endif;
    }
}
