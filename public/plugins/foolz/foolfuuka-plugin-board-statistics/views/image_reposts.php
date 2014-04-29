<?php use Foolz\Foolfuuka\Model\CommentBulk;
use Foolz\Foolfuuka\Model\Media;

$data_array = json_decode($data); ?>
<?php foreach ($data_array as $key => $item) : ?>
    <div class="image_reposts_image">
        <div class="image_reposts_number">
            <strong>#<?= $key+1 ?></strong> - Reposts: <?= $item->total ?>
        </div>

        <?php
            $bulk = new CommentBulk();
            $bulk->import((array) $item, $this->radix);
            $media = new Media($this->getContext(), $bulk);
            $media->op = true;
        ?>

        <a href="<?= $this->uri->create([$this->radix->shortname, 'search', 'image', $media->getSafeMediaHash()]) ?>">
            <img src="<?= $media->getThumbLink($this->getRequest())  ?>" />
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
