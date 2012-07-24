<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');

if (!isset($radix) && Preferences::get('fu.sphinx.global'))
{
	// search can work also without a radix selected
	$radix = new stdClass();
	$radix->shortname = '';
}

if (isset($radix)) :
?>

<ul class="nav pull-right">
	<li class="search-dropdown">
		<?php
		echo Form::open(
			array(
				'class' => 'navbar-search pull-right',
				'method' => 'GET',
				'action' => Uri::create(((!$radix->shortname)?'':'@radix/' . $radix->shortname) . '/search')
			)
		);
		echo Form::input(array(
			'name' => 'text',
			'data-function' => 'searchShow',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
			'class' => 'span4 search-query',
			'placeholder' => ($radix->shortname)?__('Search or Insert Post No. or Thread URL'):__('Global Search')
		));
		?>
		<div class="search-dropdown-menu">
			<ul>
				<li>
					<?php
					echo Form::submit(array(
						'class' => 'btn btn-inverse btn-mini',
						'value' => __('Undefined'),
						'name' => 'submit_undefined',
						'style' => 'display:none;'
					));
					?>

					<?php
					echo Form::submit(array(
						'class' => 'btn btn-inverse btn-mini',
						'value' => __('Search'),
						'name' => 'submit_search'
					));
					?>

					<?php
					if (Preferences::get('fu.sphinx.global')) :
						echo Form::submit(array(
							'class' => 'btn btn-inverse btn-mini',
							'value' => __('Global Search'),
							'name' => 'submit_search_global'
						));
					endif;
					?>

					<?php
					echo Form::submit(array(
						'class' => 'btn btn-inverse btn-mini',
						'value' => __('Go to Post'),
						'name' => 'submit_post'
					));
					?>

					<?php
					if (Auth::has_access('maccess.mod')) :
						echo Form::submit(array(
							'class' => 'btn btn-danger btn-mini',
							'value' => __('Deletion Mode'),
							'name' => 'deletion_mode'
						));
					endif;
					?>
				</li>

				<li class="divider"></li>

			</ul>

			<ul class="pull-right">
				<li><?= __('Filters:') ?></li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"]))
									? TRUE : FALSE));
						?>
						<?= __('All') ?>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted')
								? TRUE : FALSE));
						?>
						<?= __('Only Deleted Posts') ?>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted')
								? TRUE : FALSE));
						?>
						<?= __('Only Non-Deleted Posts') ?>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"]))
								? TRUE : FALSE));
						?>
						<span><?= __('All') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only')
								? TRUE : FALSE));
						?>
						<span><?= __('Only Ghost Posts') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none')
								? TRUE : FALSE));
						?>
						<span><?= __('Only Non-Ghost Posts') ?></span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'filter', 'value' => '', 'checked' => (empty($search["filter"]))
								? TRUE : FALSE));
						?>
						<span><?= __('All') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text')
								? TRUE : FALSE));
						?>
						<span><?= __('Only Containing Images') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image')
								? TRUE : FALSE));
						?>
						<span><?= __('Only Containing Text') ?></span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'type', 'value' => '', 'checked' => (empty($search["type"]))
								? TRUE : FALSE));
						?>
						<span><?= __('All') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op')
								? TRUE : FALSE));
						?>
						<span><?= __('Only OP Posts') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'type', 'value' => 'posts', 'checked' => (!empty($search["type"]) && $search["type"] == 'posts')
								? TRUE : FALSE));
						?>
						<span><?= __('Only Reply Posts') ?></span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'capcode', 'value' => '', 'checked' => (empty($search["capcode"]))
								? TRUE : FALSE));
						?>
						<span><?= __('All') ?></span>
					</label>
				</li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'capcode', 'value' => 'user', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'user')
								? TRUE : FALSE));
						?>
						<span><?= __('Only by Users') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'capcode', 'value' => 'mod', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'mod')
								? TRUE : FALSE));
						?>
						<span><?= __('Only by Mods') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'capcode', 'value' => 'admin', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'admin')
								? TRUE : FALSE));
						?>
						<span><?= __('Only by Admins') ?></span>
					</label>
				</li>

				<li class="divider"></li>

				<li>
					<label>
						<?= Form::radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc'))
								? TRUE : FALSE));
						?>
						<span><?= __('New Posts First') ?></span>
					</label>
				</li>
				<li>
					<label>
						<?= Form::radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc')
								? TRUE : FALSE));
						?>
						<span><?= __('Old Posts First') ?></span>
					</label>
				</li>
			</ul>

			<ul class="pull-left">

				<li class="input-prepend"><label for="subject" class="add-on"><?= __('Subject') ?></label><?php
					echo Form::input(array('name' => 'subject', 'id' => 'subject', 'value' => (isset($search["subject"]))
						? rawurldecode($search["subject"]) : ''))
				?></li>
				<li class="input-prepend"><label for="username" class="add-on"><?= __('Username') ?></label><?php
					echo Form::input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"]))
						? rawurldecode($search["username"]) : ''))
				?></li>
				<li class="input-prepend"><label for="tripcode" class="add-on"><?= __('Tripcode') ?></label><?php
					echo Form::input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"]))
						? rawurldecode($search["tripcode"]) : ''))
				?></li>
				<li class="input-prepend"><label for="email" class="add-on"><?= __('E-mail') ?></label><?php
					echo Form::input(array('name' => 'email', 'id' => 'email', 'value' => (isset($search["email"]))
						? rawurldecode($search["email"]) : ''))
				?></li>
				<li class="input-prepend"><label for="filename" class="add-on"><?= __('Filename') ?></label><?php
					echo Form::input(array('name' => 'filename', 'id' => 'filename', 'value' => (isset($search["filename"]))
						? rawurldecode($search["filename"]) : ''))
				?></li>
				<li class="input-prepend"><label for="date_start" class="add-on"><?= __('From Date') ?></label><?php
					$date_array = array(
						'placeholder' => 'YYYY-MM-DD',
						'name' => 'start',
						'id' => 'date_start'
					);

					if (isset($search["date_start"]))
					{
						$date_array['value'] = rawurldecode($search["date_start"]);
					}

					echo Form::input($date_array);
				?></li>

				<li class="input-prepend"><label for="date_end" class="add-on"><?= __('To Date') ?></label><?php
					$date_array = array(
						'placeholder' => 'YYYY-MM-DD',
						'name' => 'end',
						'id' => 'date_end',
					);

					if (isset($search["date_end"]))
					{
						$date_array['value'] = rawurldecode($search["date_end"]);
					}
					echo Form::input($date_array);
				?></li>

				<?php if (Auth::has_access('maccess.mod')) : ?>
				<li class="input-prepend"><label for="poster_ip" class="add-on"><?= __('IP Address') ?></label><?php
					echo Form::input(array('name' => 'poster_ip', 'id' => 'poster_ip', 'value' => (isset($search["poster_ip"]))
						? rawurldecode($search["poster_ip"]) : ''))
				?></li>
				<?php endif; ?>

				<li class="input-prepend"><label for="image" class="add-on"><?= __('Image Hash') ?></label><?php
					echo Form::input(array('name' => 'image', 'id' => 'image', 'value' => (isset($search["image"]))
						? rawurldecode($search["image"]) : ''))
				?>
				<div class="help"><?= __('You can drag-and-drop an image onto the field above.') ?></div>
				</li>
				<li class="divider"></li>
				<li><?= __('Your 5 Latest Searches:') ?>
					<div class="pull-right"><a href="#" data-function="clearLatestSearches" class="btn btn-warning btn-mini" style="margin:0; padding: 1px 3px; line-height:normal;color:#FFF; position:relative; top:-1px;"><?= __('Clear') ?></a></div>
				</li>
				<?php
					if (isset($latest_searches) || $latest_searches = @json_decode(\Cookie::get('search_latest_5'), TRUE))
					{
						// sanitization
						foreach($latest_searches as $item)
						{
							// all subitems must be array, all must have 'radix'
							if (!is_array($item) || !isset($item['board']))
							{
								$latest_searches = array();
								break;
							}
						}

						foreach($latest_searches as $latest_search)
						{
							$uri = ($latest_search['board'] === FALSE ? '' : $latest_search['board']) . '/search/';
							$text = ($latest_search['board'] === FALSE) ? '<strong>global:</strong> ' : '/<strong>' . e($latest_search['board']) . '</strong>/: ';
							unset($latest_search['board']);
							if (isset($latest_search['text']))
							{
								$uri .= 'text/' . $latest_search['text'] . '/';
								$text .= fuuka_htmlescape(urldecode($latest_search['text'])) . ' ';
								unset($latest_search['text']);
							}
							if (isset($latest_search['order']) && $latest_search['order'] == 'desc')
							{
								unset($latest_search['order']);
							}

							$extra_text = '';
							$extra_text_br = '';
							foreach($latest_search as $k => $i)
							{
								$uri .= $k.'/'.$i.'/';
								$extra_text .= '<span class="options">[' . e($k) . '] ' . e(urldecode($i)) . ' </span>';
								$extra_text_br .= '<br/><span class="options">[' . e($k) . '] ' . e(urldecode($i)) . ' </span>';
							}

							echo '<li title="' . htmlspecialchars($text . $extra_text_br) . '" class="latest_search"><a href="' . Uri::create($uri) . '">' . $text . ' ' . $extra_text . '</a></li>';
						}
					}
				?>
				</ul>
		</div>
		<?= Form::close(); ?>
	</li>
</ul>

<?php endif; ?>
