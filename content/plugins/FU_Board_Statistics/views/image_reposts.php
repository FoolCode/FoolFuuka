<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php $data_array = json_decode($data); ?>
<?php foreach ($data_array as $key => $item) : ?>
	<div class="image_reposts_image">
		<div class="image_reposts_number">
			<strong>#<?php echo $key+1 ?></strong> - Reposts: <?php echo $item->total ?>
		</div>
		<a href="<?php echo site_url(array(get_selected_radix()->shortname, 'image', substr(urlsafe_b64encode(urlsafe_b64decode($item->media_hash)), 0, -2))) ?>">
			<img src="<?php echo $this->post->get_media_link(get_selected_radix(), $item, TRUE) ?>" />
		</a>
	</div>
<?php endforeach; ?>

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
