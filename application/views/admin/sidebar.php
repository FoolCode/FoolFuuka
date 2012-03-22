<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>


<?php if(!empty($sidebar)) : ?>
<div class="well">
	<ul class="nav nav-list" style="padding: 9px 0">
		<?php 
		//echo '<pre>'.print_r($sidebar, TRUE).'</pre>';die();
			foreach($sidebar as $key => $item) : ?>
			<li class="nav-header">
				<?php echo $item['name'] ?>
			</li>
			<?php foreach($item['content'] as $k => $i) : ?>
				<li <?php echo ($i['active']?'class="active"':'') ?>>
					<a href="<?php echo $i['href'] ?>">
						<?php if($i['icon']) ?> <i class="<?php echo $i['icon'] ?><?php if($i['active']) echo ' icon-white' ?>"></i>
						<?php echo $i['name'] ?>
					</a>
				</li>
			<?php endforeach; endforeach; ?>
	</ul>
</div>
<?php else : ?>

<div class="span3" style="height:10px;">
</div>

<?php endif; ?>