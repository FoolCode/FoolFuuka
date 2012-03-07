<?php

if (! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * My_DB_active_rec
 */
class My_DB_active_record extends CI_DB_active_record
{
	var $ar_index				= array();
	var $ar_where_match			= array();

	var $ar_sphinx_match		= array();
	var $ar_sphinx_where		= array();
	var $ar_sphinx_option		= array();

	var $ar_cache_index			= array();
	var $ar_cache_sphinx_match	= array();
	var $ar_cache_sphinx_where	= array();
	var $ar_cache_sphinx_option = array();

	var $ar_group_from			= TRUE;


	/**
	 * FROM
	 *
	 * Generates the FROM portion of the query.
	 *
	 * ----------------------------------------------------------------------------------
	 * This function has been modified to add support for deciding to enable or disable
	 * the prefixing of the tables specified.
	 * ----------------------------------------------------------------------------------
	 *
	 * @param   mixed   $from
	 * @param   bool    $db_prefix
	 * @param   bool    $group_from
	 * @return  object
	 */
	public function from($from, $db_prefix = TRUE, $group_from = TRUE)
	{
		$this->ar_group_from = $group_from;

		foreach ((array)$from as $val)
		{
			if (strpos($val, ',') !== FALSE)
			{
				foreach (explode(',', $val) as $v)
				{
					$v = trim($v);
					$this->_track_aliases($v);

					$this->ar_from[] = $this->_protect_identifiers($v, TRUE, NULL, FALSE, $db_prefix);

					if ($this->ar_caching === TRUE)
					{
						$this->ar_cache_from[] = $this->_protect_identifiers($v, TRUE, NULL, FALSE, $db_prefix);
						$this->ar_cache_exists[] = 'from';
					}
				}
			}
			else
			{
				$val = trim($val);

				// Extract any aliases that might exist.  We use this information
				// in the _protect_identifiers to know whether to add a table prefix
				$this->_track_aliases($val);

				$this->ar_from[] = $this->_protect_identifiers($val, TRUE, NULL, FALSE, $db_prefix);

				if ($this->ar_caching === TRUE)
				{
					$this->ar_cache_from[] = $this->_protect_identifiers($val, TRUE, NULL, FALSE, $db_prefix);
					$this->ar_cache_exists[] = 'from';
				}
			}
		}

		return $this;
	}


	/**
	 * From Tables With Index
	 *
	 * This function implicitly groups FROM tables so there is no confusion
	 * about operator precedence in harmony with SQL standards.
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _from_tables_with_index($tables)
	{
		if ( ! is_array($tables))
		{
			$tables = array($tables);
		}

		return '('.implode(', ', $tables).' USE INDEX('.implode("", array_unique($this->ar_index)).'))';
	}


	/**
	 * USE Index
	 *
	 * Generates the USE INDEX portion of the query.
	 *
	 * @param   mixed
	 * @return  object
	 */
	public function use_index($key)
	{
		$this->_index($key);
	}


	/**
	 * @param   string  $table
	 * @param   null    $limit
	 * @param   null    $offset
	 * @return  string
	 */
	public function statement($table = '', $limit = NULL, $offset = NULL)
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}

		if ( ! is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->_compile_select();
		$this->_reset_select();
		return $sql;
	}


	/**
	 * Compile the SELECT statement
	 *
	 * Generates a query string based on which functions were used.
	 * Should not be called directly.  The get() function calls it.
	 *
	 * @param   bool    $select_override
	 * @return  string
	 */
	public function _compile_select($select_override = FALSE)
	{
		// Combine any cached components with the current statements
		$this->_merge_cache();

		// ----------------------------------------------------------------

		// Write the "select" portion of the query

		if ($select_override !== FALSE)
		{
			$sql = $select_override;
		}
		else
		{
			$sql = ( ! $this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

			if (count($this->ar_select) == 0)
			{
				$sql .= '*';
			}
			else
			{
				// Cycle through the "select" portion of the query and prep each column name.
				// The reason we protect identifiers here rather then in the select() function
				// is because until the user calls the from() function we don't know if there are aliases
				foreach ($this->ar_select as $key => $val)
				{
					$no_escape = isset($this->ar_no_escape[$key]) ? $this->ar_no_escape[$key] : NULL;
					$this->ar_select[$key] = $this->_protect_identifiers($val, FALSE, $no_escape);
				}

				$sql .= implode(', ', $this->ar_select);
			}
		}

		// ----------------------------------------------------------------

		// Write the "FROM" portion of the query

		if (count($this->ar_from) > 0)
		{
			$sql .= "\nFROM ";

			if ($this->ar_group_from)
			{
				if (count($this->ar_index) > 0)
				{
					$sql .= $this->_from_tables_with_index($this->ar_from);
				}
				else
				{
					$sql .= $this->_from_tables($this->ar_from);
				}
			}
			else
			{
				$sql .= implode(', ', $this->ar_from);
			}
		}

		// ----------------------------------------------------------------

		// Write the "JOIN" portion of the query

		if (count($this->ar_join) > 0)
		{
			$sql .= "\n";

			$sql .= implode("\n", $this->ar_join);
		}

		// ----------------------------------------------------------------

		// Write the "WHERE MATCH" portion of the query

		if (count($this->ar_where_match) > 0 OR count($this->ar_sphinx_match) > 0)
		{
			$sql .= "\nWHERE ";

			$sql .= 'MATCH(\''.implode("", $this->ar_sphinx_match).'\')';
		}

		// ----------------------------------------------------------------

		// Write the "WHERE" portion of the query

		if ((count($this->ar_where) > 0 OR count($this->ar_like) > 0)
			AND (count($this->ar_where_match) == 0 AND count($this->ar_sphinx_match) == 0))
		{
			$sql .= "\nWHERE ";
		}
		else
		{
			$sql .= "\n";
		}

		$sql .= implode("\n", $this->ar_where);

		// ----------------------------------------------------------------

		// Write the "LIKE" portion of the query

		if (count($this->ar_like) > 0)
		{
			if (count($this->ar_where) > 0)
			{
				$sql .= "\nAND ";
			}

			$sql .= implode("\n", $this->ar_like);
		}

		// ----------------------------------------------------------------

		// Write the "GROUP BY" portion of the query

		if (count($this->ar_groupby) > 0)
		{
			$sql .= "\nGROUP BY ";

			$sql .= implode(', ', $this->ar_groupby);
		}

		// ----------------------------------------------------------------

		// Write the "HAVING" portion of the query

		if (count($this->ar_having) > 0)
		{
			$sql .= "\nHAVING ";
			$sql .= implode("\n", $this->ar_having);
		}

		// ----------------------------------------------------------------

		// Write the "ORDER BY" portion of the query

		if (count($this->ar_orderby) > 0)
		{
			$sql .= "\nORDER BY ";
			$sql .= implode(', ', $this->ar_orderby);

			if ($this->ar_order !== FALSE)
			{
				$sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
			}
		}

		// ----------------------------------------------------------------

		// Write the "LIMIT" portion of the query

		if (is_numeric($this->ar_limit))
		{
			$sql .= "\n";
			$sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
		}

		// ----------------------------------------------------------------

		// Write the "OPTION" portion of the query. This is specific to Sphinx.

		if (count($this->ar_sphinx_option) > 0)
		{
			$sql .= "\nOPTION ";
			$sql .= implode(', ', $this->ar_sphinx_option);
		}

		return $sql;
	}


	/**
	 * Index
	 *
	 * Called by use_index().
	 *
	 * @param   mixed  $key
	 * @param   string $type
	 * @return  object
	 */
	public function _index($key, $type = ', ')
	{
		if ( ! is_array($key))
		{
			$key = array($key);
		}

		foreach ($key as $k)
		{
			$prefix = (count($this->ar_index) == 0) ? '' : $type;

			$this->ar_index[] = $prefix.$k;
			if ($this->ar_caching === TRUE)
			{
				$this->cache_where[] = $prefix.$k;
				$this->cache_exists[] = 'index';
			}
		}

		return $this;
	}


	/**
	 * Where
	 *
	 * Called by where() or or_where().
	 *
	 * ----------------------------------------------------------------------------------
	 * This function has been modified to add support for SphinxQL by prefixing $type to
	 * the beginning of the statement when a SPHINX MATCH or MATCH AGAINST is present.
	 * ----------------------------------------------------------------------------------
	 *
     * @param           $key
     * @param   null    $value
     * @param   string  $type
     * @param   null    $escape
     * @return  object
	 */
	public function _where($key, $value = NULL, $type = ' AND ', $escape = NULL)
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		// If the escape value was not set will will base it on the global setting
		if ( ! is_bool($escape))
		{
			$escape = $this->_protect_identifiers;
		}

		foreach ($key as $k => $v)
		{
			$prefix = (count($this->ar_where) == 0 AND count($this->ar_cache_where) == 0
				AND count($this->ar_where_match) == 0 AND count($this->ar_sphinx_match) == 0) ? '' : $type;

			if (is_null($v) && ! $this->_has_operator($k))
			{
				// value appears not to have been set, assign the test to IS NULL
				$k .= ' IS NULL';
			}

			if ( ! is_null($v))
			{
				if ($escape === TRUE)
				{
					$k = $this->_protect_identifiers($k, FALSE, $escape);

					$v = ' '.$this->escape($v);
				}

				if ( ! $this->_has_operator($k))
				{
					$k .= ' = ';
				}
			}
			else
			{
				$k = $this->_protect_identifiers($k, FALSE, $escape);
			}

			$this->ar_where[] = $prefix.$k.$v;

			if ($this->ar_caching === TRUE)
			{
				$this->ar_cache_where[] = $prefix.$k.$v;
				$this->ar_cache_exists[] = 'where';
			}

		}

		return $this;
	}


	/**
	 * Protect Identifiers
	 *
	 * This function is used extensively by the Active Record class, and by
	 * a couple functions in this class.
	 * It takes a column or table name (optionally with an alias) and inserts
	 * the table prefix onto it.  Some logic is necessary in order to deal with
	 * column names that include the path.  Consider a query like this:
	 *
	 * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
	 *
	 * Or a query with aliasing:
	 *
	 * SELECT m.member_id, m.member_name FROM members AS m
	 *
	 * Since the column name can include up to four segments (host, DB, table, column)
	 * or also have an alias prefix, we need to do a bit of work to figure this out and
	 * insert the table prefix (if it exists) in the proper position, and escape only
	 * the correct identifiers.
	 *
	 * ----------------------------------------------------------------------------------
	 * This function has been modified to add support for disabling or enabling the
	 * prefixing of the table. It should be used for custom tables that are not prefixed.
	 * ----------------------------------------------------------------------------------
	 *
	 * @access  private
	 * @param   string   $item
	 * @param   bool     $prefix_single
	 * @param   mixed    $protect_identifiers
	 * @param   bool     $field_exists
	 * @param   bool     $db_prefix
	 * @return  string
	 */
	function _protect_identifiers($item, $prefix_single = FALSE, $protect_identifiers = NULL, $field_exists = TRUE, $db_prefix = TRUE)
	{
		if ( ! is_bool($protect_identifiers))
		{
			$protect_identifiers = $this->_protect_identifiers;
		}

		if (is_array($item))
		{
			$escaped_array = array();

			foreach ($item as $k => $v)
			{
				$escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v);
			}

			return $escaped_array;
		}

		// Convert tabs or multiple spaces into single spaces
		$item = preg_replace('/[\t ]+/', ' ', $item);

		// If the item has an alias declaration we remove it and set it aside.
		// Basically we remove everything to the right of the first space
		$alias = '';
		if (strpos($item, ' ') !== FALSE)
		{
			$alias = strstr($item, " ");
			$item = substr($item, 0, - strlen($alias));
		}

		// This is basically a bug fix for queries that use MAX, MIN, etc.
		// If a parenthesis is found we know that we do not need to
		// escape the data or add a prefix.  There's probably a more graceful
		// way to deal with this, but I'm not thinking of it -- Rick
		if (strpos($item, '(') !== FALSE)
		{
			return $item.$alias;
		}

		// Break the string apart if it contains periods, then insert the table prefix
		// in the correct location, assuming the period doesn't indicate that we're dealing
		// with an alias. While we're at it, we will escape the components
		if (strpos($item, '.') !== FALSE)
		{
			$parts	= explode('.', $item);

			// Does the first segment of the exploded item match
			// one of the aliases previously identified?  If so,
			// we have nothing more to do other than escape the item
			if (in_array($parts[0], $this->ar_aliased_tables))
			{
				if ($protect_identifiers === TRUE)
				{
					foreach ($parts as $key => $val)
					{
						if ( ! in_array($val, $this->_reserved_identifiers))
						{
							$parts[$key] = $this->_escape_identifiers($val);
						}
					}

					$item = implode('.', $parts);
				}
				return $item.$alias;
			}

			// Is there a table prefix defined in the config file?  If not, no need to do anything
			if ($this->dbprefix != '' && $db_prefix)
			{
				// We now add the table prefix based on some logic.
				// Do we have 4 segments (hostname.database.table.column)?
				// If so, we add the table prefix to the column name in the 3rd segment.
				if (isset($parts[3]))
				{
					$i = 2;
				}
				// Do we have 3 segments (database.table.column)?
				// If so, we add the table prefix to the column name in 2nd position
				elseif (isset($parts[2]))
				{
					$i = 1;
				}
				// Do we have 2 segments (table.column)?
				// If so, we add the table prefix to the column name in 1st segment
				else
				{
					$i = 0;
				}

				// This flag is set when the supplied $item does not contain a field name.
				// This can happen when this function is being called from a JOIN.
				if ($field_exists == FALSE)
				{
					$i++;
				}

				// Verify table prefix and replace if necessary
				if ($this->swap_pre != '' && strncmp($parts[$i], $this->swap_pre, strlen($this->swap_pre)) === 0)
				{
					$parts[$i] = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $parts[$i]);
				}

				// We only add the table prefix if it does not already exist
				if (substr($parts[$i], 0, strlen($this->dbprefix)) != $this->dbprefix && $db_prefix)
				{
					$parts[$i] = $this->dbprefix.$parts[$i];
				}

				// Put the parts back together
				$item = implode('.', $parts);
			}

			if ($protect_identifiers === TRUE)
			{
				$item = $this->_escape_identifiers($item);
			}

			return $item.$alias;
		}

		// Is there a table prefix?  If not, no need to insert it
		if ($this->dbprefix != '' && $db_prefix)
		{
			// Verify table prefix and replace if necessary
			if ($this->swap_pre != '' && strncmp($item, $this->swap_pre, strlen($this->swap_pre)) === 0)
			{
				$item = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $item);
			}

			// Do we prefix an item with no segments?
			if ($prefix_single == TRUE AND substr($item, 0, strlen($this->dbprefix)) != $this->dbprefix)
			{
				$item = $this->dbprefix.$item;
			}
		}

		if ($protect_identifiers === TRUE AND ! in_array($item, $this->_reserved_identifiers))
		{
			$item = $this->_escape_identifiers($item);
		}

		return $item.$alias;
	}


	/**
	 * Resets the active record values.  Called by the get() function

	 * ----------------------------------------------------------------------------------
	 * This function has been modified to reset additional arrays created.
	 * ----------------------------------------------------------------------------------
	 *
	 * @return	void
	 */
	public function _reset_select()
	{
		$ar_reset_items = array(
			'ar_select'				=> array(),
			'ar_from'				=> array(),
			'ar_join'				=> array(),
			'ar_where'				=> array(),
			'ar_like'				=> array(),
			'ar_groupby'			=> array(),
			'ar_having'				=> array(),
			'ar_orderby'			=> array(),
			'ar_wherein'			=> array(),
			'ar_aliased_tables'		=> array(),
			'ar_no_escape'			=> array(),
			'ar_distinct'			=> FALSE,
			'ar_limit'				=> FALSE,
			'ar_offset'				=> FALSE,
			'ar_order'				=> FALSE,

			'ar_index'				=> array(),
			'ar_where_match'		=> array(),
			'ar_sphinx_match'		=> array(),
			'ar_sphinx_where'		=> array(),
			'ar_sphinx_option'		=> array(),
		);

		$this->_reset_run($ar_reset_items);
	}


	/**
	 * Flush Cache
	 *
	 * Empties the AR cache
	 *
	 * ----------------------------------------------------------------------------------
	 * This function has been modified to reset additional arrays created.
	 * ----------------------------------------------------------------------------------
	 *
	 * @access	public
	 * @return	void
	 */
	public function flush_cache()
	{
		$this->_reset_run(array(
			'ar_cache_select'		=> array(),
			'ar_cache_from'			=> array(),
			'ar_cache_join'			=> array(),
			'ar_cache_where'		=> array(),
			'ar_cache_like'			=> array(),
			'ar_cache_groupby'		=> array(),
			'ar_cache_having'		=> array(),
			'ar_cache_orderby'		=> array(),
			'ar_cache_set'			=> array(),
			'ar_cache_exists'		=> array(),
			'ar_cache_no_escape'	=> array(),

			'ar_cache_index'		=> array(),
			'ar_cache_sphinx_match'	=> array(),
			'ar_cache_sphinx_where'	=> array(),
			'ar_cache_sphinx_option'=> array(),
		));
	}


	/**
	 * @param   mixed   $option
	 * @param   null    $value
	 * @return  object
	 */
	public function sphinx_option($option, $value = NULL)
	{
		if ( ! is_array($option))
		{
			$option = array($option => $value);
		}

		foreach ($option as $k => $v)
		{
			$this->ar_sphinx_option[] = "$k = $v";
		}

		return $this;
	}


	/**
	 * @param   mixed   $field
	 * @param   string  $match
	 * @param   null    $escape
	 * @param   bool    $decode
	 * @return  object
	 */
	public function sphinx_match($field, $match = '', $escape = NULL, $decode = FALSE)
	{
		if ( ! is_array($field))
		{
			$field = array($field => $match);
		}

		foreach ($field as $k => $v)
		{
			$prefix = (count($this->ar_sphinx_match) == 0) ? '' : ' ';

			$k = "@".$k;

			if ($escape = 'full')
			{
				$v = $this->sphinx_escape_str($v, $decode);
			}
			else if ($escape = 'half')
			{
				$v = $this->sphinx_halfescape_str($v, $decode);
			}
			else
			{
				$v = $v;
			}

			$this->ar_sphinx_match[] = $prefix."$k $v";
		}

		return $this;
	}


	/**
	 * @param   mixed  $str
	 * @param   bool   $decode
	 * @return  mixed
	 */
	public function sphinx_escape_str($str, $decode = FALSE)
	{
		if (is_array($str))
		{
			foreach ($str as $key => $val)
			{
				$str[$key] = $this->sphinx_escape_str($val, $decode);
			}

			return $str;
		}

		$str = ($decode) ? rawurldecode($str) : $str;

		$str = str_replace(
			array('\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '='),
			array('\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\='),
			$str
		);

		return $str;
	}


	/**
	 * @param   mixed  $str
	 * @param   bool   $decode
	 * @return  mixed
	 */
	public function sphinx_halfescape_str($str, $decode = FALSE)
	{
		if (is_array($str))
		{
			foreach ($str as $key => $val)
			{
				$str[$key] = $this->sphinx_halfescape_str($val, $decode);
			}

			return $str;
		}

		$str = ($decode) ? rawurldecode($str) : $str;

		$str = str_replace(
			array('\\', '(',')','!','@','~','&', '/', '^', '$', '='),
			array('\\\\', '\(','\)','\!','\@','\~', '\&', '\/', '\^', '\$', '\='),
			$str
		);

		$str = preg_replace("'\"([^\s]+)-([^\s]*)\"'", "\\1\-\\2", $str);
		$str = preg_replace("'([^\s]+)-([^\s]*)'", "\"\\1\-\\2\"", $str);

		return $str;
	}


}
