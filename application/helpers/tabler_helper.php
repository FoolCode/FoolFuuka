<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!function_exists('tabler'))
{

	function tabler($rows, $list = TRUE, $edit = TRUE, $repopulate = FALSE)
	{
		$result = array();
		$CI = & get_instance();


		$rows[] = array(
			"",
			array(
				'type' => 'submit',
				'name' => 'submit',
				'class' => 'btn primary',
				'id' => 'submit',
				'value' => _('Save')
			)
		);

		$echo = "";

		foreach ($rows as $rowk => $row)
		{
			foreach ($row as $colk => $column)
			{
				if ($colk == 0)
				{
					$result[$rowk][$colk]["table"] = $column;
					$result[$rowk][$colk]["form"] = $column;
					$result[$rowk][$colk]["field"] = $rows[$rowk][$colk + 1]['name'];
				}
				else
				{
					if (isset($column['list']) && is_array($column['list']))
					{
						foreach ($column['list'] as $key => $item)
							if (!isset($column['list'][$key]['value']))
								$column['list'][$key]['value'] = "";
					}
					elseif (!isset($column['value']))
						$column['value'] = "";
					if (is_array($column))
					{
						$result[$rowk][$colk]["table"] = writize($column);
						if (isset($column['type']))
						{
							$result[$rowk][$colk]["form"] = formize($column, $repopulate);
							$result[$rowk][$colk]["type"] = $column['type'];
						}
					}
					else
					{
						$result[$rowk][$colk]["table"] = writize($column);
						$result[$rowk][$colk]["form"] = $column;
					}
				}
			}
		}

		// echo '<pre>'; print_r($result); echo '</pre>';
		if ($list && $edit)
		{
			$CI->buttoner[] = array(
				'text' => _('Edit'),
				'href' => '',
				'onclick' => "slideToggle('.plain'); slideToggle('.edit'); return false;"
			);
		}

		if ($list && $edit)
		{
			$echo .= '<div class="plain"><fieldset>';
			foreach ($result as $rowk => $row)
			{
				if (isset($row[1]['type']) && $row[1]['type'] == 'hidden')
				{
					//$echo .= $row[1]['form'];
				}
				else
				{
					if (!isset($row[1]) || $row[1]['table'] != _('Save') && $row[0]['table'] != 'id')
					{
						$echo .= '<div class="clearfix">';
						foreach ($row as $colk => $column)
						{
							if ($colk == 0)
							{
								$echo .= '<label for="' . $column['field'] . '">' . $column['table'] . '</label>';
								$echo .= '<div class="input">';
							}
							else
							{
								$echo .= '<span class="uneditable-input">';
								if (is_array($column['table']))
								{
									foreach ($column['table'] as $mini)
									{
										$echo .= '' . $mini->name . ' ';
									}
								}
								else if ($column['table'] == "")
									$echo .= 'N/A';
								else
									$echo .= $column['table'];
								$echo .= '</span>';
							}
						}
						$echo .= '</div></div>';
					}
				}
			}
			$echo .= '</fieldset></div>';
		}
		elseif ($list)
		{
			$echo .= '<div class="plain"><table class="zebra-striped" rules="rows">';
			foreach ($result as $rowk => $row)
			{
				if (isset($row[1]['type']) && $row[1]['type'] == 'hidden')
				{
					//$echo .= $row[1]['form'];
				}
				else
				{
					if (!isset($row[1]) || $row[1]['table'] != _('Save') && $row[0]['table'] != 'id')
					{
						$echo .= '<tr>';
						foreach ($row as $key => $column)
						{
							if ($key == 'action' && $key !== 0)
								$echo .= '<td><div style="float: right">';
							else
								$echo .= '<td>';
							if (is_array($column['table']))
							{
								foreach ($column['table'] as $mini)
								{
									$echo .= '' . $mini->name . ' ';
								}
							}
							else if ($column['table'] == "")
								$echo .= 'N/A';
							else
								$echo .= $column['table'];
							if ($key == 'action' && $key !== 0)
								$echo .= '</div>';
							$echo .= '</td>';
						}
						$echo .= '</tr>';
					}
				}
			}
			$echo .= '</table></div>';
		}

		if ($edit)
		{
			$echo .= '<div class="edit" ' . (($list && $edit) ? 'style="display:none;"' : '') . '><fieldset>';
			foreach ($result as $rowk => $row)
			{

				if (isset($row[1]['type']) && $row[1]['type'] == 'hidden')
				{
					$echo .= $row[1]['form'];
				}
				else
				{
					$echo .= '<div class="clearfix">';
					foreach ($row as $colk => $column)
					{
						if ($colk == 0)
						{
							$echo .= '<label for="' . $column['field'] . '">' . $column['form'] . '</label>';
							$echo .= '<div class="input">';
						}
						else
						{
							$echo .= $column['form'];
						}
					}
					$echo .= '</div></div>';
				}
			}
			$echo .= '</fieldset></div>';
		}

		return $echo;
	}


}


