<?php

namespace Foolz\Foolfuuka\Model;

class MediaException extends \FuelException {}
class MediaNotFoundException extends MediaException {}
class MediaHashNotFoundException extends MediaNotFoundException {}
class MediaDirNotAvailableException extends MediaNotFoundException {}
class MediaFileNotFoundException extends MediaNotFoundException {}

class MediaUploadException extends \FuelException {}
class MediaUploadNoFileException extends MediaUploadException {}
class MediaUploadMultipleNotAllowedException extends MediaUploadException {}
class MediaUploadInvalidException extends MediaUploadException {}

class MediaInsertException extends \FuelException {}
class MediaInsertNoFileException extends MediaInsertException {}
class MediaInsertMultipleNotAllowedException extends MediaInsertException {}
class MediaInsertInvalidException extends MediaInsertException {}
class MediaInsertImageRepostException extends MediaInsertException {}

class Media extends \Model\Model_Base
{

	public $op; // required to know the size of the thumbnail

	public $media_id = 0;
	public $spoiler = false;
	public $preview_orig = null;
	public $preview_w = 0;
	public $preview_h = 0;
	public $media_filename = null;
	public $media_w = 0;
	public $media_h = 0;
	public $media_size = 0;
	public $media_hash = null;
	public $media_orig = null;
	public $exif = null;
	public $total = 0;
	public $banned = 0;

	public $media = null;
	public $preview_op = null;
	public $preview_reply = null;

	public $board = null;

	public $temp_path = null;
	public $temp_filename = null;
	public $temp_extension = null;
	public static $_fields = array(
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
	);


	public static function get_fields()
	{
		return static::$_fields;
	}


	public function __construct($comment, $board, $op = false)
	{
		$this->board = $board;

		foreach ($comment as $key => $item)
		{
			$this->$key = $item;
		}

		$this->op = $op;

		if ($this->board->archive)
		{
			// archive entries for media_filename are already encoded and we risk overencoding
			$this->media_filename = html_entity_decode($this->media_filename, ENT_QUOTES, 'UTF-8');
		}

		// let's unset 0 sizes so maybe the __get() can save the day
		if ( ! $this->preview_w || ! $this->preview_h)
		{
			$this->preview_h = 0;
			$this->preview_w = 0;

			if ($this->board->archive && $this->spoiler)
			{
				try
				{
					$imgsize = \Cache::get('fu.media.call.spoiler_size.'.$this->board->id.'.'.$this->media_id.'.'.($this->op ? 'op':'reply'));
					$this->preview_w = $imgsize[0];
					$this->preview_h = $imgsize[1];
				}
				catch (\CacheNotFoundException $e)
				{
					try
					{
						$imgpath = $this->get_dir(true);
						$imgsize = false;

						if ($imgpath)
						{
							$imgsize = @getimagesize($imgpath);
						}

						\Cache::set('fu.media.call.spoiler_size.'.$this->board->id.'.'.$this->media_id.'.'.($this->op ? 'op':'reply'), $imgsize, 86400);

						if ($imgsize !== FALSE)
						{
							$this->preview_w = $imgsize[0];
							$this->preview_h = $imgsize[1];
						}
					}
					catch (MediaNotFoundException $e)
					{

					}
				}
			}
		}

		// we set them even for admins
		if ($this->banned)
		{
			$this->media_status = 'banned';
		}
		else if ($this->board->hide_thumbnails && ! \Auth::has_access('media.see_hidden'))
		{
			$this->media_status = 'forbidden';
		}
		else
		{
			$this->media_status = 'normal';
		}
	}


	public function __destruct()
	{
		// check if there's a file stored in the cache and get rid of it
		$this->rollback_upload();
	}


	public static function forge_from_comment($comment, $board, $op = false)
	{
		// if this comment doesn't have media data
		if (!isset($comment->media_id) || !$comment->media_id)
		{
			return null;
		}

		return new Media($comment, $board, $op);
	}


	public static function forge_empty($board)
	{
		$media = new \stdClass();
		return new Media($media, $board);
	}


	protected static function p_get_by($board, $where, $value, $op = 0)
	{
		$result = \DB::select()
			->from(\DB::expr(\Radix::get_table($board, '_images')))
			->where($where, $value)
			->as_object()
			->execute()
			->current();

		if ($result)
		{
			return new Media($result, $board, $op);
		}

		throw new MediaNotFoundException(__('The image could not be found.'));
	}


