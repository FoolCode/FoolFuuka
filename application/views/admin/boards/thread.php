<?php
$this->buttoner[] = array(
	'text' => _('Delete chapter'),
	'href' => site_url('/admin/series/delete/chapter/' . $chapter->id),
	'plug' => _('Do you really want to delete this chapter and its pages?')
);

$this->buttoner[] = array(
	'text' => _('Read chapter'),
	'href' => $chapter->href()
);
?>

<div class="table">
	<h3 style="float: left"><?php echo _('Chapter Information'); ?></h3>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear"/>
	<?php
		echo form_open('', array('class' => 'form-stacked'));
		echo $table;
		echo form_close();
	?>
</div>

<br/>

<div class="table">
	<h3><?php echo _('Pages'); ?></h3>

	<div id="fileupload" style="margin-right: 10px; padding-bottom: 10px">
		<link href="<?php echo site_url(); ?>assets/jquery-file-upload/jquery-ui.css?v=<?php echo get_setting('fs_priv_version') ?>" rel="stylesheet" id="theme" />
		<link href="<?php echo site_url(); ?>assets/jquery-file-upload/jquery.fileupload-ui.css?v=<?php echo get_setting('fs_priv_version') ?>" rel="stylesheet" />
		<div class="fileupload-buttonbar">
			<?php echo form_open_multipart("", array('style' => 'margin-bottom:0px;')); ?>
			<label class="fileinput-button">
				<span><?php echo _("Add files...") ?></span>
				<input type="file" name="Filedata[]" multiple>
			</label>
			<button type="submit" class="start"><?php echo _("Start upload") ?></button>
			<button type="reset" class="cancel"><?php echo _("Cancel upload") ?></button>
			<button type="button" class="delete"><?php echo _("Delete files") ?></button>
			<?php echo form_close(); ?>
		</div>
		<div class="fileupload-content">
			<table class="files zebra-striped"></table>
			<div class="fileupload-progressbar"></div>
		</div>
	</div>
</div>
</div>
<script id="template-upload" type="text/x-jquery-tmpl">
    <tr class="template-upload{{if error}} ui-state-error{{/if}}">
        <td class="name">${name}</td>
        <td class="size">${sizef}</td>
        {{if error}}
		<td class="error" colspan="2" align="right">Error:
			{{if error === 'maxFileSize'}}File is too big
			{{else error === 'minFileSize'}}File is too small
			{{else error === 'acceptFileTypes'}}Filetype not allowed
			{{else error === 'maxNumberOfFiles'}}Max number of files exceeded
			{{else}}${error}
			{{/if}}
		</td>
        {{else}}
		<td class="progress"><div></div></td>
		<td class="start" width="32"><button>Start</button></td>
        {{/if}}
        <td class="cancel" width="32"><button>Cancel</button></td>
    </tr>
</script>
<script id="template-download" type="text/x-jquery-tmpl">
    <tr class="template-download{{if error}} ui-state-error{{/if}}">
        {{if error}}
		<td class="name">${name}</td>
		<td class="size">${sizef}</td>
		<td class="error" colspan="2">Error:
			{{if error === 1}}File exceeds upload_max_filesize (php.ini directive)
			{{else error === 2}}File exceeds MAX_FILE_SIZE (HTML form directive)
			{{else error === 3}}File was only partially uploaded
			{{else error === 4}}No File was uploaded
			{{else error === 5}}Missing a temporary folder
			{{else error === 6}}Failed to write file to disk
			{{else error === 7}}File upload stopped by extension
			{{else error === 'maxFileSize'}}File is too big
			{{else error === 'minFileSize'}}File is too small
			{{else error === 'acceptFileTypes'}}Filetype not allowed
			{{else error === 'maxNumberOfFiles'}}Max number of files exceeded
			{{else error === 'uploadedBytes'}}Uploaded bytes exceed file size
			{{else error === 'emptyResult'}}Empty file upload result
			{{else}}${error}
			{{/if}}
		</td>
        {{else}}
		<td class="name">
			<a href="${url}"{{if thumbnail_url}} target="_blank"{{/if}}>${name}</a>
		</td>
		<td class="size">${sizef}</td>
		<td colspan="2"></td>
        {{/if}}
        <td class="delete" width="32">
            <button data-type="${delete_type}" data-url="${delete_url}" data-id="${delete_data}">Delete</button>
        </td>
    </tr>
