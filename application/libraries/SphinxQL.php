<?php


/**
 *
 */
class SphinxQL {
	var $conn_id;
	var $db_host;
	var $db_port;


	function __construct()
	{

	}


	public function setHost($host)
	{
		$this->db_host = (string)$host;

	}


	public function setPort($port)
	{
		if (is_natural($port) && $port > 0 && $port < 65536)
		{
			$this->db_port = (int)$port;
		}
	}


	public function SetServer($host = 'localhost', $port = 9306, $persistent = FALSE)
	{
		$this->setHost($host); $this->setPort($port);
		$connect = ($persistent == TRUE) ? 'mysql_pconnect' : 'mysql_connect';

		$this->conn_id = $connect($this->db_host . ':' . $this->db_port, '', '');
		return $this->conn_id;
	}


	public function Query($statement)
	{

		$result = array(
			'matches' => array()
		);


		$search = @mysql_query($statement, $this->conn_id);

		if (stristr($statement, 'SELECT'))
		{
			$meta_query		= @mysql_query("SHOW META", $this->conn_id);
			while (@$row = mysql_fetch_assoc($meta_query))
				$result = array_merge($result, array($row['Variable_name'] => $row['Value']));

			while (@$row = mysql_fetch_assoc($search))
				array_push($result['matches'], $row);

			return $result;
		}
		return $result;
	}


	public function PrepareQuery($SQL_ARRAY)
	{
		if (empty($SQL_ARRAY))
			return FALSE;

		$SQL = array();
		foreach ($SQL_ARRAY as $QUERY => $STATEMENT)
		{
			$_STATEMENT = '';
			switch ($QUERY)
			{
				case 'SELECT':
					if (empty($STATEMENT))
						$STATEMENT  = array('*');

					$_STATEMENT = implode(', ', $STATEMENT);
					break;

				case 'FROM':
					if (empty($STATEMENT))
						return FALSE;

					$_STATEMENT = implode(', ', $STATEMENT);
					break;

				case 'WHERE':
					foreach ($STATEMENT as $TYPE => $CONDITIONS)
					{
						switch ($TYPE)
						{
							case 'MATCH':
								foreach ($CONDITIONS as $FIELD => $VALUE)
								{
									if (!isset($_STATEMENT_MATCH))
										$_STATEMENT_MATCH  = "{$FIELD} {$VALUE}";
									else
										$_STATEMENT_MATCH .= " {$FIELD} {$VALUE}";
								}

								if (!empty($_STATEMENT_MATCH))
									$_STATEMENT_MATCH = "MATCH('" . trim($_STATEMENT_MATCH) . "')";
								break;

							case 'CONDITION':
								$_STATEMENT_CONDITIONS = array();
								foreach ($CONDITIONS as $FIELD => $VALUE)
								{
									/**
									 * MAKE SURE THERE IS NO OPERATOR SPECIFIED. IF SO,
									 * CHANGE = TO SPECIFIED OPERATOR.
									 */
									if (strpos($FIELD, ' ') === FALSE)
									{
										array_push($_STATEMENT_CONDITIONS, "{$FIELD} = {$VALUE}");
									}
									else
									{
										$FIELD_OP = explode(' ', $FIELD);
										if ($FIELD_OP[1])
											array_push($_STATEMENT_CONDITIONS, "{$FIELD_OP[0]} {$FIELD_OP[1]} {$VALUE}");
										else
											array_push($_STATEMENT_CONDITIONS, "{$FIELD} = {$VALUE}");
									}
								}

								if (!empty($_STATEMENT_CONDITIONS))
									$_STATEMENT_CONDITIONS = implode(' AND ', $_STATEMENT_CONDITIONS);
								break;
						}

						if (isset($_STATEMENT_MATCH))
							$_STATEMENT = $_STATEMENT_MATCH;

						if (isset($_STATEMENT_CONDITIONS) && !empty($_STATEMENT_CONDITIONS))
						{
							if (isset($_STATEMENT_MATCH))
								$_STATEMENT .= " AND {$_STATEMENT_CONDITIONS}";
							else
								$_STATEMENT = "{$_STATEMENT_CONDITIONS}";
						}
					}
					break;

				case 'GROUP BY':
					$_STATEMENT = implode(', ', $STATEMENT);
					break;

				case 'ORDER BY':
					foreach ($STATEMENT as $FIELD => $VALUE)
					{
						if (empty($_STATEMENT))
							$_STATEMENT  = "{$FIELD} {$VALUE}";
						else
							$_STATEMENT .= ", {$FIELD} {$VALUE}";
					}
					break;

				case 'LIMIT':
					$_STATEMENT = implode(', ', $STATEMENT);
					break;

				case 'OPTION':
					foreach ($STATEMENT as $FIELD => $VALUE)
					{
						if (empty($_STATEMENT))
							$_STATEMENT  = "{$FIELD} = {$VALUE}";
						else
							$_STATEMENT .= ", {$FIELD} = {$VALUE}";
					}
					break;
			}

			if (!empty($STATEMENT) && !empty($_STATEMENT))
				array_push($SQL, "{$QUERY} {$_STATEMENT}");
		}

		if (empty($SQL))
			return FALSE;
		else
			return implode(' ', $SQL);
	}


	function EscapeString ($string, $decode = FALSE)
	{
		$from	= array ('\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=');
		$to		= array ('\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=');
		$string	= str_replace ($from, $to, $string);
		return (($decode) ? urldecode($string) : $string);
	}


	function HalfEscapeString($string, $decode = FALSE)
	{
		$from = array ('\\', '(',')','!','@','~','&', '/', '^', '$', '=');
		$to   = array ('\\\\', '\(','\)','\!','\@','\~', '\&', '\/', '\^', '\$', '\=');
		$string = str_replace ( $from, $to, $string );
		$string = preg_replace("'\"([^\s]+)-([^\s]*)\"'", "\\1\-\\2", $string);
		$string = preg_replace("'([^\s]+)-([^\s]*)'", "\"\\1\-\\2\"", $string);
		return (($decode) ? urldecode($string) : $string);
	}
}

?>
