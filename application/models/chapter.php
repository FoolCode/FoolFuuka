<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Chapter extends DataMapper
{

	var $has_one = array('comic', 'team', 'joint');
	var $has_many = array('page');
	var $validation = array(
		'comic_id' => array(
			'rules' => array('is_int', 'required', 'max_length' => 256),
			'label' => 'Comic ID',
			'type' => 'hidden'
		),
		'name' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Name',
			'type' => 'input',
		),
		'team_id' => array(
			'rules' => array('is_int', 'max_length' => 256),
			'label' => 'Team ID'
		),
		'joint_id' => array(
			'rules' => array('is_int', 'max_length' => 256),
			'label' => 'Joint ID'
		),
		'stub' => array(
			'rules' => array('stub', 'required', 'max_length' => 256),
			'label' => 'Stub'
		),
		'volume' => array(
			'rules' => array('is_int'),
			'label' => 'Volume number',
			'type' => 'input'
		),
		'chapter' => array(
			'rules' => array('is_int', 'required'),
			'label' => 'Chapter number',
			'type' => 'input',
			'placeholder' => 'required'
		),
		'subchapter' => array(
			'rules' => array('is_int'),
			'label' => 'Subchapter number',
			'type' => 'input'
		),
		'language' => array(
			'rules' => array('required'),
			'label' => 'Language',
			'type' => 'language'
		),
		'uniqid' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Uniqid'
		),
		'hidden' => array(
			'rules' => array('is_int'),
			'label' => 'Hidden',
			'type' => 'checkbox'
		),
		'description' => array(
			'rules' => array(),
			'label' => 'Description'
		),
		'thumbnail' => array(
			'rules' => array('max_length' => 512),
			'label' => 'Thumbnail'
		),
		'pagesnum' => array(
			'rules' => array(),
			'label' => 'Number of pages'
		),
		'size' => array(
			'rules' => array(),
			'label' => 'Total size of pages',
		),
		'dirsize' => array(
			'rules' => array(),
			'label' => 'Directory size'
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
		)
	);

	function __construct($id = NULL)
	{
		// Set the translations
		$this->help_lang();

		parent::__construct(NULL);

		// We've overwrote some functions, and we need to use the get() from THIS model
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
		$this->validation['name']['help'] = _('Insert the title of the chapter, if available.');
		$this->validation['chapter']['label'] = _('Chapter Number');
		$this->validation['chapter']['help'] = _('Insert the chapter number.');
		$this->validation['subchapter']['label'] = _('Subchapter Number');
		$this->validation['subchapter']['help'] = _('Insert a subchapter number to identify extra chapters. Zero for main chapter. Start counting subchapters from 1, not from 5.');
		$this->validation['volume']['label'] = _('Volume');
		$this->validation['volume']['help'] = _('Insert the volume number.');
		$this->validation['language']['label'] = _('Language');
		$this->validation['language']['help'] = _('Select the language of the chapter.');
		$this->validation['description']['label'] = _('Description');
		$this->validation['description']['help'] = _('Insert a description.');
		$this->validation['hidden']['label'] = _('Visibility');
		$this->validation['hidden']['help'] = _('Hide the chapter from public view.');
		$this->validation['hidden']['text'] = _('Hidden');
	}


	/**
	 * This function can determine if it's a team member accessing to protected
	 * chapter functions.
	 *
	 * @author	Woxxy
	 * @return	DataMapper Returns true if the chapter is of the team of the user;
	 */
	public function is_team()
	{
		if (!$this->teams)
		{
			$team = new Team();
			$this->teams = $team->get_teams($this->team_id, $this->joint_id);
		}
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

		$result = parent::get($limit, $offset);

		foreach ($this->all as $key => $item)
		{
			if (!$item->get_comic())
			{
				unset($this->all[$key]);
			}
		}

		return $result;
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
			$item->teams = $teams->get_teams($item->team_id, $item->joint_id);
		}

		return $result;
	}


	/**
	 * Sets the $this->comic variable if it hasn't been done before
	 *
	 * @author	Woxxy
	 * @return	boolean True on success, false on failure.
	 */
	public function get_comic()
	{
		if (isset($this->comic))
			return TRUE;
		$this->comic = new Comic($this->comic_id);

		if ($this->comic->result_count() == 0)
		{
			unset($this->comic);
			return FALSE;
		}

		if (isset($this->all))
			foreach ($this->all as $key => $item)
			{
				if (!isset($item->comic))
				{
					$item->comic = new Comic($item->comic_id);
					if ($item->comic->result_count() != 1)
					{
						unset($this->all[$key]->comic);
						return FALSE;
					}
				}
			}

		// All good, return true.
		return TRUE;
	}


	/**
	 * Sets the $this->teams variable if it hasn't been done before
	 *
	 * @author	Woxxy
	 * @return	boolean True on success, false on failure.
	 */
	public function get_teams()
	{
		if (isset($this->teams))
			return true;
		$teams = new Team();
		$this->teams = $teams->get_teams($this->team_id, $this->joint_id);
		foreach ($this->all as $item)
		{
			if (isset($item->teams))
				continue;
			$teams = new Team();
			$item->teams = $teams->get_teams($item->team_id, $item->joint_id);
		}

		// All good, return true.
		return true;
	}


	/**
	 * Function to create a new entry for a chapter from scratch. It creates
	 * both a directory and a database entry, and removes them if something
	 * goes wrong.
	 *
	 * @author	Woxxy
	 * @param	array $data with the minimal values, or the function will return
	 * 			false and do nothing.
	 * @return	boolean true on success, false on failure.
	 */
	public function add($data)
	{
		// Let's make so subchapters aren't empty, so it's at least 0 for all
		// the addition of the chapter.
		if (!isset($data["subchapter"]) || !is_natural($data["subchapter"]))
			$data["subchapter"] = 0;

		// Create a stub that is humanly readable, for the worst cases.
		$this->to_stub = $data['chapter'] . "_" . $data['subchapter'] . "_" . $data['name'];

		// uniqid prevents us from having sad moments due to identical directory names
		$this->uniqid = uniqid();

		// Stub function converts the $this->to_stub if available, and processes it.
		$this->stub = $this->stub();

		// Check if comic_id is set and confirm there's a corresponding serie
		// If not, make an error message and stop adding the chapter
		$comic = new Comic($data['comic_id']);
		if ($comic->result_count() == 0)
		{
			set_notice('error', _('The series you were adding the chapter to doesn\'t exist.'));
			log_message('error', 'add: comic_id does not exist in comic database');
			return false;
		}

		// The series exists? Awesome, set it as soon as possible.
		$this->comic_id = $data['comic_id'];

		// Create the directory. The GUI error messages are inside the function.
		if (!$this->add_chapter_dir())
		{
			log_message('error', 'add: failed creating dir');
			return false;
		}

		// Hoping we got enough $data, let's throw it to the database function.
		// In case it fails, it will remove the directory.
		if (!$this->update_chapter_db($data))
		{
			$this->remove_chapter_dir();
			log_message('error', 'add: failed adding to database');
			return false;
		}

		// Oh, since we already have the series, let's put it into its variable.
		// This is very comfy for redirection!
		$this->comic = $comic;

		// All good? Return true!
		return true;
	}


	/**
	 * Removes chapter from database, all its pages, and its directory.
	 * There's no going back from this!
	 *
	 * @author	Woxxy
	 * @return	object the comic the chapter derives from.
	 */
	public function remove()
	{
		// Get series and check if existant. We don't want to have empty stub on this!
		$comic = new Comic($this->comic_id);
		if ($this->result_count() == 0)
		{
			set_notice('error', _('You\'re trying to delete something that doesn\'t even have a related series\'?'));
			log_message('error', 'remove_chapter: failed to find requested id');
			return false;
		}

		// Remove all the chapter files. GUI errors inside the function.
		if (!$this->remove_chapter_dir())
		{
			log_message('error', 'remove_chapter: failed to delete dir');
			return false;
		}

		// Remove the chapter from DB, and all its pages too.
		if (!$this->remove_chapter_db())
		{
			log_message('error', 'remove_chapter: failed to delete database entry');
			return false;
		}

		// Return the $comic for redirects.
		return $comic;
	}


	/**
	 * Handles both creating of new chapters in the database and editing old ones.
	 * It determines if it should update or not by checking if $this->id has
	 * been set. It can get the values from both the $data array and direct 
	 * variable assignation. Be aware that array > variables. The latter ones
	 * will be overwritten. Particularly, the variables that the user isn't
	 * allowed to set personally are unset and reset with the automated values.
	 * It's quite safe to throw stuff at it.
	 *
	 * @author	Woxxy
	 * @param	array $data contains the minimal data
	 * @return	object the series the chapter derives from.
	 */
	public function update_chapter_db($data = array())
	{
		// Check if we're updating or creating a new chapter by looking at $data["id"].
		// False is returned if the chapter ID was not found.
		if (isset($data["id"]) && $data['id'] != "")
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', _('The chapter you tried to edit doesn\'t exist.'));
				log_message('error', 'update_chapter_db: failed to find requested id');
				return false;
			}
			// Save the stub in case it gets changed (different chapter number/name etc.)
			// Stub is always automatized.
			$old_stub = $this->stub;
		}
		else
		{ // if we're here, it means that we're creating a new chapter
			// Set the creator name if it's a new chapter.	
			if (!isset($this->comic_id))
			{
				set_notice('error', 'You didn\'t select a series to refer to.');
				log_message('error', 'update_chapter_db: comic_id was not set');
				return false;
			}

			// Check that the related series is defined, and exists.
			$comic = new Comic($this->comic_id);
			if ($comic->result_count() == 0)
			{
				set_notice('error', _('The series you were referring to doesn\'t exist.'));
				log_message('error', 'update_chapter_db: comic_id does not exist in comic database');
				return false;
			}

			// Set the creator. This happens only on new chapter creation.
			$this->creator = $this->logged_id();
		}

		// Always set the editor
		$this->editor = $this->logged_id();

		// Unset the sensible variables. 
		// Not even admins should touch these, for database stability.
		unset($data["creator"]);
		unset($data["editor"]);
		unset($data["uniqid"]);
		unset($data["stub"]);
		unset($data["team_id"]);
		unset($data["joint_id"]);

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

		// This is necessary to make the checkbox work.
		if (!isset($data['hidden']) || $data['hidden'] != 1)
			$this->hidden = 0;

		// Prepare a new stub.
		$this->stub = $this->chapter . '_' . $this->subchapter . '_' . $this->name;
		// stub() is also able to restub the $this->stub. Already stubbed values won't change.
		$this->stub = $this->stub();

		// If the new stub is different from the old one (if the chapter was 
		// already existing), rename the folder.
		if (isset($old_stub) && $old_stub != $this->stub)
		{
			$this->get_comic();
			$dir_old = "content/comics/" . $this->comic->directory() . "/" . $old_stub . "_" . $this->uniqid;
			$dir_new = "content/comics/" . $this->comic->directory() . "/" . $this->stub . "_" . $this->uniqid;
			rename($dir_old, $dir_new);
		}


		// $data['team'] must be an array of team NAMES
		if (isset($data['team']))
		{
			// Remove the empty values in the array of team names.
			// It happens that the POST contains extra empty values.
			if (is_array($data['team']))
			{
				foreach ($data['team'] as $key => $value)
				{
					if ($value == "")
					{
						unset($data['team'][$key]);
					}
				}
				sort($data["team"]);
			}

			// In case there's more than a team name in array, get the joint_id.
			// The joint model is able to create new joints on the fly, do not worry.
			// Worry rather that the team names must exist.
			if (count($data['team']) > 1)
			{
				// Set team_id to 0 since it's a joint.
				$this->team_id = 0;
				$joint = new Joint();
				// If the search returns false, something went wrong.
				// GUI errors are inside the function.
				if (!$this->joint_id = $joint->add_joint_via_name($data['team']))
				{
					log_message('error', 'update_chapter_db: error with joint_id');
					return false;
				}
			}
			// In case there's only one team in the array, find the team.
			// return false in case one of the names doesn't exist.
			else if (count($data['team']) == 1)
			{
				// Set joint_id to 0 since it's a single team
				$this->joint_id = 0;
				$team = new Team();
				$team->where("name", $data['team'][0])->get();
				if ($team->result_count() == 0)
				{
					set_notice('error', _('The team you were referring this chapter to doesn\'t exist.'));
					log_message('error', 'update_chapter_db: team_id does not exist in team database');
					return false;
				}
				$this->team_id = $team->id;
			}
			else
			{
				set_notice('error', _('You must select at least one team for this chapter'));
				log_message('error', 'update_chapter_db: team_id not defined');
				return false;
			}
		}
		else if (!isset($this->team))
		{ // If we're here it means that this is a new chapter with no teams assigned.
			// The system doesn't allow chapters without related teams. It must be at
			// least "anonymous" or a default anonymous team.
			set_notice('error', _('You haven\'t selected any team in relation to this chapter.'));
			log_message('error', 'update_chapter_db: team_id does not defined');
			return false;
		}


		// Save with validation. Push false if fail, true if good.
		$success = $this->save();
		if (!$success)
		{
			if (!$this->valid)
			{
				log_message('error', $this->error->string);
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'update_chapter_db: failed validation');
			}
			else
			{
				set_notice('error', _('Failed to save to database for unknown reasons.'));
				log_message('error', 'update_chapter_db: failed to save');
			}
			return false;
		}
		else
		{
			// Here we go!
			return true;
		}
	}


	/**
	 * Removes the chapter from the database, but before it removes all the 
	 * related pages from the database (not the files).
	 *
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function remove_chapter_db()
	{
		// get all the pages of this chapter. Use iterated because they could be many.
		$pages = new Page();
		$pages->where('chapter_id', $this->id)->get_iterated();

		// remove them with the page model function
		foreach ($pages as $page)
		{
			$page->remove_page_db();
		}

		// And now, remove the chapter itself. There should be little chance for this to fail.
		$success = $this->delete();
		if (!$success)
		{
			set_notice('error', _('Failed to remove the chapter from the database for unknown reasons.'));
			log_message('error', 'remove_chapter_db: id found but entry not removed');
			return false;
		}

		// It's gone.
		return true;
	}


	/**
	 * Creates the necessary empty folder for a chapter
	 * 
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function add_chapter_dir()
	{
		// Get the series if we didn't yet.
		if (!$this->get_comic())
		{
			set_notice('error', _('No series related to this chapter.'));
			log_message('error', 'add_chapter_dir: comic did not exist');
			return false;
		}

		// Create the directory and return false on failure. It's most likely file permissions anyway.
		$dir = "content/comics/" . $this->comic->directory() . "/" . $this->directory();
		if (!mkdir($dir))
		{
			set_notice('error', _('Failed to create the chapter directory. Please, check file permissions.'));
			log_message('error', 'add_chapter_dir: folder could not be created');
			return false;
		}

		return true;
	}


	/**
	 * Removes the chapter folder with all the data that was inside of it.
	 * This means pages and props too.
	 *
	 * @author	Woxxy
	 * @return	boolean true if success, false if failure.
	 */
	public function remove_chapter_dir()
	{
		// Get the series if we didn't yet.
		if (!$this->get_comic())
		{
			set_notice('error', _('No series related to this chapter.'));
			log_message('error', 'remove_chapter_dir: comic did not exist');
			return false;
		}

		// Create the direcotry name
		$dir = "content/comics/" . $this->comic->directory() . "/" . $this->directory() . "/";

		// Delete all files inside of it
		if (!delete_files($dir, TRUE))
		{
			set_notice('error', _('Failed to remove the files inside the chapter directory. Please, check file permissions.'));
			log_message('error', 'remove_chapter_dir: files inside folder could not be removed');
			return false;
		}
		else
		{
			// On success of emptying, remove the chapter directory itself.
			if (!rmdir($dir))
			{
				set_notice('error', _('Failed to remove the chapter directory. Please, check file permissions.'));
				log_message('error', 'remove_chapter_dir: folder could not be removed');
				return false;
			}
		}

		return true;
	}


	/**
	 * Comfy function to just empty the chapter off pages.
	 *
	 * @author	Woxxy
	 * @return	boolean true, doesn't have error check.
	 */
	public function remove_all_pages()
	{
		$page = new Page();

		// Lets get the pages in iterated because there could be many
		$page->where('chapter_id', $this->id)->get_iterated();

		// Loop and remove each. The page model will take care of database and directories.
		$return = true;
		foreach ($page as $key => $item)
		{
			if (!$item->remove_page())
			{
				// Set false to say there was a failure, but don't stop removal.
				$return = false;
				log_message('error', 'remove_all_pages: page could not be removed');
			}
		}
		// Even if false is returned all removable pages will be removed.
		return $return;
	}


	function check($repair = FALSE)
	{
		// make sure we got the comic
		if ($this->get_comic() === FALSE)
		{
			$errors[] = 'chapter_comic_entry_not_found';
			set_notice('warning', _('Found a chapter entry without a comic entry, Chapter ID: ' . $this->id));
			log_message('debug', 'check: chapter entry without comic entry');

			if ($repair)
			{
				$this->remove_chapter_db();
			}

			return FALSE;
		}

		$errors = array();

		// check if the directory exists at all
		$path = 'content/comics/' . $this->comic->directory() . '/' . $this->directory() . '/';
		if (!is_dir($path))
		{
			$errors[] = 'chapter_directory_not_found';
			set_notice('warning', _('No directory found for:') . ' ' . $this->comic->name . ' > ' . $this->title());
			log_message('debug', 'check: chapter directory missing at ' . $path);

			// the folder doesn't exist, so get rid of the entry from database
			if ($repair)
			{
				$this->remove_chapter_db();
			}

			// there's no recovery from this, return the error codes
			return $errors;
		}

		// check if there are extraneous files in the folder
		$files = get_dir_file_info($path);
		foreach ($files as $key => $file)
		{
			// check that the file is writable
			if (!is_writable($file['relative_path']))
			{
				// non writable files are horrendous, send a notice and stop the machines
				$errors[] = 'chapter_non_writable_file';
				set_notice('warning', _('Found non writable files in the comics folder. Check your files permissions.'));
				log_message('debug', 'check: non writable file: ' . $file['relative_path']);
				return $errors;
			}

			// get the extension
			$ext = strtolower(substr($file['name'], -4));

			if (in_array($ext, array('.zip')))
			{
				// maybe it's just the zip created by the archive system
				$archive = new Archive();
				$archive->where('chapter_id', $this->id)->get();
				if ($archive->result_count() == 1)
				{
					// we actually have an archive, but is it the same file?
					if ($file['name'] == $archive->filename)
					{
						// same file, unset to confirm
						unset($files[$key]);
						continue;
					}
				}
			}

			if (in_array($ext, array('.png', '.jpg', 'jpeg', '.gif')))
			{
				$page = new Page();
				$page->where('chapter_id', $this->id)->where('filename', $file['name'])->get();
				if ($page->result_count() == 1)
				{
					// it's a simple page, unset to confirm
					unset($files[$key]);
					continue;
				}

				// probably it's just a thumbnail
				$thumbnail = preg_replace('/^thumb_/', '', $file['name'], 1);

				// check if it's actually different
				if ($thumbnail != $file['name'])
				{
					$page = new Page();
					$page->where('chapter_id', $this->id)->where('filename', $thumbnail)->get();

					// if it's 1, it's a thumbnail, so let's unset it
					if ($page->result_count() == 1)
					{
						// unset to confirm existence
						unset($files[$key]);
						continue;
					}
				}
			}
		}

		// now we have an array with files that don't belong here
		foreach ($files as $file)
		{
			$errors[] = 'chapter_unidentified_file';
			set_notice('warning', _('Unidentified file found in:') . ' ' . $this->comic->name . ' > ' . $this->title() . ': ' . $file['name']);
			log_message('debug', 'check: unidentified file ' . $file['relative_path'] . $file['name']);

			// repairing this means getting rid of extraneous files
			if ($repair)
			{
				// it's possible the file is not removeable
				if (is_writable($file['relative_path'] . $file['name']))
				{
					// the files SHOULD be writable, we checked it earlier
					if (is_dir($file['relative_path'] . $file['name']))
					{
						delete_files($file['relative_path'] . $file['name']);
						rmdir($file['relative_path'] . $file['name']);
					}
					else
					{
						unlink($file['relative_path'] . $file['name']);
					}
				}
			}
		}

		// everything's been checked. The errors are in the set_notice system
		return $errors;
	}


	function get_dirsize()
	{
		$this->get_comic();
		$filearray = get_dir_file_info("content/comics/" . $this->comic->directory() . "/" . $this->directory() . "/", FALSE);
		$size = 0;
		foreach ($filearray as $file)
		{
			$size += $file["size"];
		}
		return $size;
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
	 * Returns all the pages in a complete array, useful for displaying or sending
	 * to a json function.
	 *
	 * @author	Woxxy
	 * @return	array all pages with their data
	 */
	public function get_pages()
	{
		// if we already used the function, no need to recalc it
		if (isset($this->pages))
			return $this->pages;

		// Check that the comic is loaded, else load it.
		$this->get_comic();

		// Get the pages in filename order. Without order_by it would get them by ID which doesn't really work nicely.
		$pages = new Page();
		$pages->where('chapter_id', $this->id)->order_by('filename')->get();

		// Create the array with all page details for simple return.
		$return = array();
		foreach ($pages->all as $key => $item)
		{
			$return[$key] = $item->to_array();
			// Let's add to it the object itelf? Uncomment next line to do so.
			// $return[$key]['object'] = $item;
			// The URLs need to be completed. This function will also trigger the load balancing if enabled.
			$return[$key]['url'] = balance_url() . "content/comics/" . $this->comic->directory() . "/" . $this->directory() . "/" . $item->filename;
			$return[$key]['thumb_url'] = balance_url() . "content/comics/" . $this->comic->directory() . "/" . $this->directory() . "/" . $item->thumbnail . $item->filename;
		}

		// Put the pages in a comfy variable.
		$this->pages = $return;
		return $return;
	}


	/**
	 * Returns the date of release of the chapter WITHOUT hours, minutes and seconds
	 * 
	 * @author Woxxy
	 * @return string date d/m/y
	 */
	public function date()
	{
		return relative_date(strtotime($this->created));
	}


	/**
	 * Returns a ready to use html <a> link that points to the reader
	 * 
	 * @param $text string
	 * @author	Woxxy
	 * @return	string <a> to reader
	 */
	public function url($text = NULL)
	{
		return '<a href="' . $this->href() . '" title="' . $this->title() . '">' . ((is_null($text)) ? $this->title() : $text) . '</a>';
	}


	/**
	 * Returns a ready to use html <a> link that points to the download
	 *
	 * @param $text string
	 * @author	Woxxy
	 * @return	string <a> to reader
	 */
	public function download_url($text = NULL, $class = "")
	{
		if (get_setting('fs_dl_enabled'))
			return '<div class="icon_wrapper ' . $class . '"><a href="' . $this->download_href() . '"><img class="icon off" src="' . glyphish(67) . '" /><img class="icon on" src="' . glyphish(67, TRUE) . '" /></a></div>';
	}


	/**
	 * Returns a nicely built title for a chapter
	 *
	 * @author	Woxxy
	 * @return	string the formatted title for the chapter, with chapter and subchapter
	 */
	public function title()
	{
		$echo = "";
		if ($this->volume > 0)
			$echo .= _('Vol.') . $this->volume . ' ';
		if ($this->chapter > 0) // if chapter == 0 it means this is a one-shot
		{
			if ($this->customchaptertitle()) // support for custom chapter titles
			{
				$echo .= $this->customchaptertitle();
			}
			else
			{
				$echo .= _('Chapter') . ' ' . $this->chapter;
			}
		}
		if ($this->subchapter && $this->chapter > 0) // if it's a one-shot, we still use subchapter for sorting, but don't display it
			$echo .= '.' . $this->subchapter;
		if ($this->name != "")
		{
			// if it's a one-shot with title, hide the : because there's no numbering
			if ($this->chapter > 0)
				$echo .= ': ';
			$echo .= $this->name;
		}
		else
		{
			if ($this->chapter == 0)
			{
				// one-shots without a title gets the comic title
				$this->get_comic(); // play safe and check if we have it
				$echo .= $this->comic->name;
			}
		}

		return $echo;
	}


	public function customchaptertitle()
	{
		$this->get_comic();
		$echo = "";

		// Generate Ordinal Numbers Suffix (English)
		$ordinal = 'th';
		if (!in_array(($this->chapter % 100), array(11, 12, 13)))
		{
			switch ($this->chapter % 10)
			{
				case 1:
					$ordinal = 'st';
					break;
				case 2:
					$ordinal = 'nd';
					break;
				case 3:
					$ordinal = 'rd';
					break;
			}
		}

		$echo = str_replace(
				array('{num}', '{ord}'), array($this->chapter, $ordinal), $this->comic->customchapter
		);

		return $echo;
	}


	/**
	 * Returns a string with the teams that worked on this chapter and the relative URLs
	 *
	 * @author	Woxxy
	 * @return	string <a> to teams
	 */
	public function team_url()
	{
		$this->get_teams();
		$echo = "";
		foreach ($this->teams as $key => $team)
		{
			if ($key > 0)
				$echo .= " | ";
			$echo .= '<a href="' . site_url('/reader/team/' . $team->stub) . '" title="' . $team->name . '" >' . $team->name . '</a>';
		}
		return $echo;
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
		if (!$CI->tank_auth->is_team_leader_array($this->teams))
			return "";
		$this->get_comic();
		return site_url('/admin/series/series/' . $this->comic->stub . '/' . $this->id);
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
		if (!$CI->tank_auth->is_team_leader_array($this->teams))
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
		return site_url('/reader/read/' . $this->unique_href());
	}


	/**
	 * Returns the download href. This will create the shortest possible URL.
	 *
	 * @author	Woxxy
	 * @returns string href to reader.
	 */
	public function download_href()
	{
		return site_url('/reader/download/' . $this->unique_href());
	}


	public function unique_href()
	{
		// If we already used this function, no need to recalc it.
		if (isset($this->unique_href))
			return $this->unique_href;

		// We need the comic
		$this->get_comic();

		// Identify the chapter through data, not ID. This allows us to find out if there are multiple similar chapters.
		$chapter = new Chapter();
		$chapter->where('comic_id', $this->comic->id)->where('volume', $this->volume)->where('chapter', $this->chapter)->where('language', $this->language)->where('subchapter', $this->subchapter)->get();

		// This part of the URL won't change for sure.
		$url = $this->comic->stub . '/' . $this->language . '/' . $this->volume . '/' . $this->chapter . '/';

		// Find out if there are multiple versions of the chapter, it means there are multiple groups with the same chapter.
		// Let's set the whole URL with subchapters, teams and maybe joint too in it.
		if ($chapter->result_count() > 1)
		{

			$url .= $this->subchapter . '/';

			if ($this->team_id != 0)
			{
				$team = new Team($this->team_id);
				$url .= $team->stub . '/';
			}
			else if ($this->joint_id != 0)
				$url .= '0/' . $this->joint_id . '/';

			// save the value in a variable for reuse.
			$this->unique_href = $url;
		}
		else
		{ // There's only one chapter like this.
			// If possiblee can make it even shorter without subchapter!
			if ($this->subchapter != 0)
			{
				$url .= $this->subchapter . '/';
			}
			// Save the value in a variable for reuse.
			$this->unique_href = $url;
		}

		return $this->unique_href;
	}


	/**
	 * Returns the URL of the reader for the next chapter
	 *
	 * @author	Woxxy
	 * @return	string the href to the next chapter
	 */
	public function next()
	{
		// If we've already used this function, it's ready for use, no need to calc it again
		if (isset($this->next))
			return $this->next;

		// Needs the comic
		$this->get_comic();
		$chapter = new Chapter();

		// Check if there are subchapters for this chapter.
		$chapter->where('comic_id', $this->comic->id)->where('volume', $this->volume)->where('chapter', $this->chapter)->where('language', $this->language)->having('subchapter >', $this->subchapter)->order_by('subchapter', 'asc')->limit(1)->get();
		if ($chapter->result_count() == 0)
		{
			// There aren't subchapters for this chapter. Then let's look for the next chapter
			$chapter = new Chapter();
			$chapter->where('comic_id', $this->comic->id)->where('volume', $this->volume)->having('chapter > ', $this->chapter)->where('language', $this->language)->order_by('chapter', 'asc')->order_by('subchapter', 'asc')->limit(1)->get();
			if ($chapter->result_count() == 0)
			{
				// Check if there's a chapter in the next volume.
				// This works even if chapter goes vol2 33 -> vol3 34 or vol2 33 -> vol3 1
				$chapter = new Chapter();
				$chapter->where('comic_id', $this->comic->id)->having('volume > ', $this->volume)->where('language', $this->language)->order_by('chapter', 'asc')->order_by('subchapter', 'asc')->limit(1)->get();
				if ($chapter->result_count() == 0)
				{
					// There's no next chapter. Redirect to the comic page.
					return site_url('/reader/read/' . $this->comic->stub);
				}
			}
		}

		// We do have a chapter or more. Get them.
		$chaptere = new Chapter();
		$chaptere->where('comic_id', $this->comic->id)->where('volume', $chapter->volume)->where('chapter', $chapter->chapter)->where('language', $this->language)->where('subchapter', $chapter->subchapter)->get();

		$done = false;
		// Do we have more than a next chapter? Make so it has the same teams on it.
		if ($chaptere->result_count() > 1)
		{
			foreach ($chaptere->all as $chap)
			{
				if ($chap->team_id == $this->team_id && $chap->joint_id == $this->joint_id)
				{
					$chapter = $chap;
					$done = true;
					break;
				}
			}
			// What if the teams changed, and the old teams stopped working on it? Get a different team.
			// There must be multiple teams on the next chapter for this to happen. Rare but happens.
			if (!$done)
			{
				/**
				 * @todo This is a pretty random way to select the next chapter version, needs refinement.
				 */
				$chapter = $chaptere->all['0'];
			}
		}
		// There's only one chapter, simply use it.
		else
		{
			$chapter = $chaptere;
		}

		// This is a heavy function. Let's play it smart and cache the value.
		// Send to the href function that returns a nice URL.
		$this->next = $chapter->href();

		// finally, return the URL.
		return $this->next;
	}


	/**
	 * Returns the URL for the next page in the same chapter. It's used for
	 * page-change in systems that don't support JavaScript.
	 *
	 * @author	Woxxy
	 * @todo	this function has a quite rough method to work, though it saves 
	 * 			lots of calc power. Maybe it can be written more elegantly?
	 * @return	string with href to next page
	 */
	public function next_page($page, $max = 0)
	{
		if ($max != 0 && $max > $page)
			return $this->next();

		$url = current_url();
		// If the page hasn't been set yet, just add to the URL.
		if (!$post = strpos($url, '/page'))
		{
			return current_url() . 'page/' . ($page + 1);
		}
		// Just remove everything after the page segment and readd it with proper number.
		return substr(current_url(), 0, $post) . '/page/' . ($page + 1);
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
		$result["href"] = $this->href();
		$result["title"] = $this->title();
		$result["download_href"] = $this->download_href();
		return $result;
	}


}

/* End of file chapter.php */
/* Location: ./application/models/chapter.php */
