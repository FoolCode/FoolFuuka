<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Comic extends DataMapper
{

	static $cached = array();
	var $has_one = array();
	var $has_many = array('chapter', 'license');
	var $validation = array(
		'name' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Name',
			'type' => 'input',
			'placeholder' => 'required',
		),
		'stub' => array(
			'rules' => array('required', 'stub', 'unique', 'max_length' => 256),
			'label' => 'Stub'
		),
		'uniqid' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Uniqid'
		),
		'hidden' => array(
			'rules' => array('is_int'),
			'label' => 'Visibility',
			'type' => 'checkbox'
		),
		'description' => array(
			'rules' => array(),
			'label' => 'Description',
			'type' => 'textarea',
		),
		'thumbnail' => array(
			'rules' => array('max_length' => 512),
			'label' => 'Thumbnail',
			'type' => 'upload',
			'display' => 'image',
		),
		'customchapter' => array(
			'rules' => array(),
			'label' => 'Custom chapter',
			'type' => 'input'
		),
		'lastseen' => array(
			'rules' => array(),
			'label' => 'Lastseen'
		),
		'creator' => array(
			'rules' => array(''),
			'label' => 'Creator'
		),
		'editor' => array(
			'rules' => array(''),
			'label' => 'Editor'
		)
	);

	function __construct($id = NULL)
	{
		// Set language
		$this->help_lang();

		if (!is_null($id) && $comic = $this->get_cached($id))
		{
			parent::__construct();
			foreach ($comic->to_array() as $key => $c)
			{
				$this->$key = $c;

				// fill also the all array so result_count() is correctly 1
				$this->all[0]->$key = $c;
			}
			if (isset($comic->licenses))
				$this->licenses = $comic->licenses;
			if (isset($comic->chapters))
				$this->chapters = $comic->chapters;

			return TRUE;
		}

		parent::__construct(NULL);

		// We've overwrote the get() function so we need to look for $id from here
		if (!empty($id) && is_numeric($id))
		{
			$this->where('id', $id)->get();
		}
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	/**
	 * This function sets the translations for the validation values.
	 * 
	 * @author Woxxy
	 * @return void
	 */
	function help_lang()
	{
		$this->validation['name']['label'] = _('Name');
		$this->validation['name']['help'] = _('Insert the title of the series.');
		$this->validation['description']['label'] = _('Description');
		$this->validation['description']['help'] = _('Insert a description.');
		$this->validation['hidden']['label'] = _('Visibility');
		$this->validation['hidden']['help'] = _('Hide the series from public view.');
		$this->validation['hidden']['text'] = _('Hidden');
		$this->validation['thumbnail']['label'] = _('Thumbnail');
		$this->validation['thumbnail']['help'] = _('Upload an image to use as thumbnail.');
		$this->validation['customchapter']['label'] = _('Custom Chapter Title');
		$this->validation['customchapter']['help'] = _('Replace the default chapter title with a custom format. Example: "{num}{ord} Stage" returns "2nd Stage"');
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
		{
			$this->where('hidden', 0);
		}

		$result = parent::get($limit, $offset);

		$this->get_licenses();

		$CI = & get_instance();

		if (!$CI->tank_auth->is_allowed() && !$CI->tank_auth->is_team())
		{
			// Remove from the array the serie licensed in the user's nation
			foreach ($this->all as $key => $item)
			{
				if (in_array($CI->session->userdata('nation'), $this->licenses))
				{
					unset($this->all[$key]);
				}
			}
			if (in_array($CI->session->userdata('nation'), $this->licenses))
			{
				$this->clear();
			}
		}

		// let's put the result in a small cache, since teams are always the same
		foreach ($this->all as $comic)
		{
			// if it's not yet cached, let's cache it
			if (!$this->get_cached($comic->id))
			{
				if (count(self::$cached) > 10)
					array_shift(self::$cached);
				self::$cached[] = $comic->get_clone();
			}
		}

		return $result;
	}


	/**
	 * Returns the series that have been already called before
	 * 
	 * @author Woxxy
	 * @param int $id team_id
	 */
	public function get_cached($id)
	{
		foreach (self::$cached as $cache)
		{
			if ($cache->id == $id)
			{
				return $cache;
			}
		}
		return FALSE;
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
	 * Comodity get() function that fetches extra data for the series selected.
	 * It doesn't get the chapters.
	 * 
	 * CURRENTLY USELESS.
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
			
		}

		return $result;
	}


	/**
	 * Gets the nations where the series is licensed
	 * 
	 * @author	Woxxy
	 * @return	bool true on success
	 */
	public function get_licenses()
	{
		if (isset($this->licenses))
			return true;
		$license = new License();
		$this->licenses = $license->get_by_comic($this->id);
		// Check if the variable is not yet set, in order to save a databse read.
		foreach ($this->all as $item)
		{
			if (isset($item->licenses))
				continue;
			$license = new License();
			$item->licenses = $license->get_by_comic($item->id);
		}

		// All good, return true.
		return true;
	}


	/**
	 * Function to create a new entry for a series from scratch. It creates
	 * both a directory and a database entry, and removes them if something
	 * goes wrong.
	 *
	 * @author	Woxxy
	 * @param	array $data with the minimal values, or the function will return
	 * 			false and do nothing.
	 * @return	Returns true on success, false on failure.
	 */
	public function add($data = array())
	{
		// For the series, the stub is just the name.
		$this->to_stub = $data['name'];
		// Uniqid to prevent directory clash
		$this->uniqid = uniqid();
		// stub() checks for to_stub and makes a stub.
		$this->stub = $this->stub();

		// Check if the series database entry and remove dir in case it's not.
		// GUI errors are inner to the function
		if (!$this->update_comic_db($data))
		{
			log_message('error', 'add_comic: failed writing to database');
			return false;
		}

		// Check if dir is created. GUI errors in inner function.
		if (!$this->add_comic_dir())
		{
			log_message('error', 'add_comic: failed creating dir');
			return false;
		}

		// Good job!
		return true;
	}


	/**
	 * Removes series from database, all its pages, chapters, and its directory.
	 * There's no going back from this!
	 *
	 * @author	Woxxy
	 * @return	boolean true on success, false on failure
	 */
	public function remove()
	{

		// Remove the directory through function
		if (!$this->remove_comic_dir())
		{
			log_message('error', 'remove_comic: failed to delete dir');
			return false;
		}

		// Remove database entry through function
		if (!$this->remove_comic_db())
		{
			log_message('error', 'remove_comic: failed to delete database entry');
			return false;
		}

		return true;
	}


	/**
	 * Handles both creating of new series in the database and editing old ones.
	 * It determines if it should update or not by checking if $this->id has
	 * been set. It can get the values from both the $data array and direct 
	 * variable assignation. Be aware that array > variables. The latter ones
	 * will be overwritten. Particularly, the variables that the user isn't
	 * allowed to set personally are unset and reset with the automated values.
	 * It's quite safe to throw stuff at it.
	 *
	 * @author	Woxxy
	 * @param	array $data contains the minimal data
	 * @return	boolean true on success, false on failure
	 */
	public function update_comic_db($data = array())
	{

		// Check if we're updating or creating a new series by looking at $data["id"].
		// False is returned if the chapter ID was not found.
		if (isset($data["id"]) && $data['id'] != '')
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', _('The series you wanted to edit doesn\'t exist.'));
				log_message('error', 'update_comic_db: failed to find requested id');
				return false;
			}
			// Save the stub in a variable in case it gets changed, so we can change folder name
			$old_stub = $this->stub;
			$old_name = $this->name;
		}
		else
		{
			// let's set the creator name if it's a new entry
			$this->creator = $this->logged_id();
		}

		// always set the editor name
		$this->editor = $this->logged_id();

		// Unset sensible variables
		unset($data["creator"]);
		unset($data["editor"]);
		unset($data["uniqid"]);
		unset($data["stub"]);

		// Allow only admins and mods to arbitrarily change the release date
		$CI = & get_instance();
		if (!$CI->tank_auth->is_allowed())
			unset($data["created"]);
		if (!$CI->tank_auth->is_allowed())
			unset($data["edited"]);

		// Loop over the array and assign values to the variables.
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}

		// Double check that we have all the necessary automated variables
		if (!isset($this->uniqid))
			$this->uniqid = uniqid();
		if (!isset($this->stub))
			$this->stub = $this->stub();

		// Create a new stub if the name has changed
		if (isset($old_name) && isset($old_stub) && ($old_name != $this->name))
		{
			// Prepare a new stub.
			$this->stub = $this->name;
			// stub() is also able to restub the $this->stub. Already stubbed values won't change.
			$this->stub = $this->stub();
		}


		// Make so there's no intersecting stubs, and make a stub with a number in case of duplicates
		// In case this chapter already has a stub and it wasn't changed, don't change it!
		if ((!isset($this->id) || $this->id == '') || (isset($old_stub) && $old_stub != $this->stub))
		{
			$i = 1;
			$found = FALSE;

			$comic = new Comic();
			$comic->where('stub', $this->stub)->get();
			if ($comic->result_count() == 0)
			{
				$found = TRUE;
			}

			while (!$found)
			{
				$i++;
				$pre_stub = $this->stub . '_' . $i;
				$comic = new Comic();
				$comic->where('stub', $pre_stub)->get();
				if ($comic->result_count() == 0)
				{
					$this->stub = $pre_stub;
					$found = TRUE;
				}
			}
		}


		// This is necessary to make the checkbox work.
		/**
		 *  @todo make the checkbox work consistently across the whole framework
		 */
		if (!isset($data['hidden']) || $data['hidden'] != 1)
			$this->hidden = 0;

		// rename the folder if the stub changed
		if (isset($old_stub) && $old_stub != $this->stub && is_dir("content/comics/" . $old_stub . "_" . $this->uniqid))
		{
			$dir_old = "content/comics/" . $old_stub . "_" . $this->uniqid;
			$dir_new = "content/comics/" . $this->stub . "_" . $this->uniqid;
			rename($dir_old, $dir_new);
		}

		// let's save and give some error check. Push false if fail, true if good.
		$success = $this->save();
		if (!$success)
		{
			if (!$this->valid)
			{
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'update_comic_db: failed validation');
			}
			else
			{
				set_notice('error', _('Failed saving the series to database for unknown reasons.'));
				log_message('error', 'update_comic_db: failed to save');
			}
			return false;
		}

		if (!isset($data['licensed']))
		{
			$data['licensed'] = array();
		}

		// update license data
		$license = new License();
		$license->update($this->id, $data['licensed']);
		
		// Good job!
		return true;
	}


	/**
	 * Removes the series from the database, but before it removes all the 
	 * related chapters and their pages from the database (not the files).
	 *
	 * @author	Woxxy
	 * @return	object a copy of the series that has been deleted
	 */
	public function remove_comic_db()
	{
		// Get all its chapters
		$chapters = new Chapter();
		$chapters->where("comic_id", $this->id)->get_iterated();

		// Remove all the chapters from the database. This will also remove all the pages
		foreach ($chapters as $chapter)
		{
			$chapter->remove_chapter_db();
		}

		// We need a clone if we want to keep the variables after deletion
		$temp = $this->get_clone();
		$success = $this->delete();
		if (!$success)
		{
			set_notice('error', _('The series couldn\'t be removed from the database for unknown reasons.'));
			log_message('error', 'remove_comic_db: id found but entry not removed');
			return false;
		}

		// Return the comic clone
		return $temp;
	}


	/**
	 * Creates the necessary empty folder for the comic
	 * 
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function add_comic_dir()
	{
		// Just create the folder
		if (!mkdir("content/comics/" . $this->directory()))
		{
			set_notice('error', _('The directory could not be created. Please, check file permissions.'));
			log_message('error', 'add_comic_dir: folder could not be created');
			return false;
		}
		return true;
	}


	/**
	 * Removes the series directory with all the data that was inside of it.
	 * This means chapters, pages and props too.
	 *
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function remove_comic_dir()
	{
		$dir = "content/comics/" . $this->directory() . "/";

		// Delete all inner files
		if (!delete_files($dir, TRUE))
		{
			set_notice('error', _('The files inside the series directory could not be removed. Please, check the file permissions.'));
			log_message('error', 'remove_comic_dir: files inside folder could not be removed');
			return false;
		}
		else
		{
			// On success delete the directory itself
			if (!rmdir($dir))
			{
				set_notice('error', _('The directory could not be removed. Please, check file permissions.'));
				log_message('error', 'remove_comic_dir: folder could not be removed');
				return false;
			}
		}

		return true;
	}


	public function check($repair = FALSE, $recursive = FALSE)
	{
		$dir = "content/comics/" . $this->directory() . "/";
		$errors = array();
		if (!is_dir($dir))
		{
			$errors[] = 'comic_directory_not_found';
			set_notice('warning', _('No directory found for:') . ' ' . $this->name . ' (' . $this->directory() . ')');
			log_message('debug', 'check: comic directory missing at ' . $dir);

			if ($repair)
			{
				// the best we can do is removing the database entry
				$this->remove_comic_db();
			}
		}
		else
		{
			// check that there are no unidentified files in the comic folder
			$map = directory_map($dir, 1);
			foreach ($map as $key => $item)
			{
				$item_path = $dir . $item;
				if (is_dir($item_path))
				{
					// gotta split the directory to get stub and uniqid
					$item_arr = explode('_', $item);
					$uniqid = end($item_arr);
					$stub = str_replace('_' . $uniqid, '', $item);
					$chapter = new Chapter();
					$chapter->where('stub', $stub)->where('uniqid', $uniqid)->get();
					if ($chapter->result_count() == 0)
					{
						$errors[] = 'comic_unidentified_directory_found';
						set_notice('warning', _('Unidentified directory found at:') . ' ' . $item_path);
						log_message('debug', 'check: unidentified directory found at ' . $item_path);
						if ($repair)
						{
							// you have to remove all the files in the folder first
							delete_files($item_path, TRUE);
							rmdir($item_path);
						}
					}
				}
				else
				{
					if ($item != $this->thumbnail && $item != 'thumb_' . $this->thumbnail)
					{
						// if it's not the thumbnail image, it's an unidentified file
						$errors[] = 'comic_unidentified_file_found';
						set_notice('warning', _('Unidentified file found at:') . ' ' . $item_path);
						log_message('debug', 'check: unidentified file found at ' . $item_path);
						if ($repair)
						{
							unlink($item_path);
						}
					}
				}
			}
		}

		return $errors;
	}


	public function check_external($repair = FALSE, $recursive = FALSE)
	{
		$this->load->helper('directory');

		// check if all that is inside is writeable
		if (!$this->check_writable('content/comics/'))
		{
			return FALSE;
		}

		// check that every folder has a correpsonding comic
		$map = directory_map('content/comics/', 1);
		foreach ($map as $key => $item)
		{
			// gotta split the directory to get stub and uniqid
			$item_arr = explode('_', $item);
			$uniqid = end($item_arr);
			$stub = str_replace('_' . $uniqid, '', $item);
			$comic = new Comic();
			$comic->where('stub', $stub)->where('uniqid', $uniqid)->get();
			if ($comic->result_count() == 0)
			{
				$errors[] = 'comic_entry_not_found';
				set_notice('warning', _('No database entry found for:') . ' ' . $stub);
				log_message('debug', 'check: database entry missing for ' . $stub);
				if ($repair)
				{
					if (is_dir('content/comics/' . $item))
					{
						// you have to remove all the files in the folder first
						delete_files('content/comics/' . $item, TRUE);
						rmdir('content/comics/' . $item);
					}
					else
					{
						unlink('content/comics/' . $item);
					}
				}
			}
		}

		// check the database entries
		$comics = new Comic();
		$comics->get();
		foreach ($comics->all as $key => $comic)
		{
			$comic->check($repair);
		}

		// if recursive, this will go through a through (and long) check of all chapters
		if ($recursive)
		{
			$chapters = new Chapter();
			$chapters->get_iterated();
			foreach ($chapters as $chapter)
			{
				$chapter->check($repair);
			}

			// viceversa, check that all the database entries have a matching file
			$pages = new Page();
			$pages->get_iterated();
			foreach ($pages as $page)
			{
				$page->check($repair);
			}
		}
	}


	private function check_writable($path)
	{
		$map = directory_map($path, 1);
		foreach ($map as $key => $item)
		{
			if (is_dir($path . $item))
			{
				// check if even the dir itself is writable 
				if (!is_writable($path . $item . '/'))
				{
					$errors[] = 'non_writable_directory';
					set_notice('warning', _('Found a non-writable directory.'));
					log_message('debug', 'check: non-writable directory found: ' . $item);
					return FALSE;
				}

				// use the recursive check function
				if (!$this->check_writable($path . $item . '/'))
				{
					return FALSE;
				}
			}
			else
			{
				if (!is_writable($path . $item))
				{
					$errors[] = 'comic_non_writable_file';
					set_notice('warning', _('Found a non-writable file.'));
					log_message('debug', 'check: non-writable file: ' . $item);
					return FALSE;
				}
			}
		}
		return TRUE;
	}


	/**
	 * Creates the thumbnail and saves the original as well
	 *
	 * @author	Woxxy
	 * @param	array|$filedata a standard array coming from CodeIgniter's upload
	 * @return	boolean true on success, false on failure
	 */
	public function add_comic_thumb($filedata)
	{
		// If there's already one, remove it.
		if ($this->thumbnail != "")
			$this->remove_comic_thumb();

		// Get directory variable
		$dir = "content/comics/" . $this->directory() . "/";

		// Copy the full image over
		if (!copy($filedata["server_path"], $dir . $filedata["name"]))
		{
			set_notice('error', _('Failed to create the thumbnail image for the series. Check file permissions.'));
			log_message('error', 'add_comic_thumb: failed to create/copy the image');
			return false;
		}

		// Load the image library
		$CI = & get_instance();
		$CI->load->library('image_lib');

		// Let's setup the thumbnail creation and pass it to the image library
		$image = "thumb_" . $filedata["name"];
		$img_config['image_library'] = 'GD2';
		$img_config['source_image'] = $filedata["server_path"];
		$img_config["new_image"] = $dir . $image;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['width'] = 250;
		$img_config['height'] = 250;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['master_dim'] = 'auto';
		$CI->image_lib->initialize($img_config);

		// Resize! And return false of failure
		if (!$CI->image_lib->resize())
		{
			set_notice('error', _('Failed to create the thumbnail image for the series. Resize function didn\'t work'));
			log_message('error', 'add_comic_thumb: failed to create thumbnail');
			return false;
		}

		// Whatever we might want to do later, we better clear the library now!
		$CI->image_lib->clear();

		// The thumbnail is actually the filename of the original for series thumbnails
		// It's different from page thumbnails - those have "thumb_" in thiserie s variable!
		$this->thumbnail = $filedata["name"];

		// Save hoping we're lucky
		if (!$this->save())
		{
			set_notice('error', _('Failed to save the thumbnail image in the database.'));
			log_message('error', 'add_comic_thumb: failed to add to database');
			return false;
		}

		// Alright!
		return true;
	}


	/**
	 * Removes the thumbnail and its original image both from database and directory.
	 *
	 * @author	Woxxy
	 * @return	string true on success, false on failure.
	 */
	public function remove_comic_thumb()
	{

		// Get directory
		$dir = "content/comics/" . $this->directory() . "/";

		// Remove the full image
		if (!unlink($dir . $this->thumbnail))
		{
			set_notice('error', _('Failed to remove the thumbnail\'s original image. Please, check file permissions.'));
			log_message('error', 'Model: comic_model.php/remove_comic_thumb: failed to delete image');
			return false;
		}

		// Remove the thumbnail
		if (!unlink($dir . "thumb_" . $this->thumbnail))
		{
			set_notice('error', _('Failed to remove the thumbnail image. Please, check file permissions.'));
			log_message('error', 'Model: comic_model.php/remove_comic_thumb: failed to delete thumbnail');
			return false;
		}

		// Set the thumbnail variable to empty and save to database
		$this->thumbnail = "";
		if (!$this->save())
		{
			set_notice('error', _('Failed to remove the thumbnail image from the database.'));
			log_message('error', 'Model: comic_model.php/remove_comic_thumb: failed to remove from database');
			return false;
		}

		// All's good.
		return true;
	}


	/**
	 * Returns href to thumbnail. Uses load-balancer system.
	 *
	 * @author	Woxxy
	 * @param boolean|$full if set to true, the function returns the full image
	 * @return	string href to thumbnail.
	 */
	public function get_thumb($full = FALSE)
	{
		if ($this->thumbnail != "")
			return site_url() . "content/comics/" . $this->stub . "_" . $this->uniqid . "/" . ($full ? "" : "thumb_") . $this->thumbnail;
		return false;
	}


	function update_license($nations)
	{
		$comic_id = $this->id;
		$licenses = new License();
		$licenses->where('comic_id', $comic_id)->get();

		$removeme = array();
		foreach ($licenses->all as $key => $license)
		{
			$removeme[$key] = $license->nation;
		}

		$temp_nations = $nations;
		foreach ($nations as $key => $nation)
		{
			$found = false;
			foreach ($licenses->all as $subkey => $license)
			{
				if ($nation == $license->nation)
				{
					unset($removeme[$subkey]);
					$found = true;
				}
			}
			if (!$found && $nation != "")
			{
				$new_license = new License();
				$new_license->comic_id = $comic_id;
				$new_license->nation = $nation;
				$new_license->save();
			}
		}

		foreach ($removeme as $key => $nation)
		{
			$remove = new License();
			$remove->where('comic_id', $comic_id)->where('nation', $nation)->get()->remove();
		}
	}


	/**
	 * Returns directory name without slashes
	 *
	 * @author	Woxxy
	 * @return	string Directory name.
	 */
	public function directory()
	{
		return $this->stub . '_' . $this->uniqid;
	}


	/**
	 * Returns a ready to use html <a> link that points to the reader
	 *
	 * @author	Woxxy
	 * @return	string <a> to reader
	 */
	public function url()
	{
		return '<a href="' . $this->href() . '" title="' . $this->title() . '">' . $this->title() . '</a>';
	}


	/**
	 * Returns a nicely built title for a chapter
	 *
	 * @author	Woxxy
	 * @return	string the formatted title for the chapter, with chapter and subchapter
	 */
	public function title()
	{
		return $this->name;
	}


	/**
	 * Returns the href to the chapter editing
	 *
	 * @author	Woxxy
	 * @return	string href to chapter editing
	 */
	public function edit_href()
	{
		$CI = & get_instance();
		if (!$CI->tank_auth->is_allowed())
			return "";
		return site_url('/admin/series/series/' . $this->stub);
	}


	/**
	 * Returns the url to the chapter editing
	 *
	 * @author	Woxxy
	 * @return	string <a> to chapter editing
	 */
	public function edit_url()
	{
		$CI = & get_instance();
		if (!$CI->tank_auth->is_allowed())
			return "";
		return '<a href="' . $this->edit_href() . '" title="' . _('Edit') . ' ' . $this->title() . '">' . _('Edit') . '</a>';
	}


	/**
	 * Returns the href to the reader. This will create the shortest possible URL.
	 *
	 * @author	Woxxy
	 * @returns string href to reader.
	 */
	public function href()
	{
		return site_url('/reader/series/' . $this->stub);
	}

	
	/**
	 * Overwrites the original DataMapper to_array() to add some elements
	 * 
	 * @param array $fields
	 * @return array
	 */
	public function to_array($fields = '')
	{
		$result = parent::to_array($fields = '');
		$result["thumb_url"] = $this->get_thumb();
		$result["fullsized_thumb_url"] = $this->get_thumb(true);
		$result["href"] = $this->href();
		return $result;
	}

}

/* End of file comic.php */
/* Location: ./application/models/comic.php */