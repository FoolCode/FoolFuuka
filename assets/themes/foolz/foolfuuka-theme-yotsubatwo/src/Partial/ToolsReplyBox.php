<?php

namespace Foolz\FoolFuuka\Theme\Yotsubatwo\Partial;

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

        \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_op_open')->setObject($this)->setParam('board', $radix)->execute(); ?>

        <?= $form->open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $this->getUri()->create([$radix->shortname, 'submit'])]) ?>
        <?= $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken()); ?>
        <?= $form->hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
        <?= isset($backend_vars['last_limit']) ? $form->hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>

        <table id="reply">
            <tbody>
            <tr>
                <td><?= _i('Name') ?></td>
                <td><?php
                    echo $form->input([
                        'name' => 'name',
                        'id' => 'reply_name_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->input([
                        'name' => 'reply_bokunonome',
                        'id' => 'reply_bokunonome',
                        'value' => $user_name
                    ]);
                    ?></td>
            </tr>
            <tr>
                <td><?= _i('E-mail') ?></td>
                <td><?php
                    echo $form->input([
                        'name' => 'email',
                        'id' => 'reply_email_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->input([
                        'name' => 'reply_elitterae',
                        'id' => 'reply_elitterae',
                        'value' => $user_email
                    ]);
                    ?></td>
            </tr>
            <tr>
                <td><?= _i('Subject') ?></td>
                <td>
                    <?php
                    echo $form->input([
                        'name' => 'reply_talkingde',
                        'id' => 'reply_talkingde',
                    ]);
                    ?>

                    <?php
                    $submit_array = [
                        'data-function' => 'comment',
                        'name' => 'reply_gattai',
                        'value' => _i('Submit'),
                        'class' => 'btn',
                    ];

                    echo $form->submit($submit_array);
                    ?>

                    <?php if (!$this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
                    [ <label><?php echo $form->checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]) ?> Spoiler Image?</label> ]
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?= _i('Comment') ?></td>
                <td><?php
                    echo $form->textarea([
                        'name' => 'reply',
                        'id' => 'reply_comment_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->textarea([
                        'name' => 'reply_chennodiscursus',
                        'id' => 'reply_chennodiscursus',
                        'placeholder' => (!$radix->archive && isset($thread_dead) && $thread_dead) ? _i('This thread has entered ghost mode. Your reply will be marked as a ghost post and will only affect the ghost index.') : '',
                    ]);
                    ?></td>
            </tr>

            <?php if ($this->getPreferences()->get('foolframe.auth.recaptcha_public', false)) : ?>
            <script>
                var RecaptchaOptions = {
                    theme : 'custom',
                    custom_theme_widget: 'recaptcha_widget'
                };
            </script>
            <tr class="recaptcha_widget" style="display:none">
                <td><?= _i('Verification') ?></td>
                <td><div><p><?= e(_i('You might be a bot! Enter a reCAPTCHA to continue.')) ?></p></div>
                    <div id="recaptcha_image" style="background: #fff; border: 1px solid #ccc; padding: 3px 6px; margin: 4px 0;"></div>
                    <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
                    <div class="btn-group">
                        <a class="btn btn-mini" href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
                        <a class="recaptcha_only_if_image btn btn-mini" href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a>
                        <a class="recaptcha_only_if_audio btn btn-mini" href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a>
                        <a class="btn btn-mini" href="javascript:Recaptcha.showhelp()">Help</a>
                    </div>

                    <script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>"></script>
                    <noscript>
                        <iframe src="//www.google.com/recaptcha/api/noscript?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha_public') ?>" height="300" width="500" frameborder="0"></iframe><br>
                        <textarea name="recaptcha_challenge_field" rows="3" cols="40">
                        </textarea>
                        <input type="hidden" name="recaptcha_response_field"  value="manual_challenge">
                    </noscript></td>
            </tr>
                <?php endif; ?>
                <?php if (!$this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
            <tr>
                <td><?= _i('File') ?></td>
                <td><?php echo $form->file(['name' => 'file_image', 'id' => 'file_image']) ?></td>
            </tr>
            <tr>
                <td><?= _i('Progress') ?></td>
                <td><div class="progress progress-info progress-striped active" style="width: 300px; margin-bottom: 2px"><div class="bar" style="width: 0%"></div></div></td>
            </tr>
                <?php endif; ?>
            <tr>
                <td><?= _i('Password') ?></td>
                <td><?=  $form->password([
                    'name' => 'reply_nymphassword',
                    'id' => 'reply_nymphassword',
                    'value' => $user_pass,
                    'required' => 'required'
                ]);
                    ?> <span style="font-size: smaller;">(Password used for file deletion)</span>
                </td>
            </tr>

                <?php
                $postas = ['N' => _i('User')];

                if ($this->getAuth()->hasAccess('comment.mod_capcode')) $postas['M'] = _i('Moderator');
                if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['A'] = _i('Administrator');
                if ($this->getAuth()->hasAccess('comment.dev_capcode')) $postas['D'] = _i('Developer');
                if (count($postas) > 1) :
                    ?>
                <tr>
                    <td><?= _i('Post as') ?></td>
                    <td><?= $form->select('reply_postas', 'User', $postas, array('id' => 'reply_postas')); ?></td>
                </tr>
                    <?php endif; ?>

                <?php if ($radix->getValue('posting_rules')) : ?>
            <tr class="rules">
                <td></td>
                <td>
                    <?php
                    echo \Foolz\FoolFrame\Model\Markdown::parse($radix->getValue('posting_rules'));
                    ?>
                </td>
            </tr>
            <tr class="rules">
                <td colspan="2">
                    <div id="reply_ajax_notices"></div>
                    <?php if (isset($reply_errors)) : ?>
                    <span style="color: red"><?= $reply_errors ?></span>
                    <?php endif; ?>
                </td>
            </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= $form->close() ?>
    <?php
    }
}
