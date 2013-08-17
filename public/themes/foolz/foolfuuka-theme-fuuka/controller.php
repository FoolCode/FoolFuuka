<?php

namespace Foolz\Foolfuuka\Themes\Fuuka\Controller;

use Foolz\Foolfuuka\Model\Board;
use Foolz\Inet\Inet;
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
            'order' => ($this->radix->archive ? 'by_thread' : 'by_post')
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
        if (!$this->getPost()) {
            return $this->error(_i('You aren\'t sending the required fields for creating a new message.'));
        }

        if (!\Security::check_token()) {
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
                    $comment->delete($this->getPost('delpass'));
                } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
                    return $this->error($e->getMessage(), 404);
                } catch (\Foolz\Foolfuuka\Model\CommentDeleteWrongPassException $e) {
                    return $this->error($e->getMessage(), 404);
                }
            }

            $this->builder->createLayout('redirect')
                ->getParamManager()
                ->setParam('url', $this->uri->create([$this->radix->shortname, 'thread', $comment->thread_num]));
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
                } catch (\Foolz\Foolfuuka\Model\ReportException $e) {
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

        $post = $this->getPost();

        if (isset($post['parent'])) {
            $data['thread_num'] = $post['parent'];
        }
        if (isset($post['NAMAE'])) {
            $data['name'] = $post['NAMAE'];
            \Cookie::set('reply_name', $data['name'], 60*60*24*30);
        }
        if (isset($post['MERU'])) {
            $data['email'] = $post['MERU'];
            \Cookie::set('reply_email', $data['email'], 60*60*24*30);
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
                $media = $this->media_factory->forgeFromUpload($this->radix);
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
