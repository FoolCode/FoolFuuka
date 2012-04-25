<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FS_Articles extends Plugins
{
	/*
	 * This is a plugin that is in actual production, but that is also
	 * good for use as tutorial. It contains all the base functions that
	 * you will have to edit to match
	 */


	function __construct()
	{
		// KEEP THIS EMPTY, use the initialize_plugin method instead

		parent::__construct();
	}
	
	function structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$CI = & get_instance();
					$query = $CI->db->where('id', $input['id'])->get('plugin_fs-articles');
					if ($query->num_rows() != 1)
					{
						return array(
							'error_code' => 'ID_NOT_FOUND',
							'error' => _('Couldn\'t find the article with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			),
			'title' => array(
				'type' => 'input',
				'database' => TRUE,
				'label' => 'Title',
				'help' => _('The title of your article'),
				'class' => 'span4',
				'placeholder' => _('Required'),
				'validation' => 'trim|required'
			),
			'slug' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => _('Slug'),
				'help' => _('Insert the short name of the article to use in the url. Only alphanumeric and dashes.'),
				'placeholder' => _('Required'),
				'class' => 'span4',
				'validation' => 'required|alpha_dash',
				'validation_func' => function($input, $form_internal)
				{
					// as of PHP 5.3 we can't yet use $this in Closures, we could in 5.4
					$CI = & get_instance();
					
					// if we're working on the same object
					if (isset($input['id']))
					{
						// existence ensured by CRITICAL in the ID check
						$query = $CI->db->where('id', $input['id'])->get('plugin_fs-articles')->row();

						// no change?
						if ($input['slug'] == $query->slug)
						{
							// no change
							return array('success' => TRUE);
						}
					}

					// check that there isn't already an article with that name
					$query = $CI->db->where('slug', $input['slug'])->get('plugin_fs-articles');
					if ($query->num_rows() > 0)
					{
						return array(
							'error_code' => 'ALREADY_EXISTS',
							'error' => _('The slug is already used for another board.')
						);
					}
				}
			),
			'url' => array(
				'type' => 'input',
				'database' => TRUE,
				'class' => 'span4',
				'label' => 'URL',
				'help' => _('If you set this, the article link will actually be an outlink.'),
				'validation' => 'trim'
			),
			'article' => array(
				'type' => 'textarea',
				'database' => TRUE,
				'style' => 'height:350px; width: 90%',
				'label' => _('Article'),
				'help' => _('The content of your article, in MarkDown')
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'top' => array(
				'type' => 'checkbox',
				'database' => TRUE,
				'label' => _('Display the article link on the top of the page'),
				'help' => _('Display the article link on the top of the page')
			),
			'bottom' => array(
				'type' => 'checkbox',
				'database' => TRUE,
				'label' => _('Display the article link on the bottom of the page'),
				'help' => _('Display the article link on the bottom of the page')
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => _('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
	}

	/*
	 * We leave the install, update, remove, enable, disable functions on 
	 * bottom of this file
	 */


	function initialize_plugin()
	{
		$this->plugins->register_controller_function($this,
			array('admin', 'articles'), 'manage');
		$this->plugins->register_controller_function($this,
			array('admin', 'articles', 'manage'), 'manage');
		$this->plugins->register_controller_function($this,
			array('admin', 'articles', 'edit'), 'edit');
		$this->plugins->register_controller_function($this,
			array('admin', 'articles', 'edit', '(:any)'), 'edit');
		$this->plugins->register_controller_function($this,
			array('admin', 'articles', 'remove', '(:any)'), 'remove');

		$this->plugins->register_admin_sidebar_element('articles',
			array(
				"name" => _("Articles"),
				"default" => "manage",
				"position" => array(
					"beforeafter" => "after",
					"element" => "posts"
				),
				"level" => "admin",
				"content" => array(
					"manage" => array("level" => "admin", "name" => _("Articles"), "icon" => 'icon-font'),
				)
			)
		);

		$this->plugins->register_controller_function($this,
			array('chan', 'articles'), 'article');
		$this->plugins->register_controller_function($this,
			array('chan', 'articles', '(:any)'), 'article');
	}


	function manage()
	{
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/articles/manage") . '">' . _("Articles") . '</a>';
		$this->viewdata['function_title'] = _('Manage');

		$articles = $this->get_all();
		
		ob_start();
		?>

			<a href="<?php echo site_url('admin/articles/edit') ?>" class="btn" style="float:right; margin:5px"><?php echo _('New article') ?></a>

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Title</th>
						<th>Slug</th>
						<th>Edit</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($articles as $article) : ?>
					<tr>
						<td>
							<?php echo htmlentities($article->title) ?>
						</td>
						<td>
							<a href="<?php echo site_url('@board/articles/' . $article->slug) ?>" target="_blank"><?php echo $article->slug ?></a>
						</td>
						<td>
							<a href="<?php echo site_url('admin/articles/edit/'.$article->slug) ?>" class="btn btn-mini btn-primary"><?php echo _('Edit') ?></a>
						</td>
						<td>
							<a href="<?php echo site_url('admin/articles/remove/'.$article->id) ?>" class="btn btn-mini btn-danger"><?php echo _('Remove') ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php
		$data['content'] = ob_get_clean();
		$this->viewdata["main_content_view"] = $this->load->view("admin/plugin.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function edit($slug = null)
	{
		$data['form'] = $this->structure();
		
		if($this->input->post())
		{
			$this->load->library('form_validation');
			$result = $this->form_validation->form_validate($data['form']);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				// it's actually fully checked, we just have to throw it in DB
				$this->save($result['success']);
				if (is_null($slug))
				{
					flash_notice('success', _('New article created!'));
					redirect('admin/articles/edit/' . $result['success']['slug']);
				}
				else if ($slug != $result['success']['slug'])
				{
					// case in which letter was changed
					flash_notice('success', _('Article information updated.'));
					redirect('admin/article/edit/' . $result['success']['slug']);
				}
				else
				{
					set_notice('success', _('Article information updated.'));
				}
			}
		}
		
		if(!is_null($slug))
		{
			$data['object'] = $this->get_by_slug($slug);
			if($data['object'] == FALSE)
			{
				show_404();
			}	
			
			$this->viewdata["function_title"] = _('Article') . ': ' . $data['object']->slug;
		}
		else 
		{
			$this->viewdata["function_title"] = _('New article') ;
		}
		
		$this->viewdata["controller_title"] = '<a href="' . site_url('admin/articles') . '">' . _('Articles') . '</a>';
		
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function article($slug = null)
	{
		if(is_null($slug))
		{
			show_404();
		}
		
		$article = $this->get_by_slug($slug);

		if(!is_object($article))
		{
			show_404();
		}
		
		if($article->url)
			redirect($article->url);
		
		$this->template->title(fuuka_htmlescape($article->title) . ' « ' . get_setting('fs_gen_site_title'));
		$this->template->set('section_title', $article->title);
		$this->load->library('Markdown_Parser');

		// unless you're making a huge view you can live with output buffers
		ob_start();
		?>
			<style type="text/css">
				.markdown { 
					margin:30px auto; 
					max-width:900px; 
					background: #FFF; 
					color: #444;
					padding: 10px 40px; 
					border: 2px solid #6A836F;
					font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
					font-size:14px;
				}
				.markdown h1 { margin: 18px 0 15px; padding: 10px 0; border-bottom: 2px solid #888; }
				.markdown h2 { margin: 18px 0 8px; padding: 3px; border-bottom: 1px solid #AAA; }
				.markdown h3 { margin: 12px 0; }
				.markdown h4 { margin: 8px 0; }
				.markdown p { margin-bottom: 12px; font-size:1.1em; line-height:150%; }
				.markdown li { margin-bottom: 6px; line-height:150%; }
				.markdown code { font-weight: normal; color: #555; }
				.markdown pre { font-weight: normal; color: #555; max-width: 100%; word-wrap: break-word; }
				.markdown pre code { font-weight: normal; color: #555; width: 100%; }
			</style>
				
			<div class="markdown">
				<?php echo Markdown($article->article); ?>
			</div	>
		<?php
		$this->template->set('content', ob_get_clean());

		$this->template->build('plugin');
	}
	
	function remove($id)
	{
		if(!$article = $this->get_by_id($id))
		{
			show_404();
		}
		
		if($this->input->post())
		{
			$this->db->where('id', $id)->delete('plugin_fs-articles');
			flash_notice('success', _('The article was removed'));
			redirect('admin/articles/manage');
		}
		
		$this->viewdata["controller_title"] = '<a href="' . site_url('admin/articles') . '">' . _('Articles') . '</a>';
		$this->viewdata["function_title"] = _('Removing article:') . ' ' . $article->title;
		$data['alert_level'] = 'warning';
		$data['message'] = _('Do you really want to remove the article?');

		$this->viewdata["main_content_view"] = $this->load->view('admin/confirm', $data, TRUE);
		$this->load->view('admin/default', $this->viewdata);
		
	}


	/**
	 * Grab the whole table of articles 
	 */
	function get_all()
	{
		$query = $this->db->query('
			SELECT *
			FROM `' . $this->db->dbprefix('plugin_fs-articles') . '`
			' . (($this->tank_auth->is_allowed())?'':'WHERE top = 1 OR bottom = 1 ') . '
		');

		if($query->num_rows() == 0)
			return array();
		
		return $query->result();
	}


	function get_by_slug($slug)
	{
		$query = $this->db->query('
			SELECT *
			FROM `' . $this->db->dbprefix('plugin_fs-articles') . '`
			WHERE slug = ? ' . (($this->tank_auth->is_allowed())?'':'AND (top = 1 OR bottom = 1) ') . '
		',
			array($slug));
		
		if($query->num_rows() == 0)
			return array();

		return $query->row();
	}


	function get_by_id($id)
	{
		$query = $this->db->query('
			SELECT *
			FROM `' . $this->db->dbprefix('plugin_fs-articles') . '`
			WHERE id = ? ' . (($this->tank_auth->is_allowed())?'':'AND (top = 1 OR bottom = 1) ') . '
		',
			array($id));
		
		if($query->num_rows() == 0)
			return array();

		return $query->row();
	}
	
	
	function get_top()
	{
		$query = $this->db->query('
			SELECT *
			FROM `' . $this->db->dbprefix('plugin_fs-articles') . '`
			WHERE top = 1
		');
		
		if($query->num_rows() == 0)
			return array();

		return $query->result();
	}
	
	
	function get_bottom()
	{
		$query = $this->db->query('
			SELECT *
			FROM `' . $this->db->dbprefix('plugin_fs-articles') . '`
			WHERE bottom = 1
		');
		
		if($query->num_rows() == 0)
			return array();

		return $query->result();
	}


	function save($data)
	{
		if (isset($data['id']))
		{
			$this->db->where('id', $data['id'])->update('plugin_fs-articles', $data);
		}
		else
		{
			$this->db->insert('plugin_fs-articles', $data);
		}
	}
	
	
	/**
	 * Using the install function creates folders and database entries for 
	 * the plugin to function. 
	 */
	function plugin_install()
	{
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('plugin_fs-articles') . "` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`slug` varchar(128) NOT NULL,
				`title` varchar(256) NOT NULL,
				`url` text,
				`article` text,
				`active` smallint(2),
				`top` smallint(2),
				`bottom` smallint(2),
				`edited` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `edited` (`edited`),
				KEY `slug` (`slug`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	    ");
	}

	/**
	 * If any upgrade is necessary, use this format. Update checks are
	 * performed every time the version of the plugin is changed.
	 */
	/*
	  function upgrade_001()
	  {

	  }
	 */


	/**
	 * Removes everything by the plugin.
	 */
	function plugin_remove()
	{
		$this->db->query('
			DROP TABLE `fu_plugin_fs-articles`
	    ');
	}


	/**
	 * A function triggered when the user enables the plugin.
	 * If not present at all (it mostly shouldn't be necessary) nothing
	 * wrong will happen. 
	 */
	function plugin_enable()
	{
		
	}


	/**
	 * A function triggered when the user disables the plugin.
	 * If not present at all (it mostly shouldn't be necessary) nothing
	 * wrong will happen. 
	 */
	function plugin_disable()
	{
		
	}

}