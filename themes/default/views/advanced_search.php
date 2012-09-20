<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>
<div class="advanced_search clearfix">
<h4><?= e(__('Advanced Search')) ?></h4>

<?php
if ( ! isset($radix) && \Preferences::get('fu.sphinx.global'))
{
	// search can work also without a radix selected
	$search_radix = '_';
}
else if (isset($radix))
{
	$search_radix = $radix->shortname;
}
?>

<?= \Form::open(array('method' => 'POST', 'action' => Uri::create($search_radix.'/search'))); ?>

<div class="buttons clearfix">
<?= \Form::submit(array(
	'class' => 'btn btn-inverse',
	'value' => __('Search'),
	'name' => 'submit_search',
));
?>
	
<?= \Form::submit(array(
	'class' => 'btn btn-inverse',
	'value' => __('Search on all boards'),
	'name' => 'submit_search_global',
));
?>
</div>
<?php

$search_structure = \Search::structure();

echo '<div class="checkboxes pull-right"><table class="table"><tbody>';
foreach ($search_structure as $element)
{
	if (isset($element['access']) && ! \Auth::has_access($element['access']))
	{
		continue;
	}
		
	if ($element['type'] === 'radio')
	{
		echo '<tr><td>'.e($element['label']).'</td><td>';
		foreach ($element['elements'] as $el)
		{
			echo '<label>';
			echo \Form::radio($element['name'], $el['value'] ? : '', isset($search[$element['name']]) && $el['value'] === $search[$element['name']]);
			echo ' '.e($el['text']);
			echo '</label>';
		}
		echo '</td></tr>';
	}
}
echo '</tbody></table></div>';

foreach ($search_structure as $element)
{
	if (isset($element['access']) && ! \Auth::has_access($element['access']))
	{
		continue;
	}
	
	if ($element['type'] === 'input')
	{
		echo '<div class="input-prepend">';
		echo '<label class="add-on" for="search_form_'.$element['name'].'">'.e($element['label']).'</label>';
		echo \Form::input(array(
			'name' => $element['name'],
			'id' => 'search_form_'.$element['name'],
			'value' => (isset($search[$element['name']])) ? rawurldecode($search[$element['name']]) : '',
			'placeholder' => (isset($element['placeholder'])) ? $element['placeholder'] : '',
		));
		echo '</div>';
	}
	
	if ($element['type'] === 'date')
	{
		echo '<div class="input-prepend">';
		echo '<label class="add-on" for="search_form_'.$element['name'].'">'.e($element['label']).'</label>';
		echo \Form::input(
			array('type' => 'date',
				'name' => $element['name'],
				'placeholder' => 'YYYY-MM-DD',
				'value' => (isset($search[$element['name']])) ? rawurldecode($search[$element['name']]) : ''
			)
		);
		echo '</div>';
	}
}
?>


<?php if ( ! isset($radix) || $radix->sphinx) : ?>
<div class="radixes">
	<div>
		<?php 
		$radixes = \Radix::get_archives();
		foreach($radixes as $key => $r)
		{
			if ( ! $r->sphinx)
			{
				unset($radixes[$key]);
			}
		}
		if ($radixes) :
		?>
		<div><h5><?=e(__('On these archives'))?></h5>
			<a href="#" data-function="checkAll" class="btn btn-mini pull-right check"><?= e(__('Check all')) ?></a>
			<a href="#" data-function="uncheckAll" class="btn btn-mini pull-right uncheck"><?= e(__('Uncheck all')) ?></a>
		</div>
		<?php
		foreach ($radixes as $r)
		{
			echo '<label>'.\Form::checkbox('boards[]', $r->shortname, ! isset($radix) || $radix->shortname === $r->shortname).' /'.e($r->shortname).'/</label>';
		}
		?>
		<?php endif; ?>
	</div>
	
	<div style="clear:left; padding-top: 10px">
		<?php 
		$radixes = \Radix::get_boards();
		foreach($radixes as $key => $r)
		{
			if ( ! $r->sphinx)
			{
				unset($radixes[$key]);
			}
		}
		if ($radixes):
		?>
		<div><h5><?= e(__('On these boards')) ?></h5>
			<a href="#" data-function="checkAll" class="btn btn-mini pull-right check"><?= e(__('Check all')) ?></a>
			<a href="#" data-function="uncheckAll" class="btn btn-mini pull-right uncheck"><?= e(__('Uncheck all')) ?></a>
		</div>
		<?php
		foreach ($radixes as $r)
		{
			echo '<label>'.\Form::checkbox('boards[]', $r->shortname, ! isset($radix) || $radix->shortname === $r->shortname).' /'.e($r->shortname).'/</label>';
		}
		?>
		<?php endif; ?>
	</div>
</div>
<?php endif ?>

<?= \Form::close() ?>
</div>
