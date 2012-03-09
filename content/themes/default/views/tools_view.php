<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if ($enabled_tools_view) : ?>
<div class="clearfix" style="border-bottom:1px dashed #aaa; margin-bottom: 5px; padding-left:10px;">
	<!--- Post Input -->
	<div>
		<?php
		echo form_open(get_selected_radix()->shortname . '/post');
		echo '<div class="input-prepend">';
		echo '<span class="add-on">Post No.</span>';
		echo form_input(array(
			'name' => 'post',
			'id' => 'post',
			'class' => 'mini'
		));
		echo form_submit(array(
			'value' => 'Go',
			'class' => 'btn notice',
			'style' => 'margin-left: -2px',
			'onClick' => 'getPost(this.form); return false;'
		));
		echo '</div>';
		echo form_close();
		?>
	</div>

	<!--- Page Input -->
	<div>
		<?php
		echo form_open(get_selected_radix()->shortname . '/page');
		echo '<div class="input-prepend">';
		echo '<span class="add-on">Page #</span>';

		echo form_input(array(
			'name' => 'page',
			'id' => 'page',
			'class' => 'mini',
			'value' => (isset($page)) ? $page : 1
		));
		echo form_submit(array(
			'value' => 'Go',
			'class' => 'btn notice',
			'style' => 'margin-left: -2px; border-radius:0; -moz-border-radius:0; -webkit-border-radius:0;',
			'onClick' => 'getPage(this.form); return false;'
		));
		?>

		<input type="button" class="btn notice" style="margin-left:-5px;" value="Ghost Mode" onclick="location.href='<?php echo site_url(get_selected_radix()->shortname . '/ghost') ?>' + this.form.page.value + '/'; return false;" />
		<?php
		echo '</div>';
		echo form_close();
		?>
	</div>
</div>

<!--- Search Input -->
<div id="search_simple">
	<?php
	echo form_open(get_selected_radix()->shortname . '/search');
	echo '<div class="input-prepend">';
	echo '<a class="add-on" data-rel="popover-below" data-placement="below" data-original-title="How to search" data-content="' . htmlentities('Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing that word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt>') . '">?</a>';
	echo form_input(array(
		'name' => 'text',
		'id' => 'text',
		'style' => 'width:239px',
		'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
	));
	echo form_submit(array(
		'value' => 'Search',
		'class' => 'btn notice',
		'style' => 'margin-left: -2px; border-radius:0; -moz-border-radius:0; -webkit-border-radius:0;',
		'onClick' => 'getSearch(\'simple\', this.form); return false;'
	));
	echo form_submit(array(
		'value' => 'Advanced',
		'class' => 'btn notice',
		'style' => 'margin-left: -1px;',
		'onClick' => 'toggleSearch(\'simple\'); toggleSearch(\'advanced\'); return false;'
	));
	echo '</div>';
	echo form_close();
	?>
</div>

<!--- Search Input (Advanced) -->
<div id="search_advanced" style="display: none">
	<?php
	echo form_open(get_selected_radix()->shortname . '/search');
	echo '<div class="input-prepend">';
	echo '<a class="add-on" data-rel="popover-below" data-original-title="How to search" data-content="' . htmlentities('Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing that word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt>') . '">?</a>';
	echo form_input(array(
		'name' => 'text',
		'id' => 'text2',
		'style' => 'width:239px',
		'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
	));
	echo form_submit(array(
		'value' => 'Search',
		'class' => 'btn notice',
		'style' => 'margin-left: -2px; border-radius:0; -moz-border-radius:0; -webkit-border-radius:0;',
		'onClick' => 'getSearch(\'advanced\', this.form); return false;'
	));
	echo form_submit(array(
		'value' => 'Advanced',
		'class' => 'btn notice active',
		'style' => 'margin-left: -1px;',
		'onClick' => 'toggleSearch(\'simple\'); toggleSearch(\'advanced\'); return false;'
	));
	echo '</div>';
	?>
	<br/>
	<div style="max-width: 360px">
		<div class="clearfix">
			<label for="date_start">Starting Date</label>
			<div class="input">
				<?php echo form_input(array('type' => 'date', 'name' => 'start', 'id' => 'date_start', 'value' => (isset($search["date_start"])) ? rawurldecode($search["date_start"]) : '')); ?>
			</div>
		</div>
		<div class="clearfix">
			<label for="date_end">Ending Date</label>
			<div class="input">
				<?php echo form_input(array('type' => 'date', 'name' => 'end', 'id' => 'date_end', 'value' => (isset($search["date_end"])) ? rawurldecode($search["date_end"]) : '')); ?>
			</div>
		</div>
		<div class="clearfix">
			<label for="username">Subject</label>
			<div class="input">
				<?php echo form_input(array('name' => 'subject', 'id' => 'subject', 'value' => (isset($search["subject"])) ? rawurldecode($search["subject"]) : '')); ?>
			</div>
		</div>
		<div class="clearfix">
			<label for="username">Username</label>
			<div class="input">
				<?php echo form_input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"])) ? rawurldecode($search["username"]) : '')); ?>
			</div>
		</div>
		<div class="clearfix">
			<label for="tripcode">Tripcode</label>
			<div class="input">
				<?php echo form_input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"])) ? rawurldecode($search["tripcode"]) : '')); ?>
			</div>
		</div>
		<div class="clearfix">
			<label>Capcode</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => '', 'checked' => (empty($search["capcode"])) ? TRUE : FALSE)); ?>
							<span>Display All Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => 'mod', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'mod') ? TRUE : FALSE)); ?>
							<span>Only Mod Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'capcode', 'value' => 'admin', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'admin') ? TRUE : FALSE)); ?>
							<span>Only Admin Posts</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class="clearfix">
			<label>Deleted Posts</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"])) ? TRUE : FALSE)); ?>
							<span>Display All Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted') ? TRUE : FALSE)); ?>
							<span>Only Deleted Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted') ? TRUE : FALSE)); ?>
							<span>Only Non-Deleted Posts</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class="clearfix">
			<label>Ghost Posts</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"])) ? TRUE : FALSE)); ?>
							<span>Display All Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only') ? TRUE : FALSE)); ?>
							<span>Only Ghost Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none') ? TRUE : FALSE)); ?>
							<span>Old Archived Posts</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class="clearfix">
			<label>Exclude</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => '', 'checked' => (empty($search["filter"])) ? TRUE : FALSE)); ?>
							<span>Display All Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image') ? TRUE : FALSE)); ?>
							<span>All Image Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text') ? TRUE : FALSE)); ?>
							<span>All Text-Only Posts</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class="clearfix">
			<label>Results</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => '', 'checked' => (empty($search["type"])) ? TRUE : FALSE)); ?>
							<span>Display All Posts</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op') ? TRUE : FALSE)); ?>
							<span>Thread OPs Only</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'type', 'value' => 'posts', 'checked' => (!empty($search["type"]) && $search["type"] == 'posts') ? TRUE : FALSE)); ?>
							<span>Posts Only</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class="clearfix">
			<label>Order By</label>
			<div class="input">
				<ul class="inputs-list">
					<li>
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc')) ? TRUE : FALSE)); ?>
							<span>New Posts First</span>
						</label>
					</li>
					<li>
						<label>
							<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc') ? TRUE : FALSE)); ?>
							<span>Old Posts First</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<?php echo form_close(); ?>
</div>
<div class="clearfix"></div>
<?php endif; ?>