<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\Config;
use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\Cache\Cache;
use Foolz\FoolFrame\Model\Model;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\FoolFrame\Model\Uri;
use Foolz\Plugin\Hook;
use Foolz\Plugin\PlugSuit;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class MediaException extends \Exception {}
class MediaNotFoundException extends MediaException {}
class MediaHashNotFoundException extends MediaNotFoundException {}
class MediaDirNotAvailableException extends MediaNotFoundException {}
class MediaFileNotFoundException extends MediaNotFoundException {}

class MediaUploadException extends \Exception {}
class MediaUploadNoFileException extends MediaUploadException {}
class MediaUploadMultipleNotAllowedException extends MediaUploadException {}
class MediaUploadInvalidException extends MediaUploadException {}

class MediaInsertException extends \Exception {}
class MediaInsertInvalidFormatException extends MediaInsertException {}
class MediaInsertDomainException extends MediaInsertException {}
class MediaInsertRepostException extends MediaInsertException {}
class MediaThumbnailCreationException extends MediaInsertException {}

/**
 * Manages Media files and database
 */
class Media extends Model
{
    use PlugSuit;

    /**
     * If the media is referred to an opening post
     *
     * @var  boolean
     */
    public $op = false;

    /**
     * The radix object for the media
     *
     * @var Radix
     */
    public $radix = null;

    /**
     * The temporary filename for the uploaded file
     *
     * @var  UploadedFile
     */
    public $temp_file = null;

    /**
     * @var Audit
     */
    protected $audit;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    /**
     * The array of fields that are part of the database
     *
     * @var  array
     */
    public static $fields = [
        'media_id',
        'spoiler',
        'preview_orig',
        'media',
        'preview_op',
        'preview_reply',
        'preview_w',
        'preview_h',
        'media_filename',
        'media_w',
        'media_h',
        'media_size',
        'media_hash',
        'media_orig',
        'exif',
        'total',
        'banned'
    ];

    /**
     * Status for media that has no flags on it
     */
    const STATUS_NORMAL = 'normal';

    /**
     * Status for media that was banned
     */
    const STATUS_BANNED = 'banned';

    /**
     * Status for media that the user can't access
     */
    const STATUS_FORBIDDEN = 'forbidden';

    /**
     * Status for media that isn't found on server
     */
    const STATUS_NOT_AVAILABLE = 'not-available';

    /**
     * @var CommentBulk
     */
    public $bulk;

    /**
     * @var MediaData
     */
    public $media;

    /**
     * Returns static::$fields
     *
     * @see static::fields
     * @return array
     */
    public static function getFields()
    {
        return static::$fields;
    }

    public function __get($key)
    {
        return $this->bulk->media->$key;
    }

    /**
     * Takes the data from a Comment to forge a Media object
     *
     * @param  object|array                  $comment  An array or object, the construct will use its keys to create a Media object
     * @param  Radix $radix    The Radix in which the Media can be found
     * @param  boolean                       $op       If this media is referred to an opening post
     */
    public function __construct(\Foolz\FoolFrame\Model\Context $context, CommentBulk $bulk = null)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->preferences = $context->getService('preferences');
        $this->config = $context->getService('config');
        $this->uri = $context->getService('uri');
        $this->audit = $context->getService('foolfuuka.audit_factory');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->media_factory = $context->getService('foolfuuka.media_factory');

