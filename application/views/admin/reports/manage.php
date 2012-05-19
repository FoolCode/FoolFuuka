<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/*
 * This view depends fully on the default theme
 */
echo link_tag('content/themes/default/style.css?v=' . FOOL_VERSION);
?>
<script src="<?php echo site_url() ?>content/themes/default/plugins.js?v=<?php echo FOOL_VERSION ?>"></script>
<script src="<?php echo site_url() ?>content/themes/default/board.js?v=<?php echo FOOL_VERSION ?>"></script>
<style>
	[data-function=activateModeration] {
		display:none !important;
	}
	
	.post_mod_controls {
		display:block !important;
	}
</style>
<div class="theme_default clearfix" style="padding-bottom: 15px">
	<article class="thread clearfix">
		<?php
		
		$modifiers['post_show_board_name'] = TRUE;
		
		foreach ($posts as $key => $p)
		{
			if ($p->thread_num == 0)
				$p->thread_num = $p->num;

			include('content/themes/default/views/board_comment.php');
		}
		?>
	</article>



	<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
		<div class="paginate">
			<ul>
				<?php if ($pagination['current_page'] == 1) : ?>
					<li class="prev disabled"><a href="#">&larr; Previous</a></li>
				<?php else : ?>
					<li class="prev"><a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] - 1); ?>/">&larr; Previous</a></li>
				<?php endif; ?>

				<?php
				if ($pagination['total'] <= 15) :
					for ($index = 1; $index <= $pagination['total']; $index++)
					{
						echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
					}
				else :
					if ($pagination['current_page'] < 15) :
						for ($index = 1; $index <= 15; $index++)
						{
							echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
						}
						echo '<li class="disabled"><span>...</span></li>';
					else :
						for ($index = 1; $index < 10; $index++)
						{
							echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
						}
						echo '<li class="disabled"><span>...</span></li>';
						for ($index = ((($pagination['current_page'] + 2) > $pagination['total']) ? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
									? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
						{
							echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
						}
						if (($pagination['current_page'] + 2) < $pagination['total'])
							echo '<li class="disabled"><span>...</span></li>';
					endif;
				endif;
				?>

				<?php if ($pagination['total'] == $pagination['current_page']) : ?>
					<li class="next disabled"><a href="#">Next &rarr;</a></li>
				<?php else : ?>
					<li class="next"><a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] + 1); ?>/">Next &rarr;</a></li>
				<?php endif; ?>
			</ul>
		</div>
			<?php endif; ?>
</div>