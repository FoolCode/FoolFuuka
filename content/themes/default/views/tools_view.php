<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($board) && get_setting('fs_sphinx_global'))
{
	// searh can work also without a board selected
	$board->shortname = '';
}

if(isset($board)) :
?>

<ul class="nav pull-right">
	<li class="search-dropdown">
		<?php
		echo form_open(
			site_url(((!$board->shortname)?:'@radix/') . $board->shortname . '/search'),
			array(
				'class' => 'navbar-search pull-right',
				'method' => 'GET'
			)
		);
		echo form_input(array(
			'name' => 'text',
			'data-function' => 'searchShow',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
			'class' => 'span4 search-query',
			'placeholder' => ($board->shortname)?_('Search or insert post no. or thread URL'):_('Global Search')
		));
		?>
		<div class="search-dropdown-menu">
			<ul>
				<li>
					<?php
					echo form_submit(array(
						'class' => 'btn btn-success btn-mini',
						'value' => _('Undefined'),
						'name' => 'submit_undefined',
						'style' => 'display:none;'
					))
					?>

					<?php
					echo form_submit(array(
						'class' => 'btn btn-success btn-mini',
						'value' => _('Search'),
						'name' => 'submit_search'
					))
					?>

					<?php
					if($board->shortname) :
						echo form_submit(array(
							'class' => 'btn btn-success btn-mini',
							'value' => _('Global Search'),
							'name' => 'submit_search_global'
						));
					?>

					<?php
						echo form_submit(array(
							'class' => 'btn btn-success btn-mini',
							'value' => _('Go to post'),
							'name' => 'submit_post'
						));
					endif;
					?>

				</li>

				<li class="divider"></li>

			</ul>
			
			<ul class="pull-right">
				<li><?php echo _('Filters:') ?></li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"]))
									? TRUE : FALSE));
						?>
						All
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted')
									? TRUE : FALSE));
						?>
						Only Deleted
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted')
									? TRUE : FALSE));
						?>
						Only Non-Deleted
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"]))
									? TRUE : FALSE));
						?>
						<span>All</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only')
									? TRUE : FALSE));
						?>
						<span>Only Ghost</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none')
									? TRUE : FALSE));
						?>
						<span>Only Original</span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?php echo form_radio(array('name' => 'filter', 'value' => '', 'checked' => (empty($search["filter"]))
									? TRUE : FALSE));
						?>
						<span>All</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text')
									? TRUE : FALSE));
						?>
						<span>Only with image</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image')
									? TRUE : FALSE));
						?>
						<span>Only without image</span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?php echo form_radio(array('name' => 'type', 'value' => '', 'checked' => (empty($search["type"]))
									? TRUE : FALSE));
						?>
						<span>All</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op')
									? TRUE : FALSE));
						?>
						<span>Thread OPs Only</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'type', 'value' => 'posts', 'checked' => (!empty($search["type"]) && $search["type"] == 'posts')
									? TRUE : FALSE));
						?>
						<span>Replies Only</span>
					</label>
				</li>


				<li class="divider"></li>

				<li>
					<label>
						<?php echo form_radio(array('name' => 'capcode', 'value' => '', 'checked' => (empty($search["capcode"]))
									? TRUE : FALSE));
						?>
						<span>All</span>
					</label>
				</li>

				<li>
					<label>
						<?php echo form_radio(array('name' => 'capcode', 'value' => 'user', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'user')
									? TRUE : FALSE));
						?>
						<span>Only by Users</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'capcode', 'value' => 'mod', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'mod')
									? TRUE : FALSE));
						?>
						<span>Only by Mods</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'capcode', 'value' => 'admin', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'admin')
									? TRUE : FALSE));
						?>
						<span>Only by Admins</span>
					</label>
				</li>



				<li class="divider"></li>


				<li>
					<label>
						<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc'))
									? TRUE : FALSE));
						?>
						<span>New First</span>
					</label>
				</li>
				<li>
					<label>
						<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc')
									? TRUE : FALSE));
						?>
						<span>Old First</span>
					</label>
				</li>
			</ul>
			
			<ul class="pull-left">

				<li class="input-prepend"><label for="subject" class="add-on">Subject</label><?php echo form_input(array('name' => 'subject', 'id' => 'subject', 'value' => (isset($search["subject"]))
								? rawurldecode($search["subject"]) : ''))
					?></li>
				<li class="input-prepend"><label for="username" class="add-on">Username</label><?php echo form_input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"]))
								? rawurldecode($search["username"]) : ''))
					?></li>
				<li class="input-prepend"><label for="tripcode" class="add-on">Tripcode</label><?php echo form_input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"]))
								? rawurldecode($search["tripcode"]) : ''))
					?></li>
				<li class="input-prepend"><label for="email" class="add-on">E-mail</label><?php echo form_input(array('name' => 'email', 'id' => 'email', 'value' => (isset($search["email"]))
					? rawurldecode($search["email"]) : ''))
					?></li>
				<li class="input-prepend"><label for="filename" class="add-on">Filename</label><?php echo form_input(array('name' => 'filename', 'id' => 'filename', 'value' => (isset($search["filename"]))
					? rawurldecode($search["filename"]) : ''))
					?></li>
				<li class="input-prepend"><label for="date_start" class="add-on">Date start</label><?php
					$date_array = array(
							'placeholder' => 'yyyy-mm-dd',
							'type' => 'date',
							'name' => 'start',
							'id' => 'date_start'
						);
					if(isset($search["date_start"]))
					{
						$date_array['value'] = rawurldecode($search["date_start"]);
					}

					echo form_input($date_array);
					?></li>

				<li class="input-prepend"><label for="date_end" class="add-on">Date end</label><?php
					$date_array = array(
							'placeholder' => 'yyyy-mm-dd',
							'type' => 'date',
							'name' => 'end',
							'id' => 'date_end',
						);

					if(isset($search["date_end"]))
					{
						$date_array['value'] = rawurldecode($search["date_end"]);
					}
					echo form_input($date_array);

					?></li>
				
				<?php echo form_close(); ?>
				<?php if($board->shortname) :
					echo form_open_multipart(
						site_url('@radix/' . $board->shortname . '/search'),
						array('style' => 'margin-bottom:8px')
					);
				?>
				
				<li class="divider" style="margin-bottom:8px"></li>
				<li class="input-prepend"><label for="file_search" class="add-on">Image</label><input id="file_search" type="file" name="image" />
					</li><li><?php
					echo form_submit(array(
						'class' => 'btn btn-success btn-mini',
						'value' => _('Search image'),
						'name' => 'submit_image',
						'title' => _('On most browsers you can also drop the file on the search bar.'),
						'style' => 'margin-top:0;'
					))
					?></li>
				<?php 
					echo form_close();
					endif; 
				?>
					
				<li class="divider"></li>
				<li style="margin-top: 5px;"><?php echo _('Your latest searches:') ?>
					<div class="pull-right"><a href="#" data-function="clearLatestSearches" class="btn btn-warning btn-mini" style="margin:0; padding: 1px 3px; line-height:normal;color:#FFF; position:relative; top:-1px;"><?php echo _('Clear') ?></a></div>
				</li>
				<?php 
					if(isset($latest_searches) || $latest_searches = @json_decode($this->input->cookie('foolfuuka_search_latest_5'), TRUE))
					{
						// sanitization
						foreach($latest_searches as $item)
						{
							// all subitems must be array, all must have 'board'
							if(!is_array($item) || !isset($item['board']))
							{
								$latest_searches = array();
								break;
							}
						}

						foreach($latest_searches as $latest_search)
						{
							$uri = ($latest_search['board'] === FALSE ? '' : $latest_search['board'] . '/') . '/search/';
							$text = !$latest_search['board'] === FALSE ? '<strong>global:</strong> ' : '/<strong>' . $latest_search['board'] . '</strong>/: ';
							unset($latest_search['board']);
							if(isset($latest_search['text']))
							{
								$uri .= 'text/' . $latest_search['text'] . '/';
								$text .= urldecode($latest_search['text']) . ' ';
								unset($latest_search['text']);
							}
							if(isset($latest_search['order']) && $latest_search['order'] == 'desc')
							{
								unset($latest_search['order']);
							}
							
							$extra_text = '';
							$extra_text_br = '';
							foreach($latest_search as $k => $i)
							{
								$uri .= $k.'/'.$i.'/';
								$extra_text .= '<span class="options">[' . $k . '] ' . urldecode($i) . ' </span>';
								$extra_text_br .= '<br/><span class="options">[' . $k . '] ' . urldecode($i) . ' </span>';
							}
							
							echo '<li title="' . form_prep($text . $extra_text_br) . '" class="latest_search"><a href="' . site_url($uri) . '">' . $text . ' ' . $extra_text . '</a></li>';
						}
					}
					
				?>
			</ul>
		</div>
	</li>
</ul>

<?php endif; ?>
