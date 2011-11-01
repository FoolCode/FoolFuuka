<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Page extends DataMapper
{

	var $has_one = array('chapter');
	var $has_many = array();
	var $validation = array(
		'chapter_id' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Chapter ID'
		),
		'filename' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Filename'
		),
		'hidden' => array(
			'rules' => array(),
			'label' => 'Hidden'
		),
		'description' => array(
			'rules' => array(),
			'label' => 'Description'
		),
		'thumbnail' => array(
			'rules' => array('required', 'max_length' => 512),
			'label' => 'Thumbnail'
		),
		'lastseen' => array(
			'rules' => array(),
			'label' => 'Lastseen'
		),
		'creator' => array(
			'rules' => array('required'),
			'label' => 'Creator'
		),
		'editor' => array(
			'rules' => array('required'),
			'label' => 'Editor'
		),
		'width' => array(
			'rules' => array('required'),
			'label' => 'Width'
		),
		'height' => array(
			'rules' => array('required'),
			'label' => 'Height'
		),
		'mime' => array(
			'rules' => array('required'),
			'label' => 'Mime type'
		),
		'grayscale' => array(
			'rules' => array('required'),
			'label' => 'Is it grayscale?'
		),
		'thumbwidth' => array(
			'rules' => array('required'),
			'label' => 'Thumbnail width'
		),
		'thumbheight' => array(
			'rules' => array('required'),
			'label' => 'Thumbnail height'
		),
		'size' => array(
			'rules' => array('required'),
			'label' => 'Size'
		),
		'thumbsize' => array(
			'rules' => array('required'),
			'label' => 'Thumbnail size'
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct(NULL);
		if (!is_null($id))
		{
			$this->where('id', $id)->get();
		}
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	/**
	 * Overwrite of the get() function to add filters to the search.
	 * Refer to DataMapper ORM for get() function details.
	 *
	 * @author	Woxxy
	 * @param	integer|NULL $limit Limit the number of results.
	 * @param	integer|NULL $offset Offset the results when limiting.
	 * @return	DataMapper Returns self for method chaining.
	 */
	public function get($limit = NULL, $offset = NULL)
	{
		// Get the CodeIgniter instance, since it isn't set in this file.
		$CI = & get_instance();

		// Check if the user is allowed to see protected chapters.
		if (!$CI->tank_auth->is_allowed())
			$this->where('hidden', 0);

		return parent::get($limit, $offset);
	}


	/**
	 * Overwrite of the get_iterated() function to add filters to the search.
	 * Refer to DataMapper ORM for get_iterated() function details.
	 *
	 * @author	Woxxy
	 * @param	integer|NULL $limit Limit the number of results.
	 * @param	integer|NULL $offset Offset the results when limiting.
	 * @return	DataMapper Returns self for method chaining.
	 */
	public function get_iterated($limit = NULL, $offset = NULL)
	{
		// Get the CodeIgniter instance, since it isn't set in this file.
		$CI = & get_instance();

		// Check if the user is allowed to see protected chapters.
		if (!$CI->tank_auth->is_allowed())
			$this->where('hidden', 0);

		/**
		 * @todo figure out why those variables don't get unset... it would be
		 * way better to use the iterated in almost all cases in FoOlSlide
		 */
		return parent::get_iterated($limit, $offset);
	}


	/**
	 * Comodity get() function that fetches extra data for the chapter selected.
	 * It doesn't get the pages. For pages, see: $this->get_pages()
	 *
	 * @author	Woxxy
	 * @param	integer|NULL $limit Limit the number of results.
	 * @param	integer|NULL $offset Offset the results when limiting.
	 * @return	DataMapper Returns self for method chaining.
	 */
	public function get_bulk($limit = NULL, $offset = NULL)
	{
		// Call the get()
		$result = $this->get($limit, $offset);
		// Return instantly on false.
		if (!$result)
			return $result;

		// For each item we fetched, add the data, beside the pages
		foreach ($this->all as $item)
		{
			$item->comic = new Comic($this->comic_id);
			$teams = new Team();
			$item->teams = $teams->get_teams($this->team_id, $this->joint_id);
		}

		return $result;
	}


	/**
	 * Sets the $this->chapter and $this->chapter->comic variables if it hasn't 
	 * been done before.
	 *
	 * @author	Woxxy
	 * @return	boolean True on success, false on failure.
	 */
	public function get_chapter()
	{
		// Check if the variable is not yet set, in order to save a databse read.
		if (!isset($this->chapter))
		{
			$this->chapter = new Chapter($this->chapter_id);
			if ($this->chapter->result_count() < 1)
			{
				log_message('error', 'get_chapter: chapter not found');
				unset($this->chapter);
				return FALSE;
			}
			if (!$this->chapter->get_comic())
			{
				log_message('error', 'get_chapter: comic not found');
				return FALSE;
			}
		}

		// All good, return true.
		return TRUE;
	}


	/**
	 * Function to create a new page from a $filedata array. It deals with
	 * processing the image data, checks if it's an image, puts values like
	 * height in the variables, sends to database function and file function.
	 * 
	 * This function fails silently for the user. For now.
	 *
	 * @author	Woxxy
	 * @param	array|$filedata It's an array of data produced by CodeIgniter 
	 * 			upload function
	 * @param	int|$chapter_id The ID of the chapter
	 * @param	int|$hidden NOT USED
	 * @param	string|$description NOT USED
	 * @return	boolean true on success, false on failure.
	 */
	public function add_page($path, $filename, $chapter_id)
	{

		// Check if that file is actually an image
		if (!$imagedata = @getimagesize($path))
		{
			log_message('error', 'add_page: uploaded file doesn\'t seem to be an image');
			return false;
		}

		// Let's set some variables
		$this->chapter_id = $chapter_id;

		// Load the chapter and comic as soon as possible.
		if (!$this->get_chapter())
		{
			log_message('error', 'add_page: couldn\'t find related chapter');
			return false;
		}

		// Throw the image to the folder with this function
		// While we aren't looking, this also creates the thumbnails
		if (!$this->add_page_file($path, $filename))
		{
			log_message('error', 'add_page: failed creating file');
			return false;
		}

		// We need the dir to the chapter
		$dir = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/";


		// $imagedata = @getimagesize($filedata["server_path"]);
		// We had set $imagedata before
		// We already have the thumbnail! Get data for that too.
		$thumbdata = @getimagesize($dir . "thumb_" . $filename);

		// If a page in this chapter with the same filename exists, pick its ID and start updating it
		// This makes so everything gets overwritten with no error
		$page = new Page();
		$page->where('chapter_id', $this->chapter_id)->where('filename', $filename)->get();
		if ($page->result_count() > 0)
		{
			$this->id = $page->id;
		}

		// Prepare the variables
		$this->filename = $filename;
		$this->description = (find_imagick()) ? 'im' : '';
		$this->thumbnail = "thumb_";
		$this->width = $imagedata["0"];
		$this->height = $imagedata["1"];
		$this->size = filesize($dir . $filename);
		$this->mime = image_type_to_mime_type($imagedata["2"]);
		$this->thumbwidth = $thumbdata["0"];
		$this->thumbheight = $thumbdata["1"];
		$this->thumbsize = filesize($dir . "thumb_" . $filename);

		// Check from the thumbnail if the image is in colors or not
		$is_bw = $this->is_bw();
		if ($is_bw == "bw")
			$this->grayscale = 1;
		else if ($is_bw == "rgb")
			$this->grayscale = 0;
		else
		{
			log_message('error', 'add_page: error while determining if black and white or RGB');
			return false;
		}

		// Finally, save everything to database, and, in case of failure, remove the image files
		if (!$this->update_page_db())
		{
			log_message('error', 'add_page: failed writing to database');
			$this->remove_page_file();
			return false;
		}
		
		$this->on_change($chapter_id);

		// All good
		return true;
	}


	/**
	 * Removes the page from database and from the direcotry
	 * There's no going back from this!
	 *
	 * @author	Woxxy
	 * @return	object basically chapter with comic inside
	 */
	public function remove_page()
	{
		// Get chapter and comic to be sure they're set
		$this->get_chapter();
		$chapter_id = $this->chapter->id;
		// Remove the files
		if (!$this->remove_page_file())
		{
			log_message('error', 'remove_page: failed to delete dir');
			return false;
		}

		// Remove from database
		if (!$this->remove_page_db())
		{
			log_message('error', 'remove_page: failed to delete database entry');
			return false;
		}
		
		$this->on_change($chapter_id);

		// Return both comic and chapter for comfy redirects
		return $this->chapter;
	}


	/**
	 * Handles both creating of new pages in the database and editing old ones.
	 * It determines if it should update or not by checking if $this->id has
	 * been set. It can get the values from both the $data array and direct 
	 * variable assignation. Be aware that array > variables. The latter ones
	 * will be overwritten. Particularly, the variables that the user isn't
	 * allowed to set personally are unset and reset with the automated values.
	 * It's quite safe to throw stuff at it.
	 * 
	 * If you're overwriting an image, at this point the ID would be alreasy set.
	 *
	 * @author	Woxxy
	 * @param	array $data contains the minimal data
	 * @return	boolean true on success, false on failure
	 */
	public function update_page_db($data = array())
	{
		// Check if we're updating or creating a new entry by looking at $data["id"].
		// False is returned if the ID was not found.
		if (isset($data["id"]))
		{
			$this->where("id", $data["id"])->get();
			if ($chapter->result_count() == 0)
			{
				set_notice('error', _('There isn\'t a page in the database related to this ID.'));
				log_message('error', 'update_page_db: failed to find requested id');
				return false;
			}
		}
		else
		{
			// let's set the creator name if it's a new entry
			if (!isset($this->chapter_id))
			{
				set_notice('error', _('There was no selected chapter.'));
				log_message('error', 'update_page_db: chapter_id was not set');
				return false;
			}

			// let's also check that the related comic is defined, and exists
			$chapter = new Chapter($this->chapter_id);
			if ($chapter->result_count() == 0)
			{
				set_notice('error', _('The selected chapter doesn\'t exist.'));
				log_message('error', 'update_page_db: chapter_id does not exist in comic database');
				return false;
			}

			$this->creator = $this->logged_id();
		}

		// always set the editor name
		$this->editor = $this->logged_id();

		// Unset sensible variables
		// Not even admins should touch these, for database stability.
		// Yeah, basically everything, as this function is fully automated
		unset($data["creator"]);
		unset($data["editor"]);
		unset($data["filename"]);
		unset($data["description"]);
		unset($data["thumnail"]);
		unset($data["mime"]);
		unset($data["size"]);
		unset($data["height"]);
		unset($data["width"]);
		unset($data["thumbheight"]);
		unset($data["thumbwidth"]);
		unset($data["thumbsize"]);


		// Loop over the array and assign values to the variables.
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}

		// let's save and give some error check. Push false if fail, true if good.
		$success = $this->save();
		if (!$success)
		{
			if (!$this->valid)
			{
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'update_page_db: failed validation');
			}
			else
			{
				set_notice('error', _('Failed to write to database for unknown reasons.'));
				log_message('error', 'update_page_db: failed to save');
			}
			return false;
		}
		else
		{
			// Good job!
			return true;
		}
	}


	/**
	 * Removes the page from the database (not the files).
	 *
	 * @author	Woxxy
	 * @return	true on success, false on failure
	 */
	public function remove_page_db()
	{
		// All we have to do is deleting
		if (!$this->delete())
		{
			set_notice('error', _('Failed to remove the page from the database.'));
			log_message('error', 'remove_page_db: failed remove page entry');
			return false;
		}
		return true;
	}


	/**
	 * Copies the image file (usually uploaded or in the cache) to the directory and
	 * creates the thumbnail.
	 * 
	 * This function doesn't remove the source file, that's done by the calling
	 * function. We don't want to kill images in here!
	 * 
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function add_page_file($path, $filename)
	{
		// Let's make sure the chapter and comic is set
		$this->get_chapter();

		// Get the directory and copy the image in the cache to the directory
		$dir = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/";
		if (!copy($path, $dir . $filename))
		{
			set_notice('error', _('Failed to add the page\'s file. Please, check file permissions.'));
			log_message('error', 'add_page_file: failed to create/copy the image');
			return false;
		}

		// Prepare the image library to create the thumbnail
		$CI = & get_instance();
		$CI->load->library('image_lib');
		$img_config['image_library'] = (find_imagick()) ? 'ImageMagick' : 'GD2'; // Use GD2 as fallback
		$img_config['library_path'] = (find_imagick()) ? (get_setting('fs_serv_imagick_path') ? get_setting('fs_serv_imagick_path') : '/usr/bin') : ''; // If GD2, use none
		$img_config['source_image'] = $path;
		$img_config["new_image"] = $dir . "thumb_" . $filename;
		$img_config['width'] = 250;
		$img_config['height'] = 250;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['master_dim'] = 'auto';
		$CI->image_lib->initialize($img_config);

		// Resize to create the thumbnail
		if (!$CI->image_lib->resize())
		{
			set_notice('error', _('Failed to create the thumbnail of the page.'));
			log_message('error', 'add_page_file: failed to create thumbnail');
			return false;
		}

		// Clear the image library for who knows who else calls it
		$CI->image_lib->clear();

		// Good
		return true;
	}


	/**
	 * Removes the image file and the thumbnail.
	 *
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function remove_page_file()
	{
		// Make sure chapter and comic are set
		$this->get_chapter();

		// Get the chapter directory
		$dir = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/";

		// Remove the image
		if (!unlink($dir . $this->filename))
		{
			set_notice('error', _('Failed to remove the page\'s file. Please, check file permissions.'));
			log_message('error', 'remove_page_file: failed to delete image');
			return false;
		}

		// Remove the thumbnail
		if (!unlink($dir . "thumb_" . $this->filename))
		{
			set_notice('error', _('Failed to remove the page\'s thumbnail. Please, check file permissions.'));
			log_message('error', 'remove_page_file: failed to delete thumbnail');
			return false;
		}

		// Good
		return true;
	}


	/**
	 * Triggers the necessary calculations when a page is added, edited or removed
	 * 
	 * @author Woxxy
	 */
	public function on_change($chapter_id)
	{
		// cleanup the archive if there is one for this chapter
		$archive = new Archive();
		$archive->where('chapter_id', $chapter_id)->get();
		if ($archive->result_count() == 1)
		{
			$archive->remove();
		}
	}


	/**
	 * Checks if the database entry reflects the files for the page
	 *
	 * @author Woxxy
	 * @return array with error codes (missing_page, missing_thumbnail)
	 */
	public function check($repair = FALSE)
	{
		// Let's make sure the chapter and comic is set
		if ($this->get_chapter() === FALSE)
		{
			$errors[] = 'page_chapter_entry_not_found';
			set_notice('warning', _('Found a page entry without a chapter entry, ID: ' . $this->id));
			log_message('debug', 'check: page entry without chapter entry');

			if ($repair)
			{
				$this->remove_page_db();
			}

			return FALSE;
		}

		$errors = array();
		// check the files
		$path = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/" . $this->filename;
		$thumb_path = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/" . $this->thumbnail . $this->filename;
		// get paths and remove the thumb
		if (!file_exists($path))
		{
			$errors[] = 'missing_page';
			set_notice('warning', _('Page file not found in:') . ' ' . $this->chapter->comic->name . ' > ' . $this->chapter->title());
			log_message('debug', 'check_page: page not found in ' . $path);
		}

		if (!file_exists($thumb_path))
		{
			$errors[] = 'missing_thumbnail';
			set_notice('warning', _('Thumbnail file not found in:') . ' ' . $this->chapter->comic->name . ' > ' . $this->chapter->title());
			log_message('error', 'check_page: there\'s a missing thumbnail in ' . $thumb_path);
		}

		if ($repair)
		{
			if (in_array('missing_page', $errors) && in_array('missing_thumbnail', $errors))
			{
				// no better suggestion than removing
				$this->remove_page_db();
				return TRUE;
			}

			if (in_array('missing_thumbnail', $errors))
			{
				// just rebuild the thumbnail
				$this->rebuild_thumbnail();
				return TRUE;
			}

			if (in_array('missing_page', $errors))
			{
				// remove the thumbnail and the entry
				unlink($thumb_path);
				$this->remove_page_db();
				return TRUE;
			}
		}

		return $errors;
	}


	public function rebuild_thumbnail()
	{
		// Let's make sure the chapter and comic is set
		$this->get_chapter();

		$path = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/" . $this->filename;
		// get paths and remove the thumb
		if (!file_exists($path))
		{
			set_notice('warning', _('Page not found while creating thumbnail:') . ' ' . $this->chapter->comic->name . ' > ' . $this->chapter->title());
			log_message('error', 'rebuild_thumbnail: there\'s a missing image in ' . $path);
			// don't stop the process
			return TRUE;
		}

		$thumb_path = "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/" . $this->thumbnail . $this->filename;
		if (file_exists($thumb_path))
		{
			if (!unlink($thumb_path))
			{
				set_notice('error', _('Failed to remove the thumbnail while rebuilding it. Please, check file permissions.'));
				log_message('error', 'rebuild_thumbnail: failed to remove thumbnail while rebuilding');
				return FALSE;
			}
		}

		// Prepare the image library to create the thumbnail
		$CI = & get_instance();
		$CI->load->library('image_lib');
		$img_config['image_library'] = (find_imagick()) ? 'ImageMagick' : 'GD2'; // Use GD2 as fallback
		$img_config['library_path'] = (find_imagick()) ? (get_setting('fs_serv_imagick_path') ? get_setting('fs_serv_imagick_path') : '/usr/bin') : ''; // If GD2, use none
		$img_config['source_image'] = $path;
		$img_config["new_image"] = $thumb_path;
		$img_config['width'] = 250;
		$img_config['height'] = 250;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['master_dim'] = 'auto';
		$CI->image_lib->initialize($img_config);

		// Resize to create the thumbnail
		if (!$CI->image_lib->resize())
		{
			set_notice('error', _('Failed to recreate the thumbnail of the page.'));
			log_message('error', 'rebuild_thumbnail: failed to recreate thumbnail');
			return FALSE;
		}

		// update the kind of compression used and thumbnail filesize
		$this->thumbsize = filesize($thumb_path);
		$this->description = (find_imagick()) ? 'im' : '';
		if (!$this->save())
		{
			set_notice('error', _('Failed to save the image compression method in the database.'));
			log_message('error', 'rebuild_thumbnail: failed to save the image compression method');
			return FALSE;
		}

		// Clear the image library for who knows who else calls it
		$CI->image_lib->clear();

		// Good
		return TRUE;
	}


	/**
	 * With a heavy pixel-per-pixel, it checks if the image is black and white.
	 * For better performance, it uses the thumbnail to check on less pixels.
	 * 
	 * @author woxxy, and a loop taken from a site...
	 * @return string "bw" if black and white, "rgb" if colors, false on failure 
	 */
	public function is_bw()
	{
		// Make sure the chapter and comic are selected
		$this->get_chapter();

		// Get the thumbnail
		$rel = 'content/comics/' . $this->chapter->comic->directory() . '/' . $this->chapter->directory() . '/' . $this->thumbnail . $this->filename;

		// We need to know the image type to spawn the right imagecreate function
		switch ($this->mime)
		{
			case "image/jpeg":
				$im = imagecreatefromjpeg($rel); //jpeg file
				break;
			case "image/gif":
				$im = imagecreatefromgif($rel); //gif file
				break;
			case "image/png":
				$im = imagecreatefrompng($rel); //png file
				break;
			default:
				log_message('error', 'page.php/is_bw(): no mime found');
				return false;
		}

		// Get image two sizes
		$imgw = imagesx($im);
		$imgh = imagesy($im);

		$r = array();
		$g = array();
		$b = array();

		$c = 0;

		for ($i = 0; $i < $imgw; $i++)
		{
			for ($j = 0; $j < $imgh; $j++)
			{

				// get the rgb value for current pixel
				$rgb = ImageColorAt($im, $i, $j);

				// extract each value for r, g, b
				$r[$i][$j] = ($rgb >> 16) & 0xFF;
				$g[$i][$j] = ($rgb >> 8) & 0xFF;
				$b[$i][$j] = $rgb & 0xFF;

				// count gray pixels (r=g=b)
				if ($r[$i][$j] == $g[$i][$j] && $r[$i][$j] == $b[$i][$j])
				{
					$c++;
				}
			}
		}

		// If every pixel is black and white, return bw
		if ($c == ($imgw * $imgh))
		{
			return "bw";
		}
		else
		{
			// The image is in colors
			return "rgb";
		}
	}


	/**
	 * Creates the href to the image. Set 
	 * 
	 * @author woxxy
	 * @param boolean|$thumbnail if TRUE function returns thumbnail
	 * @return string href to the image
	 */
	public function page_url($thumbnail = FALSE)
	{
		// Make sure we loaded chapter and comic
		$this->get_chapter();
		return balance_url() . "content/comics/" . $this->chapter->comic->directory() . "/" . $this->chapter->directory() . "/" . ($thumbnail ? $this->thumbnail : "") . $this->filename;
	}


}

/* End of file page.php */
/* Location: ./application/models/page.php */
