<?php

namespace Foolfuuka\Model;

class MediaNotFoundException extends \FuelException {}
class MediaHashNotFoundException extends MediaNotFoundException {}
class MediaDirNotAvailableException extends MediaNotFoundException {}
class MediaFileNotFoundException extends MediaNotFoundException {}
class MediaHiddenException extends MediaNotFoundException {}
class MediaHiddenDayException extends MediaNotFoundException {}
class MediaBannedException extends MediaNotFoundException {}

class MediaInsertException extends \FuelException {}
class MediaInsertNoFileException extends MediaInsertException {}
class MediaInsertMultipleNotAllowedException extends MediaInsertException {}
class MediaInsertInvalidException extends MediaInsertException {}

class Media extends \Model\Model_Base
{

	public $op; // required to know the size of the thumbnail

	public $media_id = 0;
	public $spoiler = 0;
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


	protected static function p_get_fields()
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
		if ($this->preview_w === 0 || $this->preview_h === 0)
		{
			unset($this->preview_w, $this->preview_h);
		}
	}


	public function __destruct()
	{
		// check if there's a file stored in the cache and get rid of it
		$this->rollback_upload();
	}


	protected static function p_forge_from_comment($comment, $board, $op = false)
	{
		// if this comment doesn't have media data
		if (!isset($comment->media_id) || !$comment->media_id)
		{
			return null;
		}

		return new Media($comment, $board, $op);
	}


	protected static function p_forge_empty($board)
	{
		$media = new \stdClass();
		return new Media($media, $board);
	}



	protected static function p_forge_from_upload($board)
	{
		\Upload::process(array(
			'path' => APPPATH.'tmp/media_upload/',
			'max_size' => $board->max_image_size_kilobytes * 1024,
			'randomize' => true,
			'max_length' => 64,
			'ext_whitelist' => array('jpg', 'jpeg', 'gif', 'png'),
			'mime_whitelist' => array('image/jpeg', 'image/png', 'image/gif')
		));

		if (count(\Upload::get_files()) == 0)
		{
			throw new MediaUploadNoFileException(__('You must upload an image.'));
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


	public function __get($name)
	{
		switch ($name)
		{
			case 'media_status':
				try { $this->media_link = $this->get_media_link(); }
				catch (MediaNotFoundException $e) { return null; }
				return $this->media_status;
			case 'safe_media_hash':
				try { return $this->safe_media_hash = $this->get_media_hash(true); }
				catch (MediaNotFoundException $e) { return null; }
			case 'remote_media_link':
				try { return $this->remote_media_link = $this->get_remote_media_link(); }
				catch (MediaNotFoundException $e) { return null; }
			case 'media_link':
				try { return $this->media_link = $this->get_media_link(); }
				catch (MediaNotFoundException $e) { return null; }
			case 'thumb_link':
				try { return $this->thumb_link = $this->get_media_link(true); }
				catch (MediaNotFoundException $e) { return null; }
			case 'preview_w':
			case 'preview_h':
				if ($this->board->archive && $this->spoiler)
				{
					try
					{
						$imgsize = \Cache::get('comment.'.$this->board->id.'.'.$this->doc_id.'_spoiler_size');
					}
					catch (\CacheNotFoundException $e)
					{
						try
						{
							$imgpath = $this->get_media_dir(true);
							$imgsize = false;

							if ($imgpath)
							{
								$imgsize = @getimagesize($imgpath);
							}

							\Cache::set('comment.'.$this->board->id.'.'.$this->doc_id.'_spoiler_size', $imgsize, 86400);

							if ($imgsize !== FALSE)
							{
								$this->preview_h = $imgsize[1];
								$this->preview_w = $imgsize[0];
							}

							return $this->$name;
						}
						catch (MediaNotFoundException $e)
						{

						}
					}
				}
				$this->preview_w = 0;
				$this->preview_h = 0;
				return 0;
		}

		if (substr($name, -10) === '_processed')
		{
			$processing_name = substr($name, 0, strlen($name) - 10);
			return $this->$name = e(@iconv('UTF-8', 'UTF-8//IGNORE', $this->$processing_name));
		}

		return null;
	}


	/**
	 * Get the path to the media
	 *
	 * @param bool $thumbnail if we're looking for a thumbnail
	 * @return bool|string FALSE if it has no image in database, string for the path
	 */
	protected function p_get_media_dir($thumbnail = false, $precise = false)
	{
		if (!$this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		if ($thumbnail === true)
		{
			if ($this->op == 1)
			{
				if ($precise)
				{
					$image = $this->preview_op;
				}
				else
				{
					$image = !is_null($this->preview_op) ? $this->preview_op : $this->preview_reply;
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
					$image = !is_null($this->preview_reply) ? $this->preview_reply : $this->preview_op;
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

		return \Preferences::get('fu.boards.directory', DOCROOT.'content/').'/'.$this->board->shortname.'/'
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
	protected function p_get_media_link($thumbnail = false)
	{
		if (!$this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		$this->media_status = 'available';

		// these features will only affect guest users
		if ($this->board->hide_thumbnails && !\Auth::has_access('comment.show_hidden_thumbnails'))
		{
			// hide all thumbnails for the board
			if (!$this->board->hide_thumbnails)
			{
				$this->media_status = 'forbidden';
				throw new MediaHiddenException;
			}

			// add a delay of 1 day to all thumbnails
			if ($this->board->delay_thumbnails && ($this->timestamp + 86400) > time())
			{
				$this->media_status = 'forbidden-24h';
				throw new MediaHiddenDayException;
			}
		}

		// this post contain's a banned media, do not display
		if ($this->banned == 1)
		{
			$this->media_status = 'banned';
			throw new MediaBannedException;
		}

		try
		{
			// locate the image
			if ($thumbnail && file_exists($this->get_media_dir($thumbnail)) !== false)
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
			if (!$thumbnail && file_exists($this->get_media_dir(false)))
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
			if ($thumbnail && !isset($image) && file_exists($this->get_media_dir(false)))
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

			return \Preferences::get('fu.boards.url', \Uri::base()).'/'.$this->board->shortname.'/'
				.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
		}

		$this->media_status = 'not-available';
		return FALSE;
	}


	/**
	 * Get the remote link for media if it's not local
	 *
	 * @return bool|string FALSE if there's no media, local URL if it's not remote, or the remote URL
	 */
	protected function p_get_remote_media_link()
	{
		if (!$this->media_hash)
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
			try
			{
				if (file_exists($this->get_media_dir()) !== false)
				{
					return $this->get_media_link();
				}
			}
			catch (MediaNotFoundException $e)
			{
				return false;
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
	protected function p_get_media_hash($urlsafe = FALSE)
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


	protected static function p_urlsafe_b64encode($string)
	{
		$string = base64_encode($string);
		return str_replace(array('+', '/', '='), array('-', '_', ''), $string);
	}


	protected static function p_urlsafe_b64decode($string)
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
	protected function p_delete_media($media = true, $thumb = true)
	{
		if (!$this->media_hash)
		{
			throw new MediaHashNotFoundException;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($this->total == 1 || $this->banned == 1 || \Auth::has_access('comment.passwordless_deletion'))
		{
			if ($media === true)
			{
				$media_file = $this->get_media_dir();
				if (file_exists($media_file))
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
				$thumb_file = $this->get_media_dir(true);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						throw new MediaFileNotFoundException;
					}
				}

				// remove reply thumbnail
				$this->op = 0;
				$thumb_file = $this->get_media_dir(TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						throw new MediaFileNotFoundException;
					}
				}

				$this->op = $temp;
			}
		}

	}


	public function rollback_upload()
	{
		if (!is_null($this->temp_filename) && file_exists($this->temp_path.$this->temp_filename))
			unlink($this->temp_path.$this->temp_filename);
	}


	public function insert($microtime, $spoiler, $is_op)
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


		$this->spoiler = $spoiler;
		$this->media_w = $getimagesize[0];
		$this->media_h = $getimagesize[1];
		$this->media_orig = $microtime.'.'.$this->temp_extension;
		$this->preview_orig = $microtime.'s.'.$this->temp_extension;
		$this->media_hash = base64_encode(pack("H*", md5(file_get_contents($full_path))));

		$hash_query = \DB::select()->from(\DB::expr(Radix::get_table($this->board, '_images')))
				->where('media_hash', $this->media_hash)->as_object()->execute();

		$do_thumb = true;
		$do_full = true;

		// do we have this file already in database?
		if (count($hash_query) === 1)
		{
			$duplicate = $hash_query->current();
			$duplicate = new Media($duplicate, $this->board);

			try
			{
				$duplicate_dir = $duplicate->get_media_dir();
				if (file_exists($duplicate_dir))
				{
					$do_full = false;
				}
			}
			catch (MediaDirNotAvailableException $e)
			{}

			try
			{
				$duplicate_dir_thumb = $duplicate->get_media_dir(true, true);
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

		if ($do_thumb)
		{
			$thumb_width = $this->board->thumbnail_reply_width;
			$thumb_height = $this->board->thumbnail_reply_height;
			if ($is_op)
			{
				$thumb_width = $this->board->thumbnail_op_width;
				$thumb_height = $this->board->thumbnail_op_height;
			}

			if (!file_exists($this->path_from_filename(true)))
			{
				mkdir($this->path_from_filename(true), 0777, true);
			}

			\Image::forge(array('driver' => 'imagemagick', 'quality' => 80, 'temp_dir' => APPPATH.'/tmp/'))
				->load($full_path)
				->resize($thumb_width, $thumb_height)
				->save($this->path_from_filename(true).$this->preview_orig);

			$thumb_getimagesize = getimagesize($this->path_from_filename(true).$this->preview_orig);
			$this->preview_w = $thumb_getimagesize[0];
			$this->preview_h = $thumb_getimagesize[1];
		}

		if ($do_full)
		{
			if (!file_exists($this->path_from_filename()))
			{
				mkdir($this->path_from_filename(), 0777, true);
			}

			copy($full_path, $this->path_from_filename().$this->media_orig);
		}

		if (function_exists('exif_read_data') && in_array(strtolower($this->temp_extension), array('jpg', 'jpeg', 'tiff')))
		{
			$exif = exif_read_data($full_path);

			if ($exif !== FALSE)
			{
				$this->exif = $exif;
			}
		}

		return $this;
	}


	public function path_from_filename($thumbnail = false)
	{
		return \Preferences::get('fu.boards.directory', DOCROOT.'content/boards').'/'.$this->board->shortname.'/'.
			($thumbnail ? 'thumb' : 'image').'/'.
			substr($this->media_orig, 0, 4).'/'.substr($this->media_orig, 4, 2).'/';
	}

}