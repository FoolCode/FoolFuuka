<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\Model;
use Foolz\Plugin\Hook;
use Foolz\Plugin\Plugsuit;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class MediaFactory extends Model
{
    use Plugsuit;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
    }

    /**
     * Creates an empty Media object
     *
     * @param  Radix $radix  The Radix the Media will refer to
     *
     * @return \Foolz\FoolFuuka\Model\Media  An empty Media object, with all the values unset
     */
    public function forgeEmpty(Radix $radix)
    {
        $media = new \stdClass();
        return new Media($this->getContext(), $media, $radix);
    }

    /**
     * Return a Media object by a chosen column
     *
     * @param  Radix $radix  The Radix where the Media can be found
     * @param  string                        $where  The database column to match on
     * @param  string                        $value  The value searched for
     * @param  boolean                       $op     If the object is for an opening post
     *
     * @return  \Foolz\FoolFuuka\Model\MediaData  The searched object
     * @throws  MediaNotFoundException        If the media has not been found
     */
    protected function p_getBy(Radix $radix, $where, $value, $op = true)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($radix->getTable('_images'), 'ri')
            ->where($this->dc->getConnection()->quoteIdentifier($where).' = '.$this->dc->getConnection()->quote($value))
            ->execute()
            ->fetch();

        if ($result) {
            $md = new MediaData();
            $md->import($result);
            return $md;
        }

        throw new MediaNotFoundException(_i('The image could not be found.'));
    }

    /**
     * Return a Media object by the media_id column
     *
     * @param  Radix $radix  The Radix where the Media can be found
     * @param  string                        $value  The media ID
     * @param  boolean                       $op     If the object is for an opening post
     *
     * @return  \Foolz\FoolFuuka\Model\Media  The searched object
     * @throws  MediaNotFoundException        If the media has not been found
     */
    protected function p_getByMediaId(Radix $radix, $value, $op = false)
    {
        return $this->getBy($radix, 'media_id', $value, $op);
    }

    /**
     * Return a Media object by the media_hash column
     *
     * @param  Radix $radix  The Radix where the Media can be found
     * @param  string                        $value  The media hash
     * @param  boolean                       $op     If the object is for an opening post
     *
     * @return  \Foolz\FoolFuuka\Model\Media  The searched object
     * @throws  MediaNotFoundException        If the media has not been found
     */
    protected function p_getByMediaHash(Radix $radix, $value, $op = false)
    {
        return $this->getBy($radix, 'media_hash', $value, $op);
    }

    /**
     * Return a Media object by the media_hash column
     *
     * @param  Radix $radix  The Radix where the Media can be found
     * @param  string                        $value  The filename
     *
     * @return  \Foolz\FoolFuuka\Model\MediaData  The searched object
     * @throws  MediaNotFoundException        If the media has not been found
     */
    protected function p_getByFilename(Radix $radix, $filename)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($radix->getTable(), 'r')
            ->leftJoin('r', $radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
            ->where('r.media_orig = :media_orig')
            ->setParameter(':media_orig', $filename)
            ->execute()
            ->fetch();

        if ($result) {
            $bulk = new CommentBulk();
            $bulk->import($result, $radix);
            return $bulk;
        }

        throw new MediaNotFoundException;
    }

    /**
     * Takes an uploaded file and makes an object. It doesn't do the ->insert()
     *
     * @param  Radix $radix  The Radix where this Media belongs
     *
     * @return  \Foolz\FoolFuuka\Model\Media            A new Media object with the upload data
     * @throws  MediaUploadNoFileException              If there's no file uploaded
     * @throws  MediaUploadMultipleNotAllowedException  If there's multiple uploads
     * @throws  MediaUploadInvalidException             If the file format is not allowed
     */
    protected function p_forgeFromUpload(Request $request, Radix $radix)
    {
        $config = Hook::forge('Foolz\FoolFuuka\Model\MediaFactory::forgeFromUpload#var.config')
            ->setParams([
                'ext_whitelist' => ['jpg', 'jpeg', 'gif', 'png'],
                'mime_whitelist' => ['image/jpeg', 'image/png', 'image/gif']
            ])
            ->execute()
            ->getParams();

        if (!$request->files->count())
        {
            throw new MediaUploadNoFileException(_i('You must upload an image or your image was too large.'));
        }

        if ($request->files->count() !== 1) {
            throw new MediaUploadMultipleNotAllowedException(_i('You can\'t upload multiple images.'));
        }

        /** @var UploadedFile[] $files */
        $files = $request->files->all();

        $max_size = $radix->getValue('max_image_size_kilobytes') * 1024;

        foreach ($files as $file) {
            if (!$file->isValid()) {

                if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
                    throw new MediaUploadInvalidException(
                        _i('The server is misconfigured: the FoolFuuka upload size should be lower than PHP\'s upload limit.'));
                }

                if ($file->getError() === UPLOAD_ERR_PARTIAL) {
                    throw new MediaUploadInvalidException(_i('You uploaded the file partially.'));
                }

                if ($file->getError() === UPLOAD_ERR_CANT_WRITE) {
                    throw new MediaUploadInvalidException(_i('The image couldn\'t be saved on the disk.'));
                }

                if ($file->getError() === UPLOAD_ERR_EXTENSION) {
                    throw new MediaUploadInvalidException(_i('A PHP extension broke and made processing the image impossible.'));
                }

                throw new MediaUploadInvalidException(_i('Unexpected upload error.'));
            }

            if (mb_strlen($file->getFilename(), 'utf-8') > 64) {
                throw new MediaUploadInvalidException(_i('You uploaded a file with a too long filename.'));
            }

            if (!in_array(strtolower($file->getClientOriginalExtension()), $config['ext_whitelist'])) {
                throw new MediaUploadInvalidException(_i('You uploaded a file with an invalid extension.'));
            }

            if (!in_array(strtolower($file->getMimeType()), $config['mime_whitelist'])) {
                throw new MediaUploadInvalidException(_i('You uploaded a file with an invalid mime type.'));
            }

            if ($file->getClientSize() > $max_size && !$this->getAuth()->hasAccess('media.limitless_media')) {
                throw new MediaUploadInvalidException(
                    _i('You uploaded a too big file. The maxmimum allowed filesize is %s',
                        $radix->getValue('max_image_size_kilobytes')));
            }
        }

        $media = new Media($this->getContext());
        $media->setTempFile($radix, $files['file_image']);

        return $media;
    }
}