</script>
<script src="<?php echo site_url(); ?>assets/js/jquery-ui.js?v=<?php echo get_setting('fs_priv_version') ?>"></script>
<script src="<?php echo site_url(); ?>assets/js/jquery.tmpl.js?v=<?php echo get_setting('fs_priv_version') ?>"></script>
<script src="<?php echo site_url(); ?>assets/jquery-file-upload/jquery.fileupload.js?v=<?php echo get_setting('fs_priv_version') ?>"></script>
<script src="<?php echo site_url(); ?>assets/jquery-file-upload/jquery.fileupload-ui.js?v=<?php echo get_setting('fs_priv_version') ?>"></script>
<script src="<?php echo site_url(); ?>assets/jquery-file-upload/jquery.iframe-transport.js?v=<?php echo get_setting('fs_priv_version') ?>"></script>

<script type="text/javascript">
	jQuery(function () {
		jQuery('#fileupload').fileupload({
			url: '<?php echo site_url('/admin/series/upload/compressed_chapter'); ?>',
			sequentialUploads: true,
			formData: [
				{
					name: 'chapter_id',
					value: <?php echo $chapter->id; ?>
				}
			]
		});

		jQuery.post('<?php echo site_url('/admin/series/get_file_objects'); ?>', { id : <?php echo $chapter->id; ?> }, function (files) {
			var fu = jQuery('#fileupload').data('fileupload');
			fu._adjustMaxNumberOfFiles(-files.length);
			fu._renderDownload(files)
			.appendTo(jQuery('#fileupload .files'))
			.fadeIn(function () {
				jQuery(this).show();
			});

		});

		jQuery('#fileupload .files a:not([target^=_blank])').live('click', function (e) {
			e.preventDefault();
			jQuery('<iframe style="display:none;"></iframe>')
			.prop('src', this.href)
			.appendTo('body');
		});
	});
	
	$.widget('blueimpUIX.fileupload', $.blueimpUI.fileupload, {
		_deleteHandler: function(e) {
			e.preventDefault();
			var button = $(this);
			e.data.fileupload._trigger('destroy', e, {
				context: button.closest('.template-download'),
				url: button.attr('data-url'),
				type: button.attr('data-type'),
				data: { id: button.attr('data-id') },
				dataType: e.data.fileupload.options.dataType
			});
		},
		options: { 
			done: function (e, data) {
				var that = $(this).data('fileupload');
				if (data.context) {
					data.context.each(function (index) {
						var file = ($.isArray(data.result) &&
							data.result[index]) || {error: 'emptyResult'};
						if (file.error) {
							that._adjustMaxNumberOfFiles(1);
						}
						
						if (data.result.length > 1) {
							$(this).fadeOut(function() {
								that._adjustMaxNumberOfFiles(-data.result.length);
								that._renderDownload(data.result)
									.appendTo(jQuery('#fileupload .files'))
									.fadeIn(function() {
										jQuery(this).show();
									});
							});
						} else {
							$(this).fadeOut(function () {
								that._renderDownload([file])
									.css('display', 'none')
									.replaceAll(this)
									.fadeIn(function () {
										// Fix for IE7 and lower:
										$(this).show();
									});
							});
						}
					});
				} else {
					that._renderDownload(data.result)
						.css('display', 'none')
						.appendTo($(this).find('.files'))
						.fadeIn(function () {
							// Fix for IE7 and lower:
							$(this).show();
						});
				}
			}
		}
		
	});
</script>
