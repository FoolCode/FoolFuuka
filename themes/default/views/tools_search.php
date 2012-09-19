<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');

if ( ! isset($radix) && \Preferences::get('fu.sphinx.global'))
{
	// search can work also without a radix selected
	$search_radix = '_';
}
else if (isset($radix))
{
	$search_radix = $radix->shortname;
}

if (isset($search_radix)) :
?>

<ul class="nav pull-right">
	<li><a  style="padding-right: 0px"data-function="searchShow" href="<?= \Uri::create(array($search_radix, 'advanced_search')) ?>"><?= e(__('Adv.')) ?></a></li>
	
	<?= \Form::open(
			array(
				'class' => 'navbar-search',
				'method' => 'POST',
				'action' => Uri::create($search_radix.'/search')
			)
		);?>
	
	<li>
		<?= Form::input(array(
			'name' => 'text',
			'data-toggle' => 'dropdown',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
			'class' => 'search-query',
			'placeholder' => ($search_radix  !== '_') ? __('Search or Insert Post No.') : __('Global Search')
		));
		?>
	</li>
	<?= \Form::close() ?>
	<li class="dropdown">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding-left:2px; padding-right:4px;">
			<b class="caret"></b>
		</a>
		<ul class="dropdown-menu" style="margin-left:4px">
			<li class="nav-header"><?= e(__('Your searches')) ?></li>
			<li>
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
							$text .= e(urldecode($latest_search['text'])) . ' ';
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
				
			</li>
		</ul>
	</li>
</ul>
<?php endif ?>