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
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '',
			'class' => 'search-query',
			'placeholder' => ($search_radix  !== '_') ? __('Search or insert post number') : __('Search through all the boards')
		));
		?>
	</li>
	<?= \Form::close() ?>
</ul>
<?php endif ?>