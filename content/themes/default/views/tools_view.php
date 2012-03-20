<ul class="nav pull-right">
	<li class="search-dropdown">
		<?php
		echo form_open_multipart(
			$board->shortname . '/search',
			array(
			'class' => 'navbar-search pull-right',
			)
		);
		echo form_input(array(
			'name' => 'text',
			'data-function' => 'searchShow',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
			'class' => 'span4 search-query',
			'placeholder' => _('Search or insert post no. or thread URL')
		));
		?>
		<ul class="search-dropdown-menu">
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
				echo form_submit(array(
					'class' => 'btn btn-success btn-mini',
					'value' => _('Go to post'),
					'name' => 'submit_post'
				))
				?>
								
				<?php
				echo form_button(array(
					'data-function' => 'searchHide',
					'class' => 'btn btn-danger btn-mini pull-right',
					'content' => _('Close'),
				))
				?>

			</li>

			<li class="divider"></li>

			<li class="input-prepend"><span class="add-on">Subject</span><?php echo form_input(array('name' => 'subject', 'id' => 'subject', 'value' => (isset($search["subject"]))
							? rawurldecode($search["subject"]) : ''))
				?></li>
			<li class="input-prepend"><span class="add-on">Username</span><?php echo form_input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"]))
							? rawurldecode($search["username"]) : ''))
				?></li>
			<li class="input-prepend"><span class="add-on">Tripcode</span><?php echo form_input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"]))
							? rawurldecode($search["tripcode"]) : ''))
				?></li>
			<li class="input-prepend"><span class="add-on">Date start</span><?php
				echo form_input(
					array('type' => 'date',
						'name' => 'start',
						'placeholder' => 'yyyy-mm-dd',
						'id' => 'date_start',
						'value' => (isset($search["date_start"])) ?
							rawurldecode($search["date_start"]) : ''));
				?></li>

			<li class="input-prepend"><span class="add-on">Date end</span><?php
				echo form_input(
					array(
						'type' => 'date',
						'name' => 'end',
						'id' => 'date_end',
						'placeholder' => 'yyyy-mm-dd',
						'value' => (isset($search["date_start"])) ?
							rawurldecode($search["date_start"]) : ''));
				?></li>
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
<?php echo form_radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image')
			? TRUE : FALSE));
?>
					<span>Only with image</span>
				</label>
			</li>
			<li>
				<label>
					<?php echo form_radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text')
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

			<li class="divider"></li>
			<li class="input-prepend"><span class="add-on">Image</span><?php echo form_upload(array('name' => 'image'))
				?>
				</li><li><?php
				echo form_submit(array(
					'class' => 'btn btn-success btn-mini',
					'value' => _('Search image'),
					'name' => 'submit_image',
					'rel' => 'tooltip_right',
					'title' => _('On most browsers you can also drop the file on the search bar.'),
					'style' => 'margin-top:0;'
				))
				?></li>


		</ul>
<?php echo form_close(); ?>
	</li>
</ul>