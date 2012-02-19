<?php

class SphinxQL {
	var $conn_id;
	var $host;
	var $port;


	public function setHost($host)
	{
		$this->host = (string)$host;
	}


	public function setPort($port)
	{
		if (is_natural($port) && $port > 0 && $port < 65536)
		{
			$this->port = (int)$port;
		}
	}


	public function SetServer($host = 'localhost', $port = 9306, $persistent = FALSE)
	{
		$this->setHost($host); $this->setPort($port);
		$connect = ($persistent == TRUE) ? 'mysql_pconnect' : 'mysql_connect';

		$this->conn_id = $connect($this->host . ':' . $this->port, '', '');
		return $this->conn_id;
	}


	public function Query($sql)
	{
		$results['matches'] = array();
		$result = @mysql_query($sql, $this->conn_id);
		if (stristr($sql, 'select'))
		{
			while (@$row = mysql_fetch_assoc($result))
			{
				array_push($results['matches'], $row);
			}
			$results['total_found'] = count($results['matches']);
			return $results;
		}
		return $result;
	}


	function EscapeString ($string)
	{
		$from = array ('\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=');
		$to   = array ('\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=');
		return str_replace ( $from, $to, $string );
	}


	function HalfEscapeString($string)
	{
		$from = array ('\\', '(',')','!','@','~','&', '/', '^', '$', '=');
		$to   = array ('\\\\', '\(','\)','\!','\@','\~', '\&', '\/', '\^', '\$', '\=');
		$string = str_replace ( $from, $to, $string );
		$string = preg_replace("'\"([^\s]+)-([^\s]*)\"'", "\\1\-\\2", $string);
		return preg_replace("'([^\s]+)-([^\s]*)'", "\"\\1\-\\2\"", $string);
	}
}

?>
