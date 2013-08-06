<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Context;
use Foolz\Plugin\Hook;
use Foolz\Plugin\Plugsuit;

class MediaFactory extends Model
{
    use Plugsuit;

    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
    }

    /**
     * Creates an empty Media object
     *
     * @param  Radix $radix  The Radix the Media will refer to
     *
     * @return \Foolz\Foolfuuka\Model\Media  An empty Media object, with all the values unset
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
     * @return  \Foolz\Foolfuuka\Model\Media  The searched object
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
            return new Media($this->getContext(), $result, $radix, $op);
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
     * @return  \Foolz\Foolfuuka\Model\Media  The searched object
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
     * @return  \Foolz\Foolfuuka\Model\Media  The searched object
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
     * @param  boolean                       $op     If the object is for an opening post
     *
     * @return  \Foolz\Foolfuuka\Model\Media  The searched object
     * @throws  MediaNotFoundException        If the media has not been found
     */
    protected function p_getByFilename(Radix $radix, $filename, $op = false)
    {
        $result = $this->dc->qb()
            ->select('media_id')
            ->from($radix->getTable(), 'r')
            ->where('r.media_orig = :media_orig')
            ->setParameter(':media_orig', $filename)
            ->execute()
            ->fetch();

        if ($result) {
            return $this->getByMediaId($radix, $result['media_id'], $op);
        }

        throw new MediaNotFoundException;
    }

    /**
     * Takes an uploaded file and makes an object. It doesn't do the ->insert()
     *
     * @param  Radix $radix  The Radix where this Media belongs
     *
     * @return  \Foolz\Foolfuuka\Model\Media            A new Media object with the upload data
     * @throws  MediaUploadNoFileException              If there's no file uploaded
     * @throws  MediaUploadMultipleNotAllowedException  If there's multiple uploads
     * @throws  MediaUploadInvalidException             If the file format is not allowed
     */
    protected function p_forgeFromUpload(Radix $radix)
    {
        $config = Hook::forge('Foolz\Foolfuuka\Model\Media::upload.config')
            ->setParams([
                'path' => APPPATH.'tmp/media_upload/',
                'max_size' => \Auth::has_access('media.limitless_media') ? 0 : $radix->getValue('max_image_size_kilobytes') * 1024,
                'randomize' => true,
                'max_length' => 64,
                'ext_whitelist' => ['jpg', 'jpeg', 'gif', 'png'],
                'mime_whitelist' => ['image/jpeg', 'image/png', 'image/gif']
            ])
            ->execute();

        \Upload::process($config->getParams());

        if (count(\Upload::get_files()) === 0) {
            throw new MediaUploadNoFileException(_i('You must upload an image or your image was too large.'));
        }

        if (count(\Upload::get_files()) !== 1) {
            throw new MediaUploadMultipleNotAllowedException(_i('You can\'t upload multiple images.'));
        }

        if (\Upload::is_valid()) {
            // save them according to the config
            \Upload::save();
        }

        $file = \Upload::get_files(0);

        if (!\Upload::is_valid()) {
            if (in_array($file['errors'], UPLOAD_ERR_INI_SIZE)) {
                throw new MediaUploadInvalidException(
                    _i('The server is misconfigured: the FoolFuuka upload size should be lower than PHP\'s upload limit.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_PARTIAL)) {
                throw new MediaUploadInvalidException(_i('You uploaded the file partially.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_CANT_WRITE)) {
                throw new MediaUploadInvalidException(_i('The image couldn\'t be saved on the disk.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_EXTENSION)) {
                throw new MediaUploadInvalidException(_i('A PHP extension broke and made processing the image impossible.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_MAX_SIZE)) {
                throw new MediaUploadInvalidException(
                    _i('You uploaded a too big file. The maxmimum allowed filesize is %s',
                        $radix->getValue('max_image_size_kilobytes')));
            }

            if (in_array($file['errors'], UPLOAD_ERR_EXT_NOT_WHITELISTED)) {
                throw new MediaUploadInvalidException(_i('You uploaded a file with an invalid extension.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_MAX_FILENAME_LENGTH)) {
                throw new MediaUploadInvalidException(_i('You uploaded a file with a too long filename.'));
            }

            if (in_array($file['errors'], UPLOAD_ERR_MOVE_FAILED)) {
                throw new MediaUploadInvalidException(_i('Your uploaded file couldn\'t me moved on the server.'));
            }

            throw new MediaUploadInvalidException(_i('Unexpected upload error.'));
        }

        $media = [
            'media_filename' => $file['name'],
            'media_size' => $file['size'],
            'temp_path' => $file['saved_to'],
            'temp_filename' => $file['saved_as'],
            'temp_extension' => $file['extension']
        ];

        return new Media($this->getContext(), $media, $radix);
    }
}