if (!function_exists('formize'))
{

	function formize($column, $repopulate)
	{
		$CI = & get_instance();
		if ($repopulate && $CI->input->post())
			$column['value'] = (set_value($column['name']) == "") ? $column["value"] : set_value($column['name']);
		if (isset($column['preferences']))
			$column['value'] = get_setting($column['name']);

		if (isset($column['serialized']) && $column['serialized'])
			$column['value'] = unserialize($column["value"]);

		//if($column['type'] == 'input' || $column['type'] == 'nation') $column['value'] = set_value($column['name']);

		if ($column['type'] == 'checkbox')
		{
			if (!is_array($column['value']))
			{
				if ($column['value'] == 1)
					$column['checked'] = 'checked';
				$column['value'] = 1;
			}
		}

		$formize = 'form_' . $column['type'];
		if (!isset($column['type']))
			$formize = "";
		$type = $column['type'];
		if (isset($column['help']))
			$help = $column['help'];
		if (isset($column['text']))
			$text = $column['text'];
		if (isset($column['field']))
			$column['id'] = $column['field'];
		else
			$column['id'] = $column['name'];
		unset($column['rules']);
		unset($column['field']);
		unset($column['type']);
		unset($column['preferences']);
		unset($column['text']);
		unset($column['help']);

		if (is_array($column['value']))
		{
			if ($type == 'checkbox')
			{
				$result = array();
				$minion = $column['value'];
				$result[] = '<ul class="inputs-list">';
				foreach ($minion as $mini)
				{
					$mini['type'] = 'checkbox';
					$result[] = '<li><label>' . formize($mini, FALSE) . '</label></li>';
				}
				$result[] = '</ul>';
			}
			else
			{
				$column['name'] .= '[]';
				$minion = $column['value'];
				foreach ($minion as $mini)
				{
					if (isset($mini->name))
						$column['value'] = $mini->name;
					else
						$column['value'] = $mini;
					$result[] = $formize($column);
				}
				if (empty($result))
				{
					$column['value'] = "";
					$result[] = $formize($column);
				}
				$column['value'] = "";
				$column['onKeyUp'] = "addField(this);";
				$result[] = $formize($column);
			}
		}
		else
		{
			// echo '<pre>'; print_r($column); echo '</pre>';
			if ($type == 'hidden' && isset($column["value"]))
			{
				$result = $formize($column['name'], $column['value']);
			}
			else
				$result = $formize($column);
		}

		if (is_array($result))
		{
			$results = $result;
			$result = "";
			foreach ($results as $resulting)
			{
				$result.= $resulting;
				if ($type != 'checkbox')
					$result .= '<br/>';
			}
		}

		if (isset($text) && !is_array($column['value']))
			$result = $result . ' <span>' . $text . '</span>';
		if (isset($help))
			$result = $result . '<span class="help-block">' . $help . '</span>';
		return $result;
	}


}
function writize($column)
{
	//echo '<pre>'; print_r($column); echo '</pre>';
	if (!is_array($column))
	{
		return $column;
	}

	if (isset($column['display']))
	{

		if (function_exists('display_' . $column['display']))
		{
			$displayfn = 'display_' . $column['display'];
			$column['value'] = $displayfn($column);
		}

		if ($column['display'] == 'image' && $column['value'])
			$column['value'] = '<img src="' . $column['value'] . '" />';
		//if($column['display'] == 'hidden') return '';
	}

	if (isset($column['type']) && $column['type'] == 'language')
	{
		$lang = config_item('fs_languages');
		if (!isset($column['value']) || $column['value'] == "")
			$column['value'] = get_setting('fs_gen_default_lang');
		$column['value'] = $lang[$column['value']];
	}

	if (isset($column['type']) && $column['type'] == 'nation')
	{
		$value = $column['value'];
		$column['value'] = "";
		$nations = config_item('fs_country_names');
		foreach ($value as $key => $item)
		{
			$num = array_search($item, config_item('fs_country_codes'));
			if ($key > 0)
				$column['value'] .= ", ";
			$column['value'] .= $nations[$num];
		}
	}

	return $column['value'];
}


if (!function_exists('lister'))
{

	function lister($rows)
	{
		$echo = '<div class="list">';
		foreach ($rows as $row)
		{
			if (!isset($row['smalltext_r']))
				$row['smalltext_r'] = "";
			if (!isset($row['smalltext']))
				$row['smalltext'] = "";

			$echo .= '<div class="item">
                    <div class="title">' .
					$row['title'] .
					'</div>
                    <div class="smalltext info">' .
					$row['smalltext_r'] .
					'</div>
                    <div class="smalltext">' .
					$row['smalltext'] .
					'</div>';
			$echo .= '</div>';
		}
		return $echo . '</div>';
	}


}


