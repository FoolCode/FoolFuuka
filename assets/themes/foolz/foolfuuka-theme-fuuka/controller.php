<?php

namespace Foolz\FoolFuuka\Themes\Fuuka\Controller;

use Foolz\FoolFrame\Model\Cookie;
use Foolz\FoolFrame\Model\Util;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\Comment;
use Foolz\Inet\Inet;
use Symfony\Component\HttpFoundation\Response;

class Chan extends \Foolz\FoolFuuka\Controller\Chan
{
    public function radix_page($page = 1)
    {
        $options = [
            'per_page' => 24,
            'per_thread' => 5,
            'order' => ($this->radix->archive ? 'by_thread' : 'by_post')
        ];

        return $this->latest($page, $options);
    }

    public function radix_gallery($page = 1)
    {
        return $this->action_404();
    }

    /**
     * @return bool
     */
    public function radix_submit()
    {
        // adapter
        if (!$this->getPost()) {
            return $this->error(_i('You aren\'t sending the required fields for creating a new message.'));
        }

        if (!$this->checkCsrfToken()) {
            return $this->error(_i('The security token wasn\'t found. Try resubmitting.'));
        }

        if ($this->getPost('reply_delete')) {
            foreach ($this->getPost('delete') as $idx => $doc_id) {
                try {
                    $comments = Board::forge($this->getContext())
                        ->getPost()
                        ->setOptions('doc_id', $doc_id)
                        ->setRadix($this->radix)
                        ->getComments();

                    $comment = current($comments);
                    $comment = new Comment($this->getContext(), $comment);
                    $comment->delete($this->getPost('delpass'));
                } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                    return $this->error($e->getMessage(), 404);
                } catch (\Foolz\FoolFuuka\Model\CommentDeleteWrongPassException $e) {
                    return $this->error($e->getMessage(), 404);
                }
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', $this->uri->create([$this->radix->shortname, 'thread', $comment->comment->thread_num]));
            $this->builder->getProps()->addTitle(_i('Redirecting'));

            return new Response($this->builder->build());
        }

        if ($this->getPost('reply_report')) {

            foreach ($this->getPost('delete') as $idx => $doc_id) {
                try {
                    $this->getContext()->getService('foolfuuka.report_collection')
                        ->add(
                            $this->radix,
                            $doc_id,
                            $this->getPost('KOMENTO'),
                            Inet::ptod($this->getRequest()->getClientIp())
                        );
                } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                    return $this->error($e->getMessage(), 404);
                }
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', $this->uri->create($this->radix->shortname.'/thread/'.$this->getPost('parent')));
            $this->builder->getProps()->addTitle(_i('Redirecting'));

            return new Response($this->builder->build());
        }

        // Determine if the invalid post fields are populated by bots.
        if (isset($post['name']) && mb_strlen($post['name'], 'utf-8') > 0) {
            return $this->error();
        }
        if (isset($post['reply']) && mb_strlen($post['reply'], 'utf-8') > 0) {
            return $this->error();
        }
        if (isset($post['email']) && mb_strlen($post['email'], 'utf-8') > 0) {
            return $this->error();
        }

        $data = [];

        $post = $this->getPost();

        if (isset($post['parent'])) {
            $data['thread_num'] = $post['parent'];
        }
        if (isset($post['NAMAE'])) {
            $data['name'] = $post['NAMAE'];
            $this->response->headers->setCookie(new Cookie($this->getContext(), 'reply_name', $data['name'], 60*60*24*30));
        }
        if (isset($post['MERU'])) {
            $data['email'] = $post['MERU'];
            $this->response->headers->setCookie(new Cookie($this->getContext(), 'reply_email', $data['email'], 60*60*24*30));
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
                $post['delpass'] = Util::randomString(7);
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

        if ($this->getRequest()->files->count()) {
            try {
                $media = $this->media_factory->forgeFromUpload($this->getRequest(), $this->radix);
                $media->spoiler = isset($data['spoiler']) && $data['spoiler'];
            } catch (\Foolz\FoolFuuka\Model\MediaUploadNoFileException $e) {
                $media = null;
            } catch (\Foolz\FoolFuuka\Model\MediaUploadException $e) {
                return $this->error($e->getMessage());
            }
        }

        return $this->submit($data, $media);
    }
}
