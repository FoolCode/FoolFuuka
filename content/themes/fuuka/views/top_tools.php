<?php
if (!isset($page))
	$page = 1;
?>
<div style="overflow:hidden;">
	<!--- Search Input -->
	<?php echo form_open($this->fu_board . '/search'); ?>
	<div id="simple-search" class="postspan" style="float:left">
		<?php
		echo 'Text Search [<a class="tooltip" href="#">?<span>Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing the word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt></span></a>]';
		echo form_input(array(
			'name' => 'text',
			'id' => 'text',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
		));
		echo form_submit(array(
			'value' => 'Search'
		));
		echo form_submit(array(
			'value' => 'Advanced',
			'onclick' => 'javascript:toggle(\'advanced-search\');toggle(\'simple-search\');return false;'
		));
		?>
	</div>
	<?php echo form_close(); ?>

	<!--- Advanced Search Input -->
	<?php echo form_open($this->fu_board . '/search'); ?>
	<div id="advanced-search" class="postspan" style="float:left;display:none">
		<table style="float:left">
			<tbody>
				<tr>
					<td colspan="2" class="theader">Advanced Search</td>
				</tr>
				<tr>
					<td class="postblock">Text to find</td>
					<td>
						<?php echo form_input(array('name' => 'text', 'id' => 'text2', 'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock">Username <a class="tooltip" href="#">[?]<span>Search for <b>exact</b> username. Leave empty for any username.</span></a></td>
					<td>
						<?php echo form_input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"])) ? rawurldecode($search["username"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock">Tripcode <a class="tooltip" href="#">[?]<span>Search for <b>exact</b> tripcode. Leave empty for any tripcode.</span></a></td>
					<td>
						<?php echo form_input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"])) ? rawurldecode($search["tripcode"]) : '')); ?>
					</td>
				</tr>
				<tr>
					<td class="postblock">Deleted Posts</td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"])) ? TRUE : FALSE)); ?>
							<span>Show All Posts</span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted') ? TRUE : FALSE)); ?>
							<span>Show Only Deleted Posts</span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted') ? TRUE : FALSE)); ?>
							<span>Only Show Non-Deleted Posts</span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock">Internal Posts</td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"])) ? TRUE : FALSE)); ?>
							<span>Show All Posts</span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only') ? TRUE : FALSE)); ?>
							<span>Show Only Internal Posts</span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none') ? TRUE : FALSE)); ?>
							<span>Show Old Archived Posts</span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock">Order</td>
					<td>
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc')) ? TRUE : FALSE)); ?>
							<span>New Posts First</span>
						</label><br />
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc') ? TRUE : FALSE)); ?>
							<span>Old Posts First</span>
						</label>
					</td>
				</tr>
				<tr>
					<td class="postblock">Action</td>
					<td>
						<?php
						echo form_submit(array(
							'value' => 'Search',
							'onclick' => 'getSearch(\'advanced\', this.form); return false;'
						));
						echo form_submit(array(
							'value' => 'Simple',
							'onclick' => 'javascript:toggle(\'advanced-search\');toggle(\'simple-search\');return false;'
						));
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php echo form_close(); ?>

	<!--- Post Input -->
	<?php echo form_open($this->fu_board . '/post'); ?>
	<div class="postspan" style="float:left">
		<?php
		echo 'Post No.';
		echo form_input(array(
			'name' => 'post',
			'id' => 'post',
			'class' => 'mini'
		));
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'location.href=\'' . site_url($this->fu_board . '/post/') . '\' + this.form.post.value + \'/\'; return false;'
		));
		?>
	</div>
	<?php echo form_close(); ?>

	<!--- Page Input -->
	<?php echo form_open($this->fu_board . '/page'); ?>
	<div class="postspan" style="float:left">
		<?php
		echo 'Page #';
		echo form_input(array(
			'name' => 'page',
			'id' => 'page',
			'value' => $page
		));
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'location.href=\'' . site_url($this->fu_board . '/page/') . '\' + this.form.page.value + \'/\'; return false;'
		));
		?>

		<input type="button" class="btn notice" style="margin-left:-5px;" value="Ghost Mode" onclick="location.href='<?php echo site_url($this->fu_board . '/ghost') ?>' + this.form.page.value + '/'; return false;" />
		<a class="tooltip" href="#">[?] <span>In ghost mode, only threads with non-archived posts will be shown.</span></a>
	</div>
	<?php echo form_close(); ?>
</div>