	protected static function p_get_by_media_id($board, $value, $op = 0)
	{
		return static::get_by($board, 'media_id', $value, $op);
	}


	protected static function p_get_by_media_hash($board, $value, $op = 0)
	{
		return static::get_by($board, 'media_hash', $value, $op);
	}


	/**
	 *
	 * @param type $board
	 * @param type $filename
	 */
	protected static function p_get_by_filename($board, $filename)
	{
		$result = \DB::select('media_id')
			->from(\DB::expr(\Radix::get_table($board)))
			->where('media_orig', '=', $filename)
			->as_object()
			->execute()
			->current();

		if ($result)
		{
			return static::get_by_media_id($board, $result->media_id);
		}

		throw new MediaNotFoundException();
	}


	protected static function p_forge_from_upload($board)
	{
		\Upload::process(array(
			'path' => APPPATH.'tmp/media_upload/',
			'max_size' => \Auth::has_access('media.limitless_media') ? 9999 * 1024 * 1024 : $board->max_image_size_kilobytes * 1024,
			'randomize' => true,
			'max_length' => 64,
			'ext_whitelist' => array('jpg', 'jpeg', 'gif', 'png'),
			'mime_whitelist' => array('image/jpeg', 'image/png', 'image/gif')
		));

		if (count(\Upload::get_files()) == 0)
		{
			throw new MediaUploadNoFileException(__('You must upload an image or your image was too large.'));
		}

		if (count(\Upload::get_files()) != 1)
		{
			throw new MediaUploadMultipleNotAllowedException(__('You can\'t upload multiple images.'));
		}

		if (!\Upload::is_valid())
		{
			if (in_array($file['errors'], UPLOAD_ERR_INI_SIZE))
				throw new MediaUploadInvalidException(
					__('The server is misconfigured: the FoOlFuuka upload size should be lower than PHP\'s upload limit.'));

			if (in_array($file['errors'], UPLOAD_ERR_PARTIAL))
				throw new MediaUploadInvalidException(__('You uploaded the file partially.'));

			if (in_array($file['errors'], UPLOAD_ERR_CANT_WRITE))
				throw new MediaUploadInvalidException(__('The image couldn\'t be saved on the disk.'));

			if (in_array($file['errors'], UPLOAD_ERR_EXTENSION))
				throw new MediaUploadInvalidException(__('A PHP extension broke and made processing the image impossible.'));

			if (in_array($file['errors'], UPLOAD_ERR_MAX_SIZE))
				throw new MediaUploadInvalidException(
					\Str::tr(__('You uploaded a too big file. The maxmimum allowed filesize is :sizekb'),
						array('size' => $this->board->max_image_size_kilobytes)));

			if (in_array($file['errors'], UPLOAD_ERR_EXT_NOT_WHITELISTED))
				throw new MediaUploadInvalidException(__('You uploaded a file with an invalid extension.'));

			if (in_array($file['errors'], UPLOAD_ERR_MAX_FILENAME_LENGTH))
				throw new MediaUploadInvalidException(__('You uploaded a file with a too long filename.'));

			if (in_array($file['errors'], UPLOAD_ERR_MOVE_FAILED))
				throw new MediaUploadInvalidException(__('Your uploaded file couldn\'t me moved on the server.'));

			throw new MediaUploadInvalidException(__('Unexpected upload error.'));
		}

		// save them according to the config
		\Upload::save();
		$file = \Upload::get_files(0);

		$media = new \stdClass();
		$media->board = $board;
		$media->media_filename = $file['name'];
		$media->media_size = $file['size'];
		$media->temp_path = $file['saved_to'];
		$media->temp_filename = $file['saved_as'];
		$media->temp_extension = $file['extension'];

		return new Media($media, $board);
	}


	public function get_media_status()
	{
		if ( ! isset($this->media_status))
		{
			try
			{
				$this->media_link = $this->get_link(false);
			}
			catch (MediaNotFoundException $e)
			{

			}
		}

		return $this->media_status;
	}


	public function get_safe_media_hash()
	{
		if ( ! isset($this->safe_media_hash))
		{
			try
			{
				$this->safe_media_hash = $this->get_hash(true);
			}
			catch (MediaNotFoundException $e)
			{
				return null;
			}
		}

		return $this->safe_media_hash;
	}


