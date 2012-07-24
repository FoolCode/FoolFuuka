<?php


class Comment
{












	/**
	 * Manipulate the sent media and store it if there is no same media in the database
	 * Notice: currently works only with images
	 * Notice: this is not a view output function, this is a function to insert in database!
	 *
	 * @param object $board
	 * @param object $post database row for the post
	 * @param array $file the file array from the CodeIgniter upload class
	 * @param string $media_hash the media_hash for the media
	 * @param null|object used in recursion if the media is found in the database
	 * @return array|bool An array of data necessary for the board database insert
	 */
	private function p_process_media($board, $post_id, $file, $media_hash, $duplicate = NULL)
	{
		// only allow media on internal boards
		if ($board->archive)
		{
			return FALSE;
		}

		$preliminary_check = @getimagesize($file['full_path']);

		if(!$preliminary_check)
		{
			return array('error' => __('The file you submitted doesn\'t seem to be an image.'));
		}

		// if width and height are lower than 25 reject the image
		if($preliminary_check[0] < 25 || $preliminary_check[1] < 25)
		{
			return array('error' => __('The image you submitted is too small.'));
		}


		// default variables
		$media_exists = FALSE;
		$thumb_exists = FALSE;

		// only run the check when iterated with duplicate
		if ($duplicate === NULL)
		{
			// check *_images table for media hash
			$check = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE media_hash = ?
				LIMIT 0, 1
			',
				array($media_hash)
			);

			// if exists, re-run process with duplicate set
			if ($check->num_rows() > 0)
			{
				$check_row = $check->row();

				// do we have some image reposting constraint?
				if($board->min_image_repost_hours == 0 || $this->auth->is_mod_admin())
				{
					// do nothing, 0 means that there's no time constraint
					// also admins and mods can repost however mich they want
				}
				else if($board->min_image_repost_hours == -1)
				{
					// don't allow reposting, ever
					return array('error' =>
						__('This image has already been posted once. This board doesn\'t allow image reposting'));
				}
				else
				{
					// check if there's a recent image with the same media_id
					$constraint = $this->db->query('
						SELECT *
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_id = ? AND timestamp > ?
					', array($check_row->media_id, time() - $board->min_image_repost_hours * 60 * 60));

					if($constraint->num_rows() > 0)
					{
						return array('error' => sprintf(
							__('You must wait up to %s hours to repost this image.'),
							$board->min_image_repost_hours)
						);
					}
				}

				return $this->process_media($board, $post_id, $file, $media_hash, $check_row);
			}
		}

		// generate unique filename with timestamp, this will be stored with the post
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($file['file_ext']);
		$thumb_filename = $media_unixtime . 's' . strtolower($file['file_ext']);

		// set default locations of media directories and image directory structure
		$board_directory = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/';
		$thumb_filepath = $board_directory . 'thumb/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';
		$media_filepath = $board_directory . 'image/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';

		// PHP must be compiled with --enable-exif
		// exif can be grabbed only from jpg and tiff
		if(function_exists('exif_read_data')
			&& in_array(strtolower(trim($file['file_ext'], '.')), array('jpg', 'jpeg', 'tiff')))
		{
			$exif = exif_read_data($file['full_path']);

			if($exif === FALSE)
			{
				$exif = NULL;
			}
		}
		else
		{
			$exif = NULL;
		}

		// check for any type of duplicate records or information and override default locations
		if ($duplicate !== NULL)
		{
			// handle full media
			if ($duplicate->media !== NULL)
			{
				$media_exists = TRUE;

				$media_existing = $duplicate->media;
				$media_filepath = $board_directory . 'image/'
					. substr($duplicate->media, 0, 4) . '/' . substr($duplicate->media, 4, 2) . '/';
			}

			// generate full file paths for missing files only
			if ($duplicate->media === NULL || file_exists($media_filepath . $duplicate->media) === FALSE)
			{
				if(!file_exists($media_filepath))
					mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			}

			// handle thumbs
			if ($post_id == 0)
			{
				// thumb op
				if ($duplicate->preview_op !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_op;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_op, 0, 4) . '/' . substr($duplicate->preview_op, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_op === NULL || file_exists($media_filepath . $duplicate->preview_op) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
			else
			{
				// thumb re
				if ($duplicate->preview_reply !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_reply;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_reply, 0, 4) . '/' . substr($duplicate->preview_reply, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_reply === NULL || file_exists($media_filepath . $duplicate->preview_reply) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
		}
		else
		{
			// generate full file paths for everything
			if(!file_exists($media_filepath))
				mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			if(!file_exists($thumb_filepath))
				mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
		}

		// relocate the media file to proper location
		if (!copy($file['full_path'], $media_filepath . (($media_exists) ? $media_existing : $media_filename)))
		{
			log_message('error', 'post.php/process_media: failed to move media file');
			return FALSE;
		}

		// remove the media file
		if (!unlink($file['full_path']))
		{
			log_message('error', 'post.php/process_media: failed to remove media file from cache directory');
		}

		// determine the correct thumbnail dimensions
		if ($post_id == 0)
		{
			$thumb_width = $board->thumbnail_op_width;
			$thumb_height = $board->thumbnail_op_height;
		}
		else
		{
			$thumb_width = $board->thumbnail_reply_width;
			$thumb_height = $board->thumbnail_reply_height;
		}

		// generate thumbnail
		$imagemagick = locate_imagemagick();
		$media_config = array(
			'image_library' => ($imagemagick) ? 'ImageMagick' : 'GD2',
			'library_path'  => ($imagemagick) ? $this->ff_imagemagick->path : '',
			'source_image'  => $media_filepath . (($media_exists) ? $media_existing : $media_filename),
			'new_image'     => $thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename),
			'width'         => ($file['image_width'] > $thumb_width) ? $thumb_width : $file['image_width'],
			'height'        => ($file['image_height'] > $thumb_height) ? $thumb_height : $file['image_height'],
		);

		// leave this NULL so it processes normally
		$switch = Plugins::run_hook('fu_post_model_process_media_switch_resize', array($media_config));

		// if plugin returns false, error
		if(isset($switch['return']) && $switch['return'] === FALSE)
		{
			log_message('error', 'post.php/process_media: failed to generate thumbnail');
			return FALSE;
		}

		if(is_null($switch) || is_null($switch['return']))
		{
			$this->load->library('image_lib');

			$this->image_lib->initialize($media_config);
			if (!$this->image_lib->resize())
			{
				log_message('error', 'post.php/process_media: failed to generate thumbnail');
				return FALSE;
			}

			$this->image_lib->clear();
		}

		$thumb_dimensions = @getimagesize($thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename));

		return array(
			'preview_orig' => $thumb_filename,
			'thumb_width' => $thumb_dimensions[0],
			'thumb_height'=> $thumb_dimensions[1],
			'media_filename' => $file['file_name'],
			'width' => $file['image_width'],
			'height'=> $file['image_height'],
			'size' => floor($file['file_size'] * 1024),
			'media_hash' => $media_hash,
			'media_orig' => $media_filename,
			'exif' => !is_null($exif)?json_encode($exif):NULL,
			'unixtime' => $media_unixtime,
		);
	}




















	/**
	 * Sets the media hash to banned through all boards
	 *
	 * @param string $hash the hash to ban
	 * @param bool $delete if it should delete the media through all the boards
	 * @return bool
	 */
	private function p_ban_media($media_hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . '
			(
				md5
			)
			VALUES
			(
				?
			)
		',
			array($media_hash)
		);

		// update all local _images table
		foreach ($this->radix->get_all() as $board)
		{
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board, '_images') . '
				(
					media_hash, media, preview_op, preview_reply, total, banned
				)
				VALUES
				(
					?, ?, ?, ?, ?, ?
				)
				ON DUPLICATE KEY UPDATE banned = 1
			',
				array($media_hash, NULL, NULL, NULL, 0, 1)
			);
		}

		// delete media files if TRUE
		if ($delete === TRUE)
		{
			$posts = array();

			foreach ($this->radix->get_all() as $board)
			{
				$posts[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($board->id) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_hash = ' . $this->db->escape($media_hash) . '
					)
				';
			}

			$query = $this->db->query(implode('UNION', $posts));
			if ($query->num_rows() == 0)
			{
				log_message('error', 'post.php/ban_media: unable to locate posts containing media_hash');
				return FALSE;
			}

			foreach ($query->result() as $post)
			{
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
			}
		}

		return TRUE;
	}

}

/* End of file post_model.php */
/* Location: ./application/models/post_model.php */
