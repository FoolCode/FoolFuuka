<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo sprintf(_("The installation was unable to automatically create the config.php file. You'll have to create %s and paste all the following inside the file. When done, just press on the button on the bottom to be redirected to your FoOlSlide installation."), FCPATH.'config.php');
?>
<br/><br/>
<?php 
$textarea = array(
	'name' => 'config',
	'id' => 'config',
	'value' => $config,
	'style' => 'width:80%; height:600px;',
	'readonly' => '',
	'onClick' => 'SelectAll(\'config\');'
);
echo form_textarea($textarea); 
?>
<br/><br/>
<script type="text/javascript">
function SelectAll(id)
{
    document.getElementById(id).focus();
    document.getElementById(id).select();
}
</script>
<a class="gbutton" href="<?php echo site_url('admin') ?>"><?php echo _('Done') ?></a>