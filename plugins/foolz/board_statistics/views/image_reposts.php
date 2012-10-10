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
		
		<?php
			$media = \Media::forge_empty(\Radix::get_selected());
			$media->media_id = $item->media_id;
			$media->media_hash = $item->media_hash;
			$media->media = $item->media;
			$media->preview_op = $item->preview_op;
			$media->preview_reply = $item->preview_reply;
			$media->total = $item->total;
			$media->banned = $item->banned;
			$media->op = true;
		?>
		
		<a href="<?= Uri::create(array(Radix::get_selected()->shortname, 'search', 'image', $media->get_safe_media_hash())) ?>">
			<img  src="<?= $media->get_thumb_link()  ?>" />
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