	public function get_remote_media_link()
	{
		if ( ! isset($this->remote_media_link))
		{
			try
			{
				$this->remote_media_link = $this->get_remote_link();
			}
			catch (MediaNotFoundException $e)
			{
				return null;
			}
		}

		return $this->remote_media_link;
	}


	public function get_media_link()
	{
		if ( ! isset($this->media_link))
		{
			try
			{
				$this->media_link = $this->get_link(false);
			}
			catch (MediaNotFoundException $e)
			{
				return null;
			}
		}

		return $this->media_link;
	}


	public function get_thumb_link()
	{
		if ( ! isset($this->thumb_link))
		{
			try
			{
				$this->thumb_link = $this->get_link(true);
			}
			catch (MediaNotFoundException $e)
			{
				return null;
			}
		}

		return $this->thumb_link;
	}


	public static function process($string)
	{
		return e(@iconv('UTF-8', 'UTF-8//IGNORE', $string));
	}


	public function get_media_filename_processed()
	{
		if ( ! isset($this->media_filename_processed))
		{
			$this->media_filename_processed = static::process($this->media_filename);
		}

		return $this->media_filename_processed;
	}


	public function get_preview_orig_processed()
	{
		if ( ! isset($this->preview_orig_processed))
		{
			$this->preview_orig_processed = static::process($this->preview_orig);
		}

		return $this->preview_orig_processed;
	}


	public function get_media_hash_processed()
	{
		if ( ! isset($this->media_hash_processed))
		{
			$this->media_hash_processed = static::process($this->media_hash);
		}

		return $this->media_hash_processed;
	}


	/**
	 * Get the path to the media
	 *
	 * @param bool $thumbnail if we're looking for a thumbnail
	 * @return bool|string FALSE if it has no image in database, string for the path
	 */
	public function get_dir($thumbnail = false, $precise = false)
	{
		if (!$this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		if ($thumbnail === true)
		{
			if ($this->op)
			{
				if ($precise)
				{
					$image = $this->preview_op;
				}
				else
				{
					$image = $this->preview_op !== null ? $this->preview_op : $this->preview_reply;
				}
			}
			else
			{
				if ($precise)
				{
					$image = $this->preview_reply;
				}
				else
				{
					$image = $this->preview_reply !== null ? $this->preview_reply : $this->preview_op;
				}
			}
		}
		else
		{
			$image = $this->media;
		}

		// if we don't check, the return will return a valid folder that will evaluate file_exists() as TRUE
		if (is_null($image))
		{
			throw new MediaDirNotAvailableException;
		}

		return \Preferences::get('fu.boards.directory').'/'.$this->board->shortname.'/'
			.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
	}


	/**
	 * Get the full URL to the media, and in case switch between multiple CDNs
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @param bool $thumbnail if it's a thumbnail we're looking for
	 * @return bool|string FALSE on not found, a fallback image if not found for thumbnails, or the URL on success
	 */
	public function get_link($thumbnail = false, $force = false)
	{
		$before = \Foolz\Plugin\Hook::forge('foolfuuka\\model\\media.get_link.call.before')
			->setObject($this)
			->setParams(array('thumbnail' => $thumbnail, 'force' => $force))
			->execute()
			->get();

		if ( ! $before instanceof \Foolz\Plugin\Void)
		{
			return $before;
		}

		if ( ! $this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		try
		{
			// locate the image
			if ($thumbnail && file_exists($this->get_dir($thumbnail)) !== false)
			{
				if ($this->op == 1)
				{
					$image = $this->preview_op ? : $this->preview_reply;
				}
				else
				{
					$image = $this->preview_reply ? : $this->preview_op;
				}
			}
		}
		catch (MediaNotFoundException $e)
		{

		}

		try
		{
			// full image
			if ( ! $thumbnail && file_exists($this->get_dir(false)) !== false)
			{
				$image = $this->media;
			}
		}
		catch (MediaNotFoundException $e)
		{

		}

		try
		{
			// fallback if we have the full image but not the thumbnail
			if ($thumbnail && !isset($image) && file_exists($this->get_dir(false)))
			{
				$thumbnail = false;
				$image = $this->media;
			}
		}
		catch (MediaNotFoundException $e)
		{

		}

		if (isset($image))
		{
			$media_cdn = array();
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && \Preferences::get('fu.boards.media_balancers_https'))
			{
				$balancers = \Preferences::get('fu.boards.media_balancers_https');
			}

			if (!isset($balancers) && \Preferences::get('fu.boards.media_balancers'))
			{
				$balancers = \Preferences::get('fu.boards.media_balancers');
			}

			if (isset($balancers))
			{
				$media_cdn = array_filter(preg_split('/\r\n|\r|\n/', $balancers));
			}

			if (!empty($media_cdn) && $this->media_id > 0)
			{
				return $media_cdn[($this->media_id % count($media_cdn))].'/'.$this->board->shortname.'/'
					.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
			}

			return \Preferences::get('fu.boards.url').'/'.$this->board->shortname.'/'
				.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
		}

		if ($thumbnail && $this->media_status === 'normal')
		{
			$this->media_status = 'not-available';
		}

		throw new MediaNotFoundException;
	}


	/**
	 * Get the remote link for media if it's not local
	 *
	 * @return bool|string FALSE if there's no media, local URL if it's not remote, or the remote URL
	 */
	public function get_remote_link()
	{
		if ( ! $this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		if ($this->board->archive && $this->board->images_url != "")
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $this->board->images_url.$this->media_orig;
			}

			return \Uri::create(array($this->board->shortname, 'redirect')).$this->media_orig;
		}
		else
		{
			if (file_exists($this->get_dir()) !== false)
			{
				return $this->get_link();
			}
		}
	}