        if ($bulk !== null) {
            $this->setBulk($bulk);
        }
    }

    public function setTempFile(Radix $radix, File $file)
    {
        $this->bulk = new CommentBulk();
        $this->media = $this->bulk->media = new MediaData();
        $this->radix = $radix;
        $this->temp_file = $file;
    }

    public function setBulk(CommentBulk $bulk)
    {
        $this->radix = $bulk->getRadix();
        $this->bulk = $bulk;
        $this->media = $bulk->media;

        // some cases we don't have a comment attached, like banning/deleting
        if (isset($bulk->comment)) {
            $this->op = $bulk->comment->op;
        }

        if ($this->radix->archive) {
            // archive entries for media_filename are already encoded and we risk overencoding
            $this->media->media_filename = html_entity_decode($this->media->media_filename, ENT_QUOTES, 'UTF-8');
        }

        // let's unset 0 sizes so maybe the __get() can save the day
        if (!$this->media->preview_w || !$this->media->preview_h) {
            $this->media->preview_h = 0;
            $this->media->preview_w = 0;

            if ($this->radix->archive && $this->media->spoiler) {
                try {
                    $imgsize = Cache::item('foolfuuka.media.call.spoiler_size.'.$this->radix->id.'.'.$this->media->media_id.'.'.($this->op ? 'op':'reply'))->get();
                    $this->media->preview_w = $imgsize[0];
                    $this->media->preview_h = $imgsize[1];
                } catch (\OutOfBoundsException $e) {
                    $imgpath = $this->getDir(true);
                    if ($imgpath !== null) {
                        $imgsize = @getimagesize($imgpath);

                        Cache::item('foolfuuka.media.call.spoiler_size.'.$this->radix->id.'.'.$this->media->media_id.'.'.($this->op ? 'op':'reply'))->set($imgsize, 86400);

                        if ($imgsize !== false) {
                            $this->media->preview_w = $imgsize[0];
                            $this->media->preview_h = $imgsize[1];
                        }
                    }
                }
            }
        }

        // we set them even for admins
        if ($this->media->banned) {
            $this->media->media_status = static::STATUS_BANNED;
        } elseif ($this->radix->hide_thumbnails && !$this->getAuth()->hasAccess('media.see_hidden')) {
            $this->media->media_status = static::STATUS_FORBIDDEN;
        } else {
            $this->media->media_status = static::STATUS_NORMAL;
        }
    }

    /**
     * Returns the safe media hash and caches the result
     *
     * @return  string
     */
    public function getSafeMediaHash()
    {
        if ($this->media->safe_media_hash === false) {
            $this->media->safe_media_hash = $this->getHash(true);
        }

        return $this->media->safe_media_hash;
    }

    /**
     * Returns the media_status and caches the result
     *
     * @return  string
     */
    public function getMediaStatus(Request $request)
    {
        if ($this->media->media_status === false) {
            $this->media->media_link = $this->getLink($request, false);
        }

        return $this->media->media_status;
    }

    /**
     * Returns the remote media link and caches the result
     *
     * @see getRemoteLink()
     *
     * @return  string|null
     */
    public function getRemoteMediaLink(Request $request)
    {
        if ($this->media->remote_media_link === false) {
            $this->media->remote_media_link = $this->getRemoteLink($request);
        }

        return $this->media->remote_media_link;
    }

    /**
     * Returns the media_link and caches the result
     *
     * @see getLink(false)
     *
     * @return  string
     */
    public function getMediaLink(Request $request)
    {
        if ($this->media->media_link === false) {
            $this->media->media_link = $this->getLink($request, false);
        }

        return $this->media->media_link;
    }

    /**
     * Returns the media download link
     *
     * @return  string
     */
    public function getMediaDownloadLink(Request $request)
    {
        return $this->getLink($request, false, true);
    }

    /**
     * Returns the media_thumb and caches the result
     *
     * @see getLink(true)
     *
     * @return  string
     */
    public function getThumbLink(Request $request)
    {
        if ($this->media->thumb_link === false) {
            $this->media->thumb_link = $this->getLink($request, true);
        }

        return $this->media->thumb_link;
    }

    /**
     * Processes a string to be safe for HTML
     *
     * @param  string  $string  The string to escape
     *
     * @return  string  The escaped string
     */
    public static function process($string)
    {
        return htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $string));
    }

    /**
     * Returns the filename escaped for HTML display and caches the result
     *
     * @return string
     */
    public function getMediaFilenameProcessed()
    {
        if (!$this->media->media_filename_processed) {
            $this->media->media_filename_processed = static::process($this->media->media_filename);
        }

        return $this->media->media_filename_processed;
    }

    /**
     * Get the path to the media. It doesn't check if the path exists
     *
     * @param  boolean  $thumbnail  True we're looking for a thumbnail, false if we're looking for a full media
     * @param  boolean  $strict     If we want strictly the OP or the reply media. If false thumbnails will use either OP or reply as fallback.
     *
     * @return  null|string  Null if a dir can't be created, string if successful
     */
    public function getDir($thumbnail = false, $strict = false, $relative = false)
    {
        if ($thumbnail === true) {
            if ($this->op) {
                if ($strict) {
                    $image = $this->media->preview_op;
                } else {
                    $image = $this->media->preview_op !== null ? $this->media->preview_op : $this->media->preview_reply;
                }
            } else {
                if ($strict) {
                    $image = $this->media->preview_reply;
                } else {
                    $image = $this->media->preview_reply !== null ? $this->media->preview_reply : $this->media->preview_op;
                }
            }
        } else {
            $image = $this->media->media;
        }

        if ($image === null) {
            return null;
        }

        return ($relative ? '' : $this->preferences->get('foolfuuka.boards.directory')).'/'.$this->radix->shortname.'/'
            .($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
    }

    /**
     * Get the full URL to the media, and in case switch between multiple CDNs
     *
     * @param  boolean  $thumbnail  True if looking for a thumbnail, false for full media
     *
     * @return  null|string  Null if not available, string of the url if available
     */
    public function getLink(Request $request, $thumbnail = false, $download = false)
    {
        $before = Hook::forge('Foolz\FoolFuuka\Model\Media::getLink#exec.beforeMethod')
            ->setObject($this)
            ->setParams(['thumbnail' => $thumbnail])
            ->execute()
            ->get();

        if (!$before instanceof \Foolz\Plugin\Void) {
            return $before;
        }

        if ($thumbnail) {
            if ($this->op == 1) {
                $image = $this->media->preview_op ? : $this->media->preview_reply;
            } else {
                $image = $this->media->preview_reply ? : $this->media->preview_op;
            }
        } else {
            if ($this->radix->archive && !$this->radix->getValue('archive_full_images')) {
                return null;
            } else {
                $image = $this->media->media;
            }
        }

        if ($download === true && $this->preferences->get('foolfuuka.boards.media_download_url')) {
            return $this->preferences->get('foolfuuka.boards.media_download_url')
                .'/'.$this->radix->shortname.'/'.($thumbnail ? 'thumb' : 'image').'/'
                .substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
        }

        $cdn = '';
        if ($request->isSecure() && $this->preferences->get('foolfuuka.boards.media_balancers_https')) {
            $cdn = $this->preferences->get('foolfuuka.boards.media_balancers_https');
        } elseif ($this->preferences->get('foolfuuka.boards.media_balancers')) {
            $cdn = $this->preferences->get('foolfuuka.boards.media_balancers');
        }

        $cdn = array_filter(preg_split('/\r\n|\r|\n/', $cdn));
        if (!empty($cdn)) {
            return $cdn[($this->media->media_id % count($cdn))]
                .'/'.$this->radix->shortname.'/'.($thumbnail ? 'thumb' : 'image').'/'
                .substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
        }

        return $this->preferences->get('foolfuuka.boards.url')
            .'/'.$this->radix->shortname.'/'.($thumbnail ? 'thumb' : 'image').'/'
            .substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
    }

    /**
     * Get the remote link for media if it's not local
     *
     * @return  null|string  remote URL of local URL if not compatible with remote URL (see getLink() for return values)
     */
    public function getRemoteLink(Request $request)
    {
        if ($this->radix->archive && ($this->radix->getValue('images_url') === false
                || $this->radix->getValue('images_url') !== "")) {
            // ignore webkit and opera user agents
            $ua = $request->headers->get('User-Agent');
            if ($ua && preg_match('/(opera|webkit)/i', $ua)) {
                return $this->radix->getValue('images_url').$this->media->media_orig;
            }

            return $this->uri->create([$this->radix->shortname, 'redirect']).$this->media->media_orig;
        } else {
            if (file_exists($this->getDir()) !== false) {
                return $this->getLink($request);
            }
        }
    }

    /**
     * Encode or decode the hash from and to a safe URL representation
     *
     * @param  boolean  $urlsafe  True if we want the result to be URL-safe, false for standard MD5
     *
     * @return  string  The hash
     */
    public function getHash($urlsafe = false)
    {
        // return a safely escaped media hash for urls or un-altered media hash
        if ($urlsafe === true) {
            return static::urlsafe_b64encode(static::urlsafe_b64decode($this->media->media_hash));
        } else {
            return base64_encode(static::urlsafe_b64decode($this->media->media_hash));
        }
    }

    /**
     * Encodes a media hash to base64 and converts eventual unsafe characters to safe ones
     *
     * @param  string  $string  The hash
     *
     * @return  string  URL-safe hash
     */
    public static function urlsafe_b64encode($string)
    {
        $string = base64_encode($string);
        return str_replace(['+', '/', '='], ['-', '_', ''], $string);
    }

    /**
     * Decodes a media hash and converts eventual safe characters to the original representation
     *
     * @param  string  $string  The media_hash
     *
     * @return  string  The original media hash
     */
    public static function urlsafe_b64decode($string)
    {
        $string = str_replace(['-', '_'], ['+', '/'], $string);
        return base64_decode($string);
    }

    /**
     * Deletes the media file if there's no other occurencies for the same file
     *
     * @param  boolean  $full   True if the full media should be deleted
     * @param  boolean  $thumb  True if the thumbnail should be deleted (both OP and reply thumbnail will be deleted)
     * @param  boolean  $purge  True if the image should be deleted regardless if total > 1 (needs 'comment.passwordless_deletion' powers)
     */
    public function p_delete($full = true, $thumb = true, $purge = false)
    {
        // delete media file only if there is only one image OR the image is banned
        if ($this->media->total == 1 || $this->media->banned == 1 || ($this->getAuth()->hasAccess('comment.passwordless_deletion') && $purge)) {
            if ($full === true) {
                $media_file = $this->getDir();

                if ($media_file !== null && file_exists($media_file)) {
                    unlink($media_file);
                }
            }

            if ($thumb === true) {
                $temp = $this->op;

                // remove OP thumbnail
                $this->op = 1;

                $thumb_file = $this->getDir(true);

                if ($thumb_file !== null && file_exists($thumb_file)) {
                    unlink($thumb_file);
                }

                // remove reply thumbnail
                $this->op = 0;
                $thumb_file = $this->getDir(true);

                if ($thumb_file !== null && file_exists($thumb_file)) {
                    unlink($thumb_file);
                }

                $this->op = $temp;
            }

            $this->audit->log(Audit::AUDIT_DEL_FILE, ['radix' => $this->radix->id, 'media_id' => $this->media->media_id, 'media_hash' => $this->media->media_hash]);
        }
    }

    /**
     * Bans an image for a board or for all the boards
     *
     * @param  boolean  $global  False if the media should be banned only on current Radix, true if it should be banned on all the Radix and future ones
     */
    public function p_ban($global = false)
    {
        if ($global === false) {
            $this->dc->qb()
                ->update($this->radix->getTable('_images'))
                ->set('banned', 1)
                ->where('media_id = :media_id')
                ->setParameter(':media_id', $this->media->media_id)
                ->execute();

            $this->delete(true, true, true);
            $this->audit->log(Audit::AUDIT_BAN_FILE, ['global' => false, 'radix' => $this->radix->id, 'media_id' => $this->media->media_id, 'media_hash' => $this->media->media_hash]);
            return;
        }

        $result = $this->dc->qb()
            ->select('COUNT(*) as count')
            ->from($this->radix->getTable('_images'), 'ri')
            ->where('media_hash = :md5')
            ->setParameter(':md5', $this->media->media_hash)
            ->execute()
            ->fetch();

        if (!$result['count']) {
            $this->dc->getConnection()
                ->insert('banned_md5', ['md5' => $this->media->media_hash])
                ->execute();
        }

        foreach ($this->radix_coll->getAll() as $radix) {
            try {
                $media = $this->media_factory->getByMediaHash($radix, $this->media->media_hash);
                $media = new Media($this->getContext(), CommentBulk::forge($radix, null, $media));

                $this->dc->qb()
                    ->update($radix->getTable('_images'))
                    ->set('banned', 1)
                    ->where('media_id = :media_id')
                    ->setParameter(':media_id', $media->media_id)
                    ->execute();

                $media->delete(true, true, true);
            } catch (MediaNotFoundException $e) {
                $this->dc->getConnection()
                    ->insert($radix->getTable('_images'), ['media_hash' => $this->media->media_hash, 'banned' => 1]);
            }
        }
        $this->audit->log(Audit::AUDIT_BAN_FILE, ['global' => true, 'media_hash' => $this->media->media_hash]);
    }

    /**
     * Inserts the media uploaded (static::forgeFromUpload())
     *
     * @param  string   $microtime  The time in microseconds to use for the local filename
     * @param  boolean  $is_op      True if the thumbnail sizes should be for OP, false if they should be for a reply
     *
     * @return  \Foolz\FoolFuuka\Model\Media       The current object updated with the insert data
     * @throws  MediaInsertInvalidFormatException  In case the media is not a valid format
     * @throws  MediaInsertDomainException         In case the media uploaded is in example too small or validations don't pass
     * @throws  MediaInsertRepostException         In case the media has been reposted too recently according to control panel settings
     * @throws  MediaThumbnailCreationException	   If the thumbnail fails to generate
     */
    public function p_insert($microtime, $is_op)
    {
        $file = $this->temp_file;
        $this->op = $is_op;

        $data = Hook::forge('Foolz\FoolFuuka\Model\Media::insert#var.media')
            ->setParams([
                'dimensions' => getimagesize($file->getPathname()),
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'path' => $file->getPathname(),
                'hash' => base64_encode(pack("H*", md5(file_get_contents($file->getPathname())))),
                'size' => $file->getClientSize(),
                'time' => $microtime,
                'media_orig' => $microtime.'.'.strtolower($file->getClientOriginalExtension()),
                'preview_orig' => $microtime.'s.'.strtolower($file->getClientOriginalExtension())
            ])
            ->execute()
            ->getParams();

        if (!($getimagesize = $data['dimensions'])) {
            throw new MediaInsertInvalidFormatException(_i('The file you uploaded is not allowed.'));
        }

        // if width and height are lower than 25 reject the image
        if ($getimagesize[0] < 25 || $getimagesize[1] < 25) {
            throw new MediaInsertDomainException(_i('The file you uploaded is too small.'));
        }

        if ($getimagesize[0] > $this->radix->getValue('max_image_size_width')
            || $getimagesize[1] > $this->radix->getValue('max_image_size_height'))
        {
            throw new MediaInsertDomainException(_i('The dimensions of the file you uploaded is too large.'));
        }

        $this->media->media_w = $getimagesize[0];
        $this->media->media_h = $getimagesize[1];
        $this->media->media_filename = $data['name'];;
        $this->media->media_hash = $data['hash'];;
        $this->media->media_size = $data['size'];;
        $this->media->media_orig = $data['media_orig'];;
        $this->media->preview_orig = $data['preview_orig'];;

        $do_thumb = true;
        $do_full = true;

        try {
            $duplicate = CommentBulk::forge($this->radix, null, $this->media_factory->getByMediaHash($this->radix, $this->media->media_hash));
            $duplicate = new Media($this->getContext(), $duplicate);
            // we want the current media to work with the same filenames as previously stored
            $this->media->media_id = $duplicate->media->media_id;
            $this->media->media = $duplicate->media->media;
            $this->media->media_orig = $duplicate->media->media;
            $this->media->preview_op = $duplicate->media->preview_op;
            $this->media->preview_reply = $duplicate->media->preview_reply;

            if (!$this->getAuth()->hasAccess('comment.limitless_comment') && $this->radix->getValue('min_image_repost_time')) {
                // if it's -1 it means that image reposting is disabled, so this image shouldn't pass
                if ($this->radix->getValue('min_image_repost_time') == -1) {
                    throw new MediaInsertRepostException(
                        _i('This image has already been posted once. This board doesn\'t allow image reposting.')
                    );
                }

                // we don't have to worry about archives with weird timestamps, we can't post images there
                $duplicate_entry = $this->dc->qb()
                    ->select('COUNT(*) as count, MAX(timestamp) as max_timestamp')
                    ->from($this->radix->getTable(), 'r')
                    ->where('media_id = :media_id')
                    ->andWhere('timestamp > :timestamp')
                    ->setParameter('media_id', $duplicate->media->media_id)
                    ->setParameter('timestamp', time() - $this->radix->getValue('min_image_repost_time'))
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch();

                if ($duplicate_entry['count']) {
                    $datetime = new \DateTime(date('Y-m-d H:i:s', $duplicate_entry['max_timestamp'] + $this->radix->getValue('min_image_repost_time')));
                    $remain = $datetime->diff(new \DateTime());

                    throw new MediaInsertRepostException(
                        _i('This image has been posted recently. You will be able to post it again in %s.',
                             ($remain->d > 0 ? $remain->d.' '._i('day(s)') : '').' '
                            .($remain->h > 0 ? $remain->h.' '._i('hour(s)') : '').' '
                            .($remain->i > 0 ? $remain->i.' '._i('minute(s)') : '').' '
                            .($remain->s > 0 ? $remain->s.' '._i('second(s)') : '')
                        )
                    );
                }
            }

            // if we're here, we got the media
            $duplicate_dir = $duplicate->getDir();
            if ($duplicate_dir !== null && file_exists($duplicate_dir)) {
                $do_full = false;
            }

            $duplicate->op = $is_op;
            $duplicate_dir_thumb = $duplicate->getDir(true, true);
            if ($duplicate_dir_thumb !== null && file_exists($duplicate_dir_thumb)) {
                $duplicate_dir_thumb_size = getimagesize($duplicate_dir_thumb);
                $this->media->preview_w = $duplicate_dir_thumb_size[0];
                $this->media->preview_h = $duplicate_dir_thumb_size[1];
                $do_thumb = false;
            }
        } catch (MediaNotFoundException $e) {

        }

        if ($do_thumb) {
            $thumb_width = $this->radix->getValue('thumbnail_reply_width');
            $thumb_height = $this->radix->getValue('thumbnail_reply_height');

            if ($is_op) {
                $thumb_width = $this->radix->getValue('thumbnail_op_width');
                $thumb_height = $this->radix->getValue('thumbnail_op_height');
            }

            if (!file_exists($this->pathFromFilename(true, $is_op))) {
                mkdir($this->pathFromFilename(true, $is_op), 0777, true);
            }

            $return = Hook::forge('Foolz\FoolFuuka\Model\Media::insert#exec.createThumbnail')
                ->setObject($this)
                ->setParams([
                    'thumb_width' => $thumb_width,
                    'thumb_height' => $thumb_height,
                    'exec' => str_replace(' ', '\\ ', $this->preferences->get('foolframe.imagick.convert_path')),
                    'is_op' => $is_op,
                    'media' => $file,
                    'thumb' => $this->pathFromFilename(true, $is_op, true)
                ])
                ->execute()
                ->get();

            if ($return instanceof \Foolz\Plugin\Void) {
                if ($this->radix->getValue('enable_animated_gif_thumbs') && strtolower($file->getClientOriginalExtension()) === 'gif') {
                    exec(str_replace(' ', '\\ ', $this->preferences->get('foolframe.imagick.convert_path')) .
                        " " . $data['path'] . " -coalesce -treedepth 4 -colors 256 -quality 80 -background none " .
                        "-resize \"" . $thumb_width . "x" . $thumb_height . ">\" " . $this->pathFromFilename(true, $is_op, true));
                } else {
                    exec(str_replace(' ', '\\ ', $this->preferences->get('foolframe.imagick.convert_path')) .
                        " " . $data['path'] . "[0] -quality 80 -background none " .
                        "-resize \"" . $thumb_width . "x" . $thumb_height . ">\" " . $this->pathFromFilename(true, $is_op, true));
                }
            }

            if (!file_exists($this->pathFromFilename(true, $is_op, true))) {
                throw new MediaThumbnailCreationException(_i('The thumbnail failed to generate.'));
            }

            $thumb_getimagesize = getimagesize($this->pathFromFilename(true, $is_op, true));
            $this->media->preview_w = $thumb_getimagesize[0];
            $this->media->preview_h = $thumb_getimagesize[1];

            if ($do_full) {
                if (!file_exists($this->pathFromFilename())) {
                    mkdir($this->pathFromFilename(), 0777, true);
                }

                copy($data['path'], $this->pathFromFilename(false, false, true));
            }
        }
        if (function_exists('exif_read_data') && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'tiff'])) {
            $exif_data = null;
            getimagesize($data['path'], $exif_data);

            if (!isset($exif_data['APP1']) || strpos($exif_data['APP1'], 'Exif') === 0) {
                $exif = exif_read_data($data['path']);

                if ($exif !== false) {
                    $this->media->exif = $exif;
                }
            }
        }

        if (!$this->media->media_id) {
            $this->dc->getConnection()->insert($this->radix->getTable('_images'), [
                'media_hash' => $this->media->media_hash,
                'media' => $this->media->media_orig,
                'preview_op' => $this->op ? $this->media->preview_orig : null,
                'preview_reply' => ! $this->op ? $this->media->preview_orig : null,
                'total' => 1,
                'banned' => 0,
            ]);

            $this->media->media_id = $this->dc->getConnection()->lastInsertId($this->radix->getTable('_images_media_id_seq'));
        } else {
            $media_sql = $this->dc->qb()
                ->select('COUNT(*)')
                ->from($this->radix->getTable(), 't')
                ->where('media_id = :media_id')
                ->setParameter(':media_id', $this->media->media_id)
                ->getSQL();

            $query = $this->dc->qb()
                ->update($this->radix->getTable('_images'));
            if ($this->media === null) {
                $query->set('media', ':media_orig')
                    ->setParameter(':media_orig', $this->media->preview_orig);
            }
            if ($this->op && $this->media->preview_op === null) {
                $query->set('preview_op', ':preview_orig')
                ->setParameter(':preview_orig', $this->media->preview_orig);
            }
            if (!$this->op && $this->media->preview_reply === null) {
                $query->set('preview_reply', ':preview_orig')
                ->setParameter(':preview_orig', $this->media->preview_orig);
            }

            $query->set('total', '('.$media_sql.')');

            $query->where('media_id = :media_id')
                ->setParameter(':media_id', $this->media->media_id)
                ->execute();
        }

        return $this;
    }

    /**
     * Creates a path for the file
     *
     * @param  boolean  $thumbnail      If the path should be for a thumbnail
     * @param  boolean  $is_op          If the path should be for an OP
     * @param  boolean  $with_filename  If the path should include the filename
     *
     * @return  string  The path
     */
    public function p_pathFromFilename($thumbnail = false, $is_op = false, $with_filename = false)
    {
        $dir = $this->preferences->get('foolfuuka.boards.directory').'/'.$this->radix->shortname.'/'.
            ($thumbnail ? 'thumb' : 'image').'/';

        // we first check if we have media/preview_op/preview_reply available to reuse the value
        if ($thumbnail) {
            if ($is_op && $this->media->preview_op !== null) {
                return $dir.'/'.substr($this->media->preview_op, 0, 4).'/'.substr($this->media->preview_op, 4, 2).'/'.
                    ($with_filename ? $this->media->preview_op : '');
            } elseif (!$is_op && $this->media->preview_reply !== null) {
                return $dir.'/'.substr($this->media->preview_reply, 0, 4).'/'.substr($this->media->preview_reply, 4, 2).'/'.
                    ($with_filename ? $this->media->preview_reply : '');
            }

            // we didn't have media/preview_op/preview_reply so fallback to making a new file
            return $dir.'/'.substr($this->media->preview_orig, 0, 4).'/'.substr($this->media->preview_orig, 4, 2).'/'.
                ($with_filename ? $this->media->preview_orig : '');
        } else {
            if ($this->media->media !== null) {
                return $dir.'/'.substr($this->media->media, 0, 4).'/'.substr($this->media->media, 4, 2).'/'.
                    ($with_filename ? $this->media->media : '');
            }

            // we didn't have media/preview_op/preview_reply so fallback to making a new file
            return $dir.'/'.substr($this->media->media_orig, 0, 4).'/'.substr($this->media->media_orig, 4, 2).'/'.
                ($with_filename ? $this->media->media_orig : '');
        }
    }
}