if (!function_exists('ormer'))
{

	function ormer($db)
	{
		$result = array();
		$rows = $db->validation;
		foreach ($rows as $key => $row)
		{
			if ($key == 'id')
			{
				$row['type'] = 'hidden';
			}

			if (isset($row['type']))
			{
				if ($db->$key != "")
					$row['value'] = $db->$key;

				$details = array();
				$details = $row;
				unset($details['label']);
				$details['name'] = $key;

				$result[] = array(
					$row['label'],
					$details
				);
			}
		}

		return $result;
	}


}

if (!function_exists('form_dropdowner'))
{

	function form_dropdowner($column)
	{
		if (isset($column['onKeyUp']))
		{
			$column['onChange'] = 'onChange="' . $column['onKeyUp'] . '"';
			unset($column['onKeyUp']);
		}
		else
			$column['onChange'] = '';
		return form_dropdown($column['name'], $column['values'], $column['value'], $column['onChange']);
	}


}

if (!function_exists('form_nation'))
{

	function form_nation($column)
	{
		$codes = config_item('fs_country_codes');
		$nations = config_item('fs_country_names');

		$nationcodes = array();
		foreach ($codes as $key => $code)
		{
			$nationcodes[$code] = $nations[$key];
		}
		if (isset($column['onKeyUp']))
		{
			$column['onChange'] = 'onChange="' . $column['onKeyUp'] . '"';
			unset($column['onKeyUp']);
		}
		else
			$column['onChange'] = '';
		return form_dropdown($column['name'], $nationcodes, $column['value'], $column['onChange']);
	}


}

if (!function_exists('form_language'))
{

	function form_language($column)
	{
		$lang = config_item('fs_languages');
		if (!isset($column['value']) || $column['value'] == "")
			$column['value'] = get_setting('fs_gen_default_lang');
		return form_dropdown($column['name'], $lang, $column['value']);
	}


}


if (!function_exists('form_themes'))
{

	function form_themes($column)
	{
		$column["value"] = get_setting('fs_theme_dir') ? get_setting('fs_theme_dir') : 'default';
		$dirs = scandir('content/themes', 1);
		$set = array();
		foreach ($dirs as $key => $item)
		{
			if (is_dir('content/themes/' . $item) && $item != '.' && $item != '..' && $item != 'mobile')
			{
				$set[$item] = $item;
			}
		}
		return form_dropdown($column['name'], $set, $column['value']);
	}


}

if (!function_exists('buttoner'))
{

	function buttoner($data = NULL)
	{
		if (!is_array($data))
		{
			$CI = & get_instance();
			if (!isset($CI->buttoner))
				return "";
			$texturl = $CI->buttoner;
		}
		else
			$texturl = array($data);

		$echo = '';
		foreach ($texturl as $item)
		{
			$echo .= '<a ';
			$text = $item['text'];
			unset($item['text']);
			if (isset($item['onclick']) && !isset($item['plug']))
			{
				$echo .= 'onclick="' . ($item['onclick']) . '" ';
				unset($item['onclick']);
			}
			if (isset($item['plug']))
			{
				if (!isset($item['function']))
					$echo .= 'onclick="confirmPlug(\'' . $item['href'] . '\', \'' . addslashes($item['plug']) . '\', this); return false;"';
				else
					$echo .= 'onclick="confirmPlug(\'' . $item['href'] . '\', \'' . addslashes($item['plug']) . '\', this, '.addslashes($item['function']).'); return false;"';
				unset($item['plug']);
			}
			if (isset($item['href']))
			{
				$echo .= 'href="' . ($item['href']) . '" ';
				unset($item['href']);
			}

			$echo .= 'class="btn ';

			if (isset($item['class']))
			{
				$echo .= $item['class'];
				unset($item['class']);
			}

			$echo .= '"';

			foreach ($item as $key => $arg)
				$echo .= $key . '="' . $arg . '" ';
			$echo .= '>';
			$echo .= $text . '</a> ';
		}
		return $echo;
	}


}

if (!function_exists('display_buttoner'))
{

	function display_buttoner($column)
	{
		return buttoner($column);
	}


}

if (!function_exists('form_buttoner'))
{

	function form_buttoner($column)
	{
		return buttoner($column);
	}


}

if (!function_exists('prevnext'))
{

	function prevnext($base_url, $item)
	{
		$echo = '<div class="prevnext">';

		if ($item->paged->has_previous)
		{
			$echo .= '<div class="prev">
					<a class="gbutton fleft" href="' . site_url($base_url . '1') . '">«« ' . _('First') . '</a>
					<a class="gbutton fleft" href="' . site_url($base_url . $item->paged->previous_page) . '">« ' . _('Prev') . '</a>
				</div>';
		}
		if ($item->paged->has_next)
		{
			$echo .= '<div class="next">
					<a class="gbutton fright" href="' . site_url($base_url . $item->paged->total_pages) . '">' . _('Last') . ' »»</a>
					<a class="gbutton fright" href="' . site_url($base_url . $item->paged->next_page) . '">' . _('Next') . ' »</a>
				</div>';
		}
		$echo .= '<div class="clearer"></div></div>';

		return $echo;
	}


}