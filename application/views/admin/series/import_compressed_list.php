<script type="text/javascript">

	archives = <?php echo json_encode($archives); ?>;
	default_team = '<?php echo addslashes(get_setting('fs_gen_default_team')) ?>';

	function chapter_options(index, chapter) {
		result = "<div class='chapter chapter_" + index + "'><table class='form'>";
		
		result += "<?php echo _('Chapter Filename') ?>: <b>" + chapter.filename + "</b>";

		result += "<input class='input_hidden' type='hidden' value='" + index + "' /></td></tr>";

		result += "<tr><td>Title:</td><td><input class='input_name' type='text' value='' /></td></tr>";

		if(chapter.numbers.length > 1)
			result += "<tr><td>Volume number:</td><td><input class='input_volume' type='text' value='" + chapter.numbers[chapter.numbers.length-2] + "' /></td></tr>";
		else
			result += "<tr><td>Volume number:</td><td><input class='input_volume' type='text' value='0' /></td></tr>";
		

		if(chapter.numbers.length > 0)
			result += "<tr><td>Chapter number:</td><td><input class='input_chapter' type='text' value='" + chapter.numbers[chapter.numbers.length-1] + "' /></td></tr>";
		else
			result += "<tr><td>Chapter number:</td><td><input class='input_chapter' type='text' value='0' /></td></tr>";
		
		result += "<tr><td>Subchapter number:</td><td><input class='input_subchapter' type='text' value='0' /></td></tr>";		
		
		result += '<tr><td>Chapter language:</td><td><?php echo str_replace("\n", "", form_language(array('name' => 'input_language', 'class' => 'input_language'))); ?></td></tr>';

		result += "<tr><td>Teams:</td><td class='insert_teams'>";
			
		team_found = false;
		jQuery.each(chapter.teams, function(index, team){
			team_found = true;
			result += "<input type='text' class='set_teams' value='" + team.substring(1, team.length-1) + "' />";
		});
		if(!team_found) {
			result += "<input type='text' class='set_teams' value='" + default_team + "' />";
		}
		
		result += "<br/><input type='text' class='set_teams' value='' onKeyUp='addField(this);' /></td></tr>";
		
		result += "</table>";
			
		result += "<a class='btn' href='#' onClick='submit_chapter(this); return false;'>Submit</a>";
		
		result += "</div>";
		return result;
	}
	
	function chapters_options(chapters) {
		jQuery.each(chapters, function(index, data){
			jQuery('#chapters').append(chapter_options(index, data));
		});
	}
	
	function set_volume() {
		value = jQuery('#set_volume').val();
		jQuery('.input_volume').each(function(index){
			jQuery(this).val(value);
		});
	}
	
	function set_init_chapter() {
		value = jQuery('#set_init_chapter').val();
		count = 0;
		jQuery('.input_chapter').each(function(index){
			jQuery(this).val(parseInt(value) + parseInt(count));
			count++;
		});
	}
	
	function set_teams() {
		result = "";
		jQuery('.set_teams', '.teams_setter').each(function(index){
			if (jQuery(this).val() != "")
				result += "<input type='text' id='set_teams' class='set_teams' value=\"" + jQuery(this).val().replace('"', '\"') + "\" /><br/>";
		});
		
		result += "<input type='text' id='set_teams' class='set_teams' value='' onKeyUp='addField(this);' />";
		jQuery(".insert_teams").each(function(index){
			jQuery(this).html(result);
		});
	}
	
	function try_filename()
	{
		result = {};
		
		value = jQuery('#manual_filename').val();
		value = value.replace("{v","v");
		value = value.replace("v}","v");
		value = value.replace("{s","s");
		value = value.replace("s}","s");
		firstC = value.indexOf("{c");
		if(firstC)
		{
			lastC = value.indexOf("c}");
		}
		
		
		value = jQuery('#manual_filename').val();
		value = value.replace("{c", "c");
		value = value.replace("c}","c");
		value = value.replace("{v","v");
		value = value.replace("v}","v");
		firstS = value.indexOf("{s");
		if(firstS)
		{
			lastS = value.indexOf("s}");
		}
		
		value = jQuery('#manual_filename').val();
		value = value.replace("{c", "c");
		value = value.replace("c}","c");
		value = value.replace("{s","s");
		value = value.replace("s}","s");
		firstV = value.indexOf("{v");
		if(firstV)
		{
			lastV = value.indexOf("v}");
		}
	
		jQuery.each(archives, function(index, data){
			if(firstC)jQuery('.chapter_' + index + " .input_chapter").val(data.filename.substring(firstC, lastC));
			if(firstS)jQuery('.chapter_' + index + " .input_subchapter").val(data.filename.substring(firstS, lastS));
			if(firstV)jQuery('.chapter_' + index + " .input_volume").val(data.filename.substring(firstV, lastV));
		});
	}
	
	function submit_chapter(obj, all)
	{
		box = jQuery(obj).parent();
		jQuery(box).css({'background': '#FCDEDE', 'opacity' : '0.6'});
		teams = [];
		jQuery('.set_teams',box).each(function(index){
			teams.push(jQuery(this).val());
		})
		
		index = jQuery('.input_hidden', box).val();
		jQuery.post('<?php echo site_url('/admin/series/import/' . $comic->stub) ?>', {
			action: 'execute',
			type: 'single_compressed',
			name: '',
			server_path: archives[index].server_path,
			comic_id: archives[0].comic_id,
			name: jQuery('.input_name', box).val(),
			chapter: jQuery('.input_chapter', box).val(),
			subchapter: jQuery('.input_subchapter', box).val(),
			volume: jQuery('.input_volume', box).val(),
			language: jQuery('[name="input_language"]', box).val(),
			team: teams
		}, function(result){
			if(result.error === undefined) jQuery(box).css({'background': '#DDFCE7', 'opacity' : '0.4'});
			else jQuery(box).css({'opacity' : '0.9'});
		},'json').complete(function(){
			if(all !== undefined) {
				submit_all(all+1);
			}});
	}
	
	function submit_all(index){
		submit_chapter(jQuery('.chapter:eq('+index+') .btn'), index);
		return false;
	}
	
	jQuery(document).ready(function(){
		chapters_options(archives);
		jQuery('#manual_filename').val(archives[0].filename);
		
		jQuery('form#import_settings').keypress(function(e) {
			if (e.which == 13) {
				console.debug(e.target);
				var input = jQuery(e.target).attr('id');
				if (input == 'manual_filename')
					try_filename();
				if (input == 'set_volume')
					set_volume();
				if (input == 'set_init_chapter')
					set_init_chapter();
				if (input == 'set_teams')
					set_teams();
			}
		});
		
		jQuery('a[rel=popover-import]').popover({
			live: true,
			placement: 'left'
		});
	});
	
</script>
<style>
	.chapter {
		padding:8px; 
		border:1px solid #aaa;
		margin:8px;
		border-radius:5px;
		-webkit-border-radius:5px;
		-moz-border-radius:5px;
	}
	.chapter div {
		margin:5px;
		width:700px;
	}
</style>

<div class="table">
	<h3><?php echo _('Import'); ?></h3>
	<div id="tools">
		<form id="import_settings" class="form-stacked" onsubmit="return false;">
			<fieldset>
				<div class="clearfix">
					<label><?php echo _('Manual Setup'); ?>:</label>
					<div class="input"><input type="text" id="manual_filename" value="" /> <a href="#" class="btn" onClick="try_filename(); return false;"><?php echo _('Try') ?></a></div>
					<span class="help-inline"><?php echo _('Attempts to detect the volume, chapter, and sub-chapter numbers for the chapters listed below. (For Example: "Chapter_123" would be detected by using "Chapter_{ccc}" in the field.)') ?></span><br/>
					<span class="help-inline">Variables: {vv} denotes volume, {cc} denotes chapter, and {ss} denotes sub-chapter.</span>
				</div>
				<div class="clearfix">
					<label><?php echo _('Mass Set Volumes'); ?>:</label>
					<div class="input"><input type="text" id="set_volume" value="" /> <a href="#" class="btn" onClick="set_volume(); return false;"><?php echo _('Set') ?></a></div>
					<span class="help-inline"><?php echo _('Sets the volume for all of the chapters listed below.') ?></span>
				</div>
				<div class="clearfix">
					<label><?php echo _('Set Initial Chapter Number'); ?>:</label>
					<div class="input"><input text="text" id="set_init_chapter" value="" /> <a href="#" class="btn" onClick="set_init_chapter(); return false;"><?php echo _('Set') ?></a></div>
					<span class="help-inline"><?php echo _('Sets the initial chapter and auto-increments for additional chapters listed below.') ?></span>
				</div>
				<div class="clearfix">
					<label><?php echo _('Mass Set Teams'); ?>:</label>
					<div class="input">
						<div class="teams_setter">
							<input type="text" id="set_teams" class="set_teams" value="" /> <a href="#" class="btn" onClick="set_teams(); return false;"><?php echo _('Set') ?></a>
							<br/><input type="text" id="set_teams" class="set_teams" value="" onKeyUp="addField(this);" />
						</div>
					</div>
					<span class="help-inline"><?php echo _('Sets the teams for all the chapters listed below.') ?></span>
				</div>
			</fieldset>			
		</form>
	</div>
</div>

<br/>

<div class="table">
	<h3 style="float: left"><?php echo _('Adding Chapters to'); ?>: <?php echo $comic->name ?></h3>
	<span style="float: right; padding: 5px"><a href="#" onClick="return submit_all(0)" class="btn primary"><?php echo _('Submit All') ?></a></span>
	<hr class="clear"/>
	
	<br/>
	<div id="chapters" style="padding-right: 10px; padding-bottom: 10px"></div>
</div>
