<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<?php $data_array = json_decode($data); ?>
<?php foreach ($data_array as $key => $item) : ?>
	<div class="image_reposts_image">
		<div class="image_reposts_number">
			<strong>#<?php echo $key+1 ?></strong> - Reposts: <?php echo $item->total ?>
		</div>
		<a href="<?php echo Uri::create(array(Radix::get_selected()->shortname, 'search', 'image', $this->post->get_media_hash($item->media_hash, TRUE))) ?>">
			<img src="<?php echo $this->post->get_media_link(Radix::get_selected(), $item, TRUE) ?>" />
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
