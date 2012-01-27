<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$data_array = json_decode($data);
//print_r($data_array);
$count = 0;
foreach ($data_array as $key => $item) :
	?>

<div class="image_reposts_image">
	<div class="image_reposts_number"><strong>#<?php echo $key+1 ?></strong> - Reposts: <?php echo $item->total ?></div>
<a href="<?php echo site_url(array(get_selected_radix()->shortname, 'image', urlencode(substr($item->media_hash, 0, -2)))) ?>">
	<img src="<?php echo $this->post->get_image_href(get_selected_radix(), $item, TRUE) ?>" />
</a>
</div>
<?php
endforeach; ?>
<div class="clearfix"></div>

<style type="text/css">
	.image_reposts_image {
		float:left;
		margin:20px;
		text-align:center;
		height:250px;
		width:250px;
	}
</style>