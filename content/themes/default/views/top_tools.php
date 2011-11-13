<?php
if (!isset($page))
	$page = 1;

echo form_open($this->fu_board . '/search');
echo '<div class="input-prepend">';
echo '<span class="add-on" rel="popover-right" data-original-title="How to search" data-content="' . htmlentities('Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing that word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt>') . '">?</span>';
echo form_input(array(
	'name' => 'search',
	'id' => 'search'
));
echo form_submit(array(
	'value' => 'Search',
	'class' => 'btn notice',
	'style' => 'border-radius:0; -webkit-border-radius:0; -moz-border-radius:0'
));

echo form_submit(array(
	'value' => 'Advanced',
	'class' => 'btn notice',
	'style' => 'margin-left:-6px'
));
echo '</div>';
echo form_close();
?>

<?php
echo form_open($this->fu_board . '/post');
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
	'onClick' => 'getPost(this.form); return false;'
));
echo '</div>';
echo form_close();
?>

<?php

if ($this->input->cookie('ghost_mode') != 'true')
{
	$toggle_mode = 'ghost';
	echo form_open($this->fu_board . '/page');
	echo '<div class="input-prepend">';
	echo '<span class="add-on">Page #</span>';
	echo form_input(array(
		'name' => 'page',
		'id' => 'page',
		'class' => 'mini',
		'value' => $page
	));
	echo form_submit(array(
		'value' => 'Go',
		'class' => 'btn notice',
		'onClick' => 'getPage(this.form); return false;'
	));
	echo '</div>';
	echo form_close();
}
else
{
	$toggle_mode = 'page';
	echo form_open($this->fu_board . '/ghost');
	echo '<div class="input-prepend">';
	echo '<span class="add-on">? Page #</span>';
	echo form_input(array(
		'name' => 'page_view',
		'id' => 'page_view',
		'class' => 'mini',
		'value' => $page
	));
	echo form_submit(array(
		'value' => 'Go',
		'class' => 'btn notice',
		'onClick' => 'getPage(this.form); return false;'
	));
	echo '</div>';
	echo form_close();
}
?>

<a href="<?php echo site_url($this->fu_board . '/' . $toggle_mode . '/' . $page) ?>">
	<button class="btn<?php echo ($this->input->cookie('ghost_mode') == 'true') ? ' active' : ''; ?>">Ghost Mode</button>
</a>
<div class="clearfix"></div>