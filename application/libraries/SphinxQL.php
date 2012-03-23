<?php


/**
 *
 */
class SphinxQL {
	var $db_host;
	var $db_port;
	var $conn_id;

	public function set_host($host)
	{
		$this->db_host = (string)$host;

	}


	public function set_port($port)
	{
		if (is_natural($port) && $port > 0 && $port < 65536)
		{
			$this->db_port = (int)$port;
		}
	}


	public function set_server($host = 'localhost', $port = 9306, $persistent = FALSE)
	{
		$this->set_host($host);
		$this->set_port($port);

		$connect = ($persistent == TRUE) ? 'mysql_pconnect' : 'mysql_connect';

		$this->conn_id = @$connect($this->db_host . ':' . $this->db_port, '', '');
		return $this->conn_id;
	}


	public function query($statement)
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
