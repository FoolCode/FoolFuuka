<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($board))
{
	// searh can work also without a board selected
	$board = new stdClass();
	$board->shortname = '';
}
?>

<div style="overflow:hidden;">
	<!--- Search Input -->
	<?php echo form_open(site_url(((!$board->shortname)?'':'@radix/' . $board->shortname) . '/search')); ?>
	<div id="simple-search" class="postspan" style="float:left">
		<?= __('Text Search') ?>
		[<a class="tooltip" href="#">?<span>Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing the word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt></span></a>]

		<?php
		echo form_input(array(
			'name' => 'text',
			'id' => 'text',
			'size' => '24',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'Go'
		));
		?>
		<a href="<?php echo site_url(((!$board->shortname)?'':'@radix/' . $board->shortname) . '/search') ?>" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ <?= __('Advanced') ?> ]</a>
	</div>
	<?php echo form_close(); ?>

	<!--- Advanced Search Input -->
	<?php echo form_open(site_url(((!$board->shortname)?'':'@radix/' . $board->shortname) . '/search')); ?>
	<div id="advanced-search" class="postspan" style="float:left;display:none">
		<table style="float:left">
			<tbody>
				<tr>
					<td colspan="2" class="theader"><?= __('Advanced Search') ?></td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Text Search') ?></td>
					<td>
						<?php echo form_input(array('name' => 'text', 'size' => '32', 'id' => 'text2', 'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Subject') ?></td>
					<td>
						<?php echo form_input(array('name' => 'subject', 'size' => '32', 'id' => 'subject', 'value' => (isset($search["subject"])) ? rawurldecode($search["subject"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Username') ?> <a class="tooltip" href="#">[?]<span><?= __('Search for an <b>exact</b> username. Leave empty for any username.') ?></span></a></td>
					<td>
						<?php echo form_input(array('name' => 'username', 'size' => '32', 'id' => 'username', 'value' => (isset($search["username"])) ? rawurldecode($search["username"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Tripcode') ?> <a class="tooltip" href="#">[?]<span><?= __('Search for an <b>exact</b> tripcode. Leave empty for any tripcode.') ?></span></a></td>
					<td>
						<?php echo form_input(array('name' => 'tripcode', 'size' => '32', 'id' => 'tripcode', 'value' => (isset($search["tripcode"])) ? rawurldecode($search["tripcode"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('E-mail') ?></td>
					<td>
						<?php echo form_input(array('name' => 'email', 'size' => '32', 'id' => 'email', 'value' => (isset($search["email"])) ? rawurldecode($search["email"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('From Date') ?> <a class="tooltip" href="#">[?]<span><?= __('Enter the starting date for your search.') ?><br/><?= __('Format: YYYY-MM-DD') ?></span></a></td>
					<td>
						<?php
						echo form_input(
							array('type' => 'date',
								'name' => 'start',
								'placeholder' => 'YYYY-MM-DD',
								'id' => 'date_start',
								'value' => (isset($search["date_start"])) ? rawurldecode($search["date_start"]) : ''
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('To Date') ?> <a class="tooltip" href="#">[?]<span><?= __('Enter the ending date for your search.') ?><br/><?= __('Format: YYYY-MM-DD') ?></span></a></td>
					<td>
						<?php
						echo form_input(
							array(
								'type' => 'date',
								'name' => 'end',
								'id' => 'date_end',
								'placeholder' => 'YYYY-MM-DD',
								'value' => (isset($search["date_start"])) ? rawurldecode($search["date_start"]) : ''
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Filename') ?></td>
					<td>
						<?php echo form_input(array('name' => 'filename', 'size' => '32', 'id' => 'filename', 'value' => (isset($search["filename"])) ? rawurldecode($search["filename"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Image Hash') ?></td>
					<td>
						<?php echo form_input(array('name' => 'image', 'size' => '32', 'id' => 'image', 'value' => (isset($search["image"])) ? rawurldecode($search["image"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Deleted Posts') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"])) ? TRUE : FALSE)); ?>
							<span><?= __('Show All Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted') ? TRUE : FALSE)); ?>
							<span><?= __('Only Deleted Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted') ? TRUE : FALSE)); ?>
							<span><?= __('Only Non-Deleted Posts') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Ghost Posts') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"])) ? TRUE : FALSE)); ?>
							<span><?= __('Show All Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only') ? TRUE : FALSE)); ?>
							<span><?= __('Only Ghost Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none') ? TRUE : FALSE)); ?>
							<span><?= __('Only Non-Ghost Posts') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Show Posts') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => '', 'checked' => (empty($search["filter"])) ? TRUE : FALSE)); ?>
							<span><?= __('Show All Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text') ? TRUE : FALSE)); ?>
							<span><?= __('Only Containing Images') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image') ? TRUE : FALSE)); ?>
							<span><?= __('Only Containing Text') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Results') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => '', 'checked' => (empty($search["type"])) ? TRUE : FALSE)); ?>
							<span><?= __('All Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => 'posts', 'checked' => (empty($search["type"]) || (!empty($search["type"]) && $search["type"] == 'posts')) ? TRUE : FALSE)); ?>
							<span><?= __('Only Reply Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op') ? TRUE : FALSE)); ?>
							<span><?= __('Only OP Posts') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Capcode') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => '', 'checked' => (empty($search["capcode"])) ? TRUE : FALSE)); ?>
							<span><?= __('Show All Posts') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => 'user', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'user') ? TRUE : FALSE)); ?>
							<span><?= __('Only by Users') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => 'mod', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'mod') ? TRUE : FALSE)); ?>
							<span><?= __('Only by Mods') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => 'admin', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'admin') ? TRUE : FALSE)); ?>
							<span><?= __('Only by Admins') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Order') ?></td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc')) ? TRUE : FALSE)); ?>
							<span><?= __('New Posts First') ?></span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc') ? TRUE : FALSE)); ?>
							<span><?= __('Old Posts First') ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock"><?= __('Action') ?></td>
					<td>
						<?php
						echo form_submit(array(
							'value' => 'Search',
							'name' => 'submit_search'
						));

						if (get_setting('fs_sphinx_global')) :
							echo form_submit(array(
								'value' => 'Global Search',
								'name' => 'submit_search_global'
							));
						endif;
						?>
						<a href="#" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ <?=__('Simple') ?> ]</a>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php echo form_close(); ?>

<?php if(get_selected_radix()) : ?>
	<!--- Post Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/post'); ?>
	<div class="postspan" style="float:left">
		<?= __('View Post') ?>

		<?php
		echo form_input(array(
			'name' => 'post',
			'id' => 'post',
			'size' => '9'
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'getPost(this.form); return false;'
		));
		?>
	</div>
	<?php echo form_close(); ?>

	<!--- Page Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/page'); ?>
	<div class="postspan" style="float:left">
		<?= __('View Page') ?>

		<?php
		echo form_input(array(
			'name' => 'page',
			'id' => 'page',
			'size' => '6',
			'value' => ((isset($page)) ? $page : 1)
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'location.href=\'' . site_url(get_selected_radix()->shortname . '/page/') . '\' + this.form.page.value + \'/\'; return false;'
		));
		?>

		<a class="tooltip" href="#">[?]<span><?= __('In Ghost Mode, only threads that contain ghost posts will be listed.') ?></span></a>

		<input type="button" value="View in Ghost Mode" onclick="location.href='<?php echo site_url(get_selected_radix()->shortname . '/ghost') ?>' + this.form.page.value + '/'; return false;" />
	</div>
	<?php echo form_close(); ?>
<?php endif; ?>
</div>
