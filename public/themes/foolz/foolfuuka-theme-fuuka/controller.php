<?php

namespace Foolz\Foolfuuka\Themes\Fuuka\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Chan extends \Foolz\Foolfuuka\Controller\Chan
{
    /**
     * @param int $page
     */
    public function radix_page($page = 1)
    {
        $options = [
            'per_page' => 24,
            'per_thread' => 5,
            'order' => ($this->_radix->archive ? 'by_thread' : 'by_post')
        ];

        return $this->latest($page, $options);
    }

    public function radix_gallery($page = 1)
    {
        throw new NotFoundHttpException;
    }

    /**
     * @return bool
     */
    public function radix_submit()
    {
        // adapter
        if (!\Input::post()) {
            return $this->error(_i('You aren\'t sending the required fields for creating a new message.'));
        }

        if (!\Security::check_token()) {
            return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
        }

        die('here');
        if (\Input::post('reply_delete')) {
            foreach (\Input::post('delete') as $idx => $doc_id) {
                try {
                    $comments = \Board::forge()
                        ->getPost()
                        ->setOptions('doc_id', $doc_id)
                        ->setRadix($this->_radix)
                        ->getComments();

                    $comment = current($comments);
                    $comment->delete(\Input::post('delpass'));
                } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
                    return $this->error($e->getMessage(), 404);
                } catch (\Foolz\Foolfuuka\Model\CommentDeleteWrongPassException $e) {
                    return $this->error($e->getMessage(), 404);
                }
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', \Uri::create([$this->_radix->shortname, 'thread', $comment->thread_num]));
            $this->builder->getProps()->addTitle(_i('Redirecting'));

            return new Response($this->builder->build());
        }

        if (\Input::post('reply_report')) {

            foreach (\Input::post('delete') as $idx => $doc_id) {
                try {
                    \Report::add($this->_radix, $doc_id, \Input::post('KOMENTO'));
                } catch (\Foolz\Foolfuuka\Model\ReportException $e) {
                    return $this->error($e->getMessage(), 404);
                }
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', \Uri::create($this->_radix->shortname.'/thread/'.\Input::post('parent')));
            $this->builder->getProps()->addTitle(_i('Redirecting'));

            return new Response($this->builder->build());
        }

        // Determine if the invalid post fields are populated by bots.
        if (isset($post['name']) && mb_strlen($post['name']) > 0) {
            return $this->error();
        }
        if (isset($post['reply']) && mb_strlen($post['reply']) > 0) {
            return $this->error();
        }
        if (isset($post['email']) && mb_strlen($post['email']) > 0) {
            return $this->error();
        }

        $data = [];

        $post = \Input::post();

        if (isset($post['parent'])) {
            $data['thread_num'] = $post['parent'];
        }
        if (isset($post['NAMAE'])) {
            $data['name'] = $post['NAMAE'];
        }
        if (isset($post['MERU'])) {
            $data['email'] = $post['MERU'];
        }
        if (isset($post['subject'])) {
            $data['title'] = $post['subject'];
        }
        if (isset($post['KOMENTO'])) {
            $data['comment'] = $post['KOMENTO'];
        }
        if (isset($post['delpass'])) {
            // get the password needed for the reply field if it's not set yet
            if (!$post['delpass'] || strlen($post['delpass']) < 3) {
                $post['delpass'] = \Str::random('alnum', 7);
            }

            $data['delpass'] = $post['delpass'];
        }
        if (isset($post['reply_spoiler'])) {
            $data['spoiler'] = true;
        }
        if (isset($post['reply_postas'])) {
            $data['capcode'] = $post['reply_postas'];
        }
        if (isset($post['recaptcha_challenge_field']) && isset($post['recaptcha_response_field'])) {
            $data['recaptcha_challenge'] = $post['recaptcha_challenge_field'];
            $data['recaptcha_response'] = $post['recaptcha_response_field'];
        }

        $media = null;

        if (count(\Upload::get_files())) {
            try {
                $media = \Media::forgeFromUpload($this->_radix);
                $media->spoiler = isset($data['spoiler']) && $data['spoiler'];
            } catch (\Foolz\Foolfuuka\Model\MediaUploadNoFileException $e) {
                $media = null;
            } catch (\Foolz\Foolfuuka\Model\MediaUploadException $e) {
                return $this->error($e->getMessage());
            }
        }

        return $this->submit($data, $media);
    }
}
