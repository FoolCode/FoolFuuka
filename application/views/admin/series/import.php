<div class="table">
	<h3><?php echo _('Import'); ?></h3>
	<span class="clearfix"><?php echo _('Here you can select a directory and import chapters from it. This directory must contain ZIP, RAR or directories with each representing one single chapter. After you select a valid directory, the interface will give you the ability to fine-tune the import of each chapter. Notice that every directory, ZIP or RAR will be scanned for images and these will all be put in the same folder. If there are two files with the same name in the same ZIP, RAR or directory, the second found will overwrite the first.'); 
?></span>
	<br/><br/>
<?php 
	echo form_open('', array('class' => 'form-stacked'));
	echo $archive; 
	echo form_close();
?>	
</div>