<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<h3><?php echo _('Editing board:') . ' ' .fuuka_htmlescape($board->name); ?></h3>

<form class="well">
<?php echo form_open() ?>
  <label><?php echo _('Name') ?></label>
  <?php echo form_input(array(
	  'name' => 'board-name',
	  'class' => 'span3',
	  'placeholder' => _('Required'),
	  'value' => $board->name
  )) ?>
  <span class="help-inline">
	  <?php echo _('Insert the name of the board normally shown as title.') ?></span>
  
  <label><?php echo _('Shortname') ?></label>
  <?php echo form_input(array(
	  'name' => 'board-shortname',
	  'class' => 'span1',
	  'placeholder' => _('Required'),
	  'value' => $board->shortname
  )) ?>
  <span class="help-inline">
	  <?php echo _('Insert the shorter name of the board. Reserved: "api", "cli", "admin".') ?></span>
  
  <label class="checkbox">
    <?php echo form_checkbox('board-archive', 'board-archive', $board->archive) ?>
		<?php echo _('Is this a 4chan archiving board?') ?>
  </label>
  
  <label class="checkbox">
    <?php echo form_checkbox('board-archive', 'board-archive', $board->thumbnails) ?>
		<?php echo _('Display the thumbnails?') ?>
  </label>
  
  <label class="checkbox">
    <?php echo form_checkbox('board-delay_thumbnails', 'board-delay_thumbnails', $board->delay_thumbnails) ?>
		<?php echo _('Hide the thumbnails for 24 hours? (for moderation purposes)') ?>
  </label>
  
  <label class="checkbox">
    <?php echo form_checkbox('board-sphinx', 'board-sphinx', $board->sphinx) ?>
		<?php echo _('Use SphinxSearch as search engine?') ?>
  </label>
  
  <label class="checkbox">
    <?php echo form_checkbox('board-hidden', 'board-hidden', $board->hidden) ?>
		<?php echo _('Hide the board from public access? (only admins and mods will be able to browse it)') ?>
  </label>
  
  <button type="submit" class="btn"><?php echo _('Submit') ?></button>
  
<?php echo form_close() ?>
</form>