	/**
	 * Get the post's media hash
	 *
	 * @param mixed $media
	 * @param bool $urlsafe if TRUE it will return a modified base64 compatible with URL
	 * @return bool|string FALSE if media_hash not found, or the base64 string
	 */
	public function get_hash($urlsafe = FALSE)
	{
		if (is_object($this) || is_array($this))
		{
			if (!$this->media_hash)
			{
				throw new MediaHashNotFoundException;
			}

			$media_hash = $this->media_hash;
		}
		else
		{
			if (strlen(trim($media_hash)) == 0)
			{
				return FALSE;
			}
		}

		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === TRUE)
		{
			return static::urlsafe_b64encode(static::urlsafe_b64decode($media_hash));
		}
		else
		{
			return base64_encode(static::urlsafe_b64decode($media_hash));
		}
	}


	public static function urlsafe_b64encode($string)
	{
		$string = base64_encode($string);
		return str_replace(array('+', '/', '='), array('-', '_', ''), $string);
	}


	public static function urlsafe_b64decode($string)
	{
		$string = str_replace(array('-', '_'), array('+', '/'), $string);
		return base64_decode($string);
	}


	/**
	 * Delete media for the selected post
	 *
	 * @param bool $media if full media should be deleted
	 * @param bool $thumb if thumbnail should be deleted
	 * @return bool TRUE on success or if it didn't exist in first place, FALSE on failure
	 */
	public function p_delete($media = true, $thumb = true, $all = false)
	{
		if ( ! $this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($this->total == 1 || $this->banned == 1 || (\Auth::has_access('comment.passwordless_deletion') && $all))
		{
			if ($media === true)
			{
				try
				{
					$media_file = $this->get_dir();
				}
				catch (MediaDirNotAvailableException $e)
				{
					$media_file = null;
				}

				if ($media_file !== null && file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						throw new MediaFileNotFoundException;
					}
				}
			}

			if ($thumb === true)
			{
				$temp = $this->op;

				// remove OP thumbnail
				$this->op = 1;

				try
				{
					$thumb_file = $this->get_dir(true);
				}
				catch(MediaDirNotAvailableException $e)
				{
					$thumb_file = null;
				}

				if ($thumb_file !== null && file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						throw new MediaFileNotFoundException;
					}
				}

				// remove reply thumbnail
				$this->op = 0;
				try
				{
					$thumb_file = $this->get_dir(true);
				}
				catch(MediaDirNotAvailableException $e)
				{
					$thumb_file = null;
				}
				if ($thumb_file !== null && file_exists($thumb_file))
				{
					if ( ! unlink($thumb_file))
					{
						throw new MediaFileNotFoundException;
					}
				}

				$this->op = $temp;
			}
		}

	}


	public function p_ban($global = false)
	{
		if ( ! $global)
		{
			\DB::update(\DB::expr(\Radix::get_table($this->board, '_images')))
				->where('media_id', $this->media_id)
				->value('banned', 1)
				->execute();
			$this->delete(true, true, true);

			return $this;
		}

		$count = \DB::select(\DB::expr('COUNT(*) as count'))
			->from('banned_md5')
			->where('md5', $this->media_hash)
			->as_object()
			->execute()
			->current()
			->count;

		if ( ! $count)
		{
			\DB::insert('banned_md5')
				->set(array('md5' => $this->media_hash))
				->execute();
		}

		foreach (\Radix::get_all() as $radix)
		{
			try
			{
				$media = \Media::get_by_media_hash($radix, $this->media_hash);
				\DB::update(\DB::expr(\Radix::get_table($radix, '_images')))
					->where('media_id', $media->media_id)
					->value('banned', 1)
					->execute();
				$media->delete(true, true, true);
			}
			catch (MediaNotFoundException $e)
			{
				\DB::insert(\DB::expr(\Radix::get_table($radix,'_images')))
					->set(array('media_hash' => $this->media_hash, 'banned' => 1))
					->execute();
			}
		}
	}


	public function rollback_upload()
	{
		if (!is_null($this->temp_filename) && file_exists($this->temp_path.$this->temp_filename))
		{
			unlink($this->temp_path.$this->temp_filename);
		}
	}


	public function p_insert($microtime, $is_op)
	{
		$this->op = $is_op;
		$full_path = $this->temp_path.$this->temp_filename;

		$getimagesize = getimagesize($full_path);

		if (!$getimagesize)
		{
			throw new MediaInsertNotImageException(__('The file you uploaded is not an image.'));
		}

		// if width and height are lower than 25 reject the image
		if ($getimagesize[0] < 25 || $getimagesize[1] < 25)
		{
			throw new MediaInsertImageSizeSmall(__('The image you uploaded is too small.'));
		}

		$this->media_w = $getimagesize[0];
		$this->media_h = $getimagesize[1];
		$this->media_orig = $microtime.'.'.strtolower($this->temp_extension);
		$this->preview_orig = $microtime.'s.'.strtolower($this->temp_extension);
		$this->media_hash = base64_encode(pack("H*", md5(file_get_contents($full_path))));

		$do_thumb = true;
		$do_full = true;

		try
		{
			$duplicate = static::get_by_media_hash($this->board, $this->media_hash);

			// we want the current media to work with the same filenames as previously stored
			$this->media = $duplicate->media;
			$this->preview_op = $duplicate->preview_op;
			$this->preview_reply = $duplicate->preview_reply;

			if ($this->board->min_image_repost_time)
			{
				// if it's -1 it means that image reposting is disabled, so this image shouldn't pass
				if ($this->board->min_image_repost_time == -1)
				{
					throw new MediaInsertImageRepostException(
						__('This image has already been posted once. This board doesn\'t allow image reposting.')
					);
				}

				// we don't have to worry about archives with weird timestamps, we can't post images there
				$duplicate_entry = \DB::select(\DB::expr('COUNT(*) as count'), 'timestamp')
					->from(\DB::expr(\Radix::get_table($this->board)))
					->where('media_id', '=', $duplicate->media_id)
					->where('timestamp', '>', time() - $this->board->min_image_repost_time)
					->order_by('timestamp', 'desc')
					->limit(1)
					->as_object()
					->execute()
					->current();

				if ($duplicate_entry->count)
				{
					$datetime = new \DateTime(date('Y-m-d H:i:s', $duplicate_entry->timestamp + $this->board->min_image_repost_time));
					$remain = $datetime->diff(new \DateTime());

					throw new MediaInsertImageRepostException(
						\Str::tr(
							__('This image has been posted recently. You will be able to post it again in :time.'),
							array('time' =>
								 ($remain->d > 0 ? $remain->d.' '.__('day(s)') : '').' '
								.($remain->h > 0 ? $remain->h.' '.__('hour(s)') : '').' '
								.($remain->i > 0 ? $remain->i.' '.__('minute(s)') : '').' '
								.($remain->s > 0 ? $remain->s.' '.__('second(s)') : ''))
						)
					);
				}
			}

			// if we're here, we got the media
			try
			{
				$duplicate_dir = $duplicate->get_dir();
				if (file_exists($duplicate_dir))
				{
					$do_full = false;
				}
			}
			catch (MediaDirNotAvailableException $e)
			{}

			try
			{
				$duplicate->op = $is_op;
				$duplicate_dir_thumb = $duplicate->get_dir(true, true);
				if (file_exists($duplicate_dir_thumb))
				{
					$duplicate_dir_thumb_size = getimagesize($duplicate_dir_thumb);
					$this->preview_w = $duplicate_dir_thumb_size[0];
					$this->preview_h = $duplicate_dir_thumb_size[1];
					$do_thumb = false;
				}
			}
			catch (MediaDirNotAvailableException $e)
			{}
		}
		catch (MediaNotFoundException $e)
		{}

		if ($do_thumb)
		{
			$thumb_width = $this->board->thumbnail_reply_width;
			$thumb_height = $this->board->thumbnail_reply_height;

			if ($is_op)
			{
				$thumb_width = $this->board->thumbnail_op_width;
				$thumb_height = $this->board->thumbnail_op_height;
			}

			if ( ! file_exists($this->path_from_filename(true, $is_op)))
			{
				mkdir($this->path_from_filename(true, $is_op), 0777, true);
			}

			$return = \Foolz\Plugin\Hook::forge('fu.model.media.insert.resize')
				->setObject($this)
				->setParams(array(
					'thumb_width' => $thumb_width,
					'thumb_height' => $thumb_height,
					'full_path' => $full_path,
					'is_op' => $is_op
				))
				->execute()
				->get();

			if ($return instanceof \Foolz\Plugin\Void)
			{
				if ($this->board->enable_animated_gif_thumbs && strtolower($this->temp_extension) === 'gif')
				{
					exec("convert ".$full_path." -coalesce -treedepth 4 -colors 256 -quality 80 -background none ".
						"-resize \"".$thumb_width."x".$thumb_height.">\" ".$this->path_from_filename(true, $is_op, true));
				}
				else
				{
					exec("convert ".$full_path."[0] -quality 80 -background none ".
						"-resize \"".$thumb_width."x".$thumb_height.">\" ".$this->path_from_filename(true, $is_op, true));
				}
			}



			$thumb_getimagesize = getimagesize($this->path_from_filename(true, $is_op, true));
			$this->preview_w = $thumb_getimagesize[0];
			$this->preview_h = $thumb_getimagesize[1];
		}

		if ($do_full)
		{
			if (!file_exists($this->path_from_filename()))
			{
				mkdir($this->path_from_filename(), 0777, true);
			}

			copy($full_path, $this->path_from_filename(false, false, true));
		}

		if (function_exists('exif_read_data') && in_array(strtolower($this->temp_extension), array('jpg', 'jpeg', 'tiff')))
		{
			$media_data = null;
			getimagesize($full_path, $media_data);

			if ( ! isset($media_data['APP1']) || strpos($media_data['APP1'], 'Exif') === 0)
			{
				$exif = exif_read_data($full_path);

				if ($exif !== false)
				{
					$this->exif = $exif;
				}
			}
		}

		return $this;
	}


	public function p_path_from_filename($thumbnail = false, $is_op = false, $with_filename = false)
	{
		$dir = \Preferences::get('fu.boards.directory').'/'.$this->board->shortname.'/'.
			($thumbnail ? 'thumb' : 'image').'/';

		// we first check if we have media/preview_op/preview_reply available to reuse the value
		if ($thumbnail)
		{
			if ($is_op && $this->preview_op !== null)
			{
				return $dir.'/'.substr($this->preview_op, 0, 4).'/'.substr($this->preview_op, 4, 2).'/'.
					($with_filename ? $this->preview_op : '');
			}
			else if ( ! $is_op && $this->preview_reply !== null)
			{
				return $dir.'/'.substr($this->preview_reply, 0, 4).'/'.substr($this->preview_reply, 4, 2).'/'.
					($with_filename ? $this->preview_reply : '');
			}

			// we didn't have media/preview_op/preview_reply so fallback to making a new file
			return $dir.'/'.substr($this->preview_orig, 0, 4).'/'.substr($this->preview_orig, 4, 2).'/'.
				($with_filename ? $this->preview_orig : '');
		}
		else
		{
			if ($this->media !== null)
			{
				return $dir.'/'.substr($this->media, 0, 4).'/'.substr($this->media, 4, 2).'/'.
					($with_filename ? $this->media : '');
			}

			// we didn't have media/preview_op/preview_reply so fallback to making a new file
			return $dir.'/'.substr($this->media_orig, 0, 4).'/'.substr($this->media_orig, 4, 2).'/'.
				($with_filename ? $this->media_orig : '');
		}
	}
}