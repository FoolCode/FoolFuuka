<?php


/**
 *
 */
class SphinxQL
{

	var $db_host;
	var $db_port;
	var $conn_id;


	public function set_host($host)
	{
		$this->db_host = (string) $host;
	}


	public function set_port($port)
	{
		if (is_natural($port) && $port > 0 && $port < 65536)
		{
			$this->db_port = (int) $port;
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
			$meta_query = @mysql_query("SHOW META", $this->conn_id);
			while (@$row = mysql_fetch_assoc($meta_query))
				$result = array_merge($result, array($row['Variable_name'] => $row['Value']));

			//check to see if the query returns a resource or bool
			if (is_resource($search))
			{
				while (@$row = mysql_fetch_assoc($search))
					array_push($result['matches'], $row);
			}

			return $result;
		}
		return $result;
	}


	function EscapeString($string, $decode = FALSE)
	{
		$from = array('\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=');
		$to = array('\\\\', '\(', '\)', '\|', '\-', '\!', '\@', '\~', '\"', '\&', '\/', '\^', '\$', '\=');
		$string = str_replace($from, $to, $string);
		return (($decode) ? urldecode($string) : $string);
	}


	function HalfEscapeString($string, $decode = FALSE)
	{
		$from = array('\\', '(', ')', '!', '@', '~', '&', '/', '^', '$', '=');
		$to = array('\\\\', '\(', '\)', '\!', '\@', '\~', '\&', '\/', '\^', '\$', '\=');
		$string = str_replace($from, $to, $string);
		$string = preg_replace("'\"([^\s]+)-([^\s]*)\"'", "\\1\-\\2", $string);
		$string = preg_replace("'([^\s]+)-([^\s]*)'", "\"\\1\-\\2\"", $string);
		return (($decode) ? urldecode($string) : $string);
	}


	function generate_sphinx_config($boards)
	{
		$CI = & get_instance();

		$config = '
########################################################
## Sphinx Configuration for FoOlFuuka
########################################################

# Remember to set the password manually
# Add also the port if you\'re not using the default MySQL 3306
		';

		// obtain only one board for initial connection
		$test_board = end($boards);

		$config .= '
########################################################
## data source definition
########################################################

source main
{
	# data source type. mandatory, no default value
	# known types are mysql, pgsql, mssql, xmlpipe, xmlpipe2, odbc
	type = mysql

	# SQL source information
	sql_host = ' . $CI->db->hostname . '
	sql_user = ' . $CI->db->username . '
	sql_pass =
	sql_db = ' . $CI->db->database . '
	sql_port = 3306

	mysql_connect_flags = ' . get_setting('fu_sphinx_connection_flags', 0) . '

	sql_query_pre = SET NAMES utf8
	sql_range_step = 10000
	sql_query = \
		SELECT doc_id, ' . $test_board->id . ' AS board, num, subnum, name, trip, email, media_filename, media_id AS mid, media_hash, \
		thread_num AS tnum, CAST(capcode AS UNSIGNED) AS cap, (media_filename != \'\' AND media_filename IS NOT NULL) AS has_image, \
		(subnum != 0) AS is_internal, spoiler AS is_spoiler, deleted AS is_deleted, sticky AS is_sticky, op AS is_op, \
		poster_ip AS pip, timestamp, title, comment \
		FROM ' . $test_board->shortname . ' LIMIT 1

	sql_attr_uint = num
	sql_attr_uint = subnum
	sql_attr_uint = tnum
	sql_attr_uint = cap
	sql_attr_uint = board
	sql_attr_uint = mid
	sql_attr_uint = pip
	sql_attr_bool = has_image
	sql_attr_bool = is_internal
	sql_attr_bool = is_spoiler
	sql_attr_bool = is_deleted
	sql_attr_bool = is_sticky
	sql_attr_bool = is_op
	sql_attr_timestamp = timestamp

	sql_query_info =
	sql_query_post_index =
}
		';

		foreach ($boards as $key => $board)
		{
			if ($board->sphinx)
			{
				$config .= $this->_generate_sphinx_definition_source($board);
			}
		}

		$config .= '
########################################################
## index definition
########################################################

index main
{
	source = main
	path = ' . get_setting('fu_sphinx_dir') . '/data/main
	docinfo = extern
	mlock = 0
	morphology = none
	min_word_len = ' . get_setting('fu_sphinx_min_word_len', 3) . '
	charset_type = utf-8

	charset_table=0..9, A..Z->a..z, _, a..z, _,   \
	U+410..U+42F->U+430..U+44F, U+430..U+44F, \
	U+C0->a, U+C1->a, U+C2->a, U+C3->a, U+C7->c, U+C8->e, U+C9->e, U+CA->e, U+CB->e, U+CC->i, U+CD->i, \
	U+CE->i, U+CF->i, U+D2->o, U+D3->o, U+D4->o, U+D5->o, U+D9->u, U+DA->u, U+DB->u, U+E0->a, U+E1->a, \
	U+E2->a, U+E3->a, U+E7->c, U+E8->e, U+E9->e, U+EA->e, U+EB->e, U+EC->i, U+ED->i, U+EE->i, U+EF->i, \
	U+F2->o, U+F3->o, U+F4->o, U+F5->o, U+F9->u, U+FA->u, U+FB->u, U+FF->y, U+102->a, U+103->a, U+15E->s, \
	U+15F->s, U+162->t, U+163->t, U+178->y,   \
	U+FF10..U+FF19->0..9, U+FF21..U+FF3A->a..z, \
	U+FF41..U+FF5A->a..z, U+4E00..U+9FCF, U+3400..U+4DBF, \
	U+20000..U+2A6DF, U+3040..U+309F, U+30A0..U+30FF, U+3000..U+303F, U+3042->U+3041, \
	U+3044->U+3043, U+3046->U+3045, U+3048->U+3047, U+304A->U+3049, \
	U+304C->U+304B, U+304E->U+304D, U+3050->U+304F, U+3052->U+3051, \
	U+3054->U+3053, U+3056->U+3055, U+3058->U+3057, U+305A->U+3059, \
	U+305C->U+305B, U+305E->U+305D, U+3060->U+305F, U+3062->U+3061, \
	U+3064->U+3063, U+3065->U+3063, U+3067->U+3066, U+3069->U+3068, \
	U+3070->U+306F, U+3071->U+306F, U+3073->U+3072, U+3074->U+3072, \
	U+3076->U+3075, U+3077->U+3075, U+3079->U+3078, U+307A->U+3078, \
	U+307C->U+307B, U+307D->U+307B, U+3084->U+3083, U+3086->U+3085, \
	U+3088->U+3087, U+308F->U+308E, U+3094->U+3046, U+3095->U+304B, \
	U+3096->U+3051, U+30A2->U+30A1, U+30A4->U+30A3, U+30A6->U+30A5, \
	U+30A8->U+30A7, U+30AA->U+30A9, U+30AC->U+30AB, U+30AE->U+30AD, \
	U+30B0->U+30AF, U+30B2->U+30B1, U+30B4->U+30B3, U+30B6->U+30B5, \
	U+30B8->U+30B7, U+30BA->U+30B9, U+30BC->U+30BB, U+30BE->U+30BD, \
	U+30C0->U+30BF, U+30C2->U+30C1, U+30C5->U+30C4, U+30C7->U+30C6, \
	U+30C9->U+30C8, U+30D0->U+30CF, U+30D1->U+30CF, U+30D3->U+30D2, \
	U+30D4->U+30D2, U+30D6->U+30D5, U+30D7->U+30D5, U+30D9->U+30D8, \
	U+30DA->U+30D8, U+30DC->U+30DB, U+30DD->U+30DB, U+30E4->U+30E3, \
	U+30E6->U+30E5, U+30E8->U+30E7, U+30EF->U+30EE, U+30F4->U+30A6, \
	U+30AB->U+30F5, U+30B1->U+30F6, U+30F7->U+30EF, U+30F8->U+30F0, \
	U+30F9->U+30F1, U+30FA->U+30F2, U+30AF->U+31F0, U+30B7->U+31F1, \
	U+30B9->U+31F2, U+30C8->U+31F3, U+30CC->U+31F4, U+30CF->U+31F5, \
	U+30D2->U+31F6, U+30D5->U+31F7, U+30D8->U+31F8, U+30DB->U+31F9, \
	U+30E0->U+31FA, U+30E9->U+31FB, U+30EA->U+31FC, U+30EB->U+31FD, \
	U+30EC->U+31FE, U+30ED->U+31FF, U+FF66->U+30F2, U+FF67->U+30A1, \
	U+FF68->U+30A3, U+FF69->U+30A5, U+FF6A->U+30A7, U+FF6B->U+30A9, \
	U+FF6C->U+30E3, U+FF6D->U+30E5, U+FF6E->U+30E7, U+FF6F->U+30C3, \
	U+FF71->U+30A1, U+FF72->U+30A3, U+FF73->U+30A5, U+FF74->U+30A7, \
	U+FF75->U+30A9, U+FF76->U+30AB, U+FF77->U+30AD, U+FF78->U+30AF, \
	U+FF79->U+30B1, U+FF7A->U+30B3, U+FF7B->U+30B5, U+FF7C->U+30B7, \
	U+FF7D->U+30B9, U+FF7E->U+30BB, U+FF7F->U+30BD, U+FF80->U+30BF, \
	U+FF81->U+30C1, U+FF82->U+30C3, U+FF83->U+30C6, U+FF84->U+30C8, \
	U+FF85->U+30CA, U+FF86->U+30CB, U+FF87->U+30CC, U+FF88->U+30CD, \
	U+FF89->U+30CE, U+FF8A->U+30CF, U+FF8B->U+30D2, U+FF8C->U+30D5, \
	U+FF8D->U+30D8, U+FF8E->U+30DB, U+FF8F->U+30DE, U+FF90->U+30DF, \
	U+FF91->U+30E0, U+FF92->U+30E1, U+FF93->U+30E2, U+FF94->U+30E3, \
	U+FF95->U+30E5, U+FF96->U+30E7, U+FF97->U+30E9, U+FF98->U+30EA, \
	U+FF99->U+30EB, U+FF9A->U+30EC, U+FF9B->U+30ED, U+FF9C->U+30EF, \
	U+FF9D->U+30F3

	min_prefix_len = 3
	prefix_fields = comment, title
	enable_star = 1
	html_strip = 0
}
		';

		foreach ($boards as $key => $board)
		{
			if ($board->sphinx)
			{
				$config .= $this->_generate_sphinx_definition_index($board,
					((get_setting('fu_sphinx_dir')) ? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/data');
			}
		}

		$config .= '
########################################################
## indexer settings
########################################################

indexer
{
	mem_limit = ' . get_setting('fu_sphinx_mem_limit', '2047M') . '
	max_xmlpipe2_field = 4M
	write_buffer = 5M
	max_file_field_buffer = 32M
}
';

		$config .= '
########################################################
## searchd settings
########################################################

searchd
{
	listen = ' . get_setting('fu_sphinx_listen', '127.0.0.1:9312') . ':sphinx
	listen = ' . get_setting('fu_sphinx_listen_mysql', '127.0.0.1:9312') . ':mysql41
	log = ' . get_setting('fu_sphinx_dir', '/usr/local/sphinx/var') . '/log/searchd.log
	query_log = ' . get_setting('fu_sphinx_dir', '/usr/local/sphinx/var') . '/log/query.log
	read_timeout = 5
	client_timeout = 300
	max_children = ' . get_setting('fu_sphinx_max_children', 10) . '
	pid_file = ' . get_setting('fu_sphinx_dir', '/usr/local/sphinx/var') . '/searchd.pid
	max_matches = ' . get_setting('fu_sphinx_max_matches', 5000) . '
	seamless_rotate = 1
	preopen_indexes = 1
	unlink_old = 1
	mva_updates_pool = 1M
	max_packet_size = 8M
	max_filters = 256
	max_filter_values = 4096
	max_batch_queries = 32
	workers = threads
	binlog_path = ' . get_setting('fu_sphinx_dir', '/usr/local/sphinx/var') . '/data
	collation_server = utf8_general_ci
	collation_libc_locale = en_US.UTF-8
	compat_sphinxsql_magics = 0
}
		';

		$config .= '
# --eof--
		';

		return $config;
	}


	function _generate_sphinx_definition_source($board)
	{
		$CI = & get_instance();

		return '
# /' . $board->shortname . '/
source ' . $board->shortname . '_main : main
{
	sql_query = \
		SELECT doc_id, ' . $board->id . ' AS board, num, subnum, name, trip, email, media_filename, media_id AS mid, media_hash, \
		thread_num AS tnum, CAST(capcode AS UNSIGNED) AS cap, (media_filename != \'\' AND media_filename IS NOT NULL) AS has_image, \
		(subnum != 0) AS is_internal, spoiler AS is_spoiler, deleted AS is_deleted, sticky AS is_sticky, op AS is_op, \
		poster_ip AS pip, timestamp, title, comment FROM ' . $CI->radix->get_table($board) . ' WHERE doc_id >= $start AND doc_id <= $end
	sql_query_info = SELECT * FROM ' . $CI->radix->get_table($board) . ' WHERE doc_id = $id

	sql_query_range = SELECT (SELECT max_ancient_id FROM `' . $CI->db->database . '`.' . $CI->db->protect_identifiers('boards',
			TRUE) . ' WHERE id = ' . $board->id . '), (SELECT MAX(doc_id) FROM ' . $CI->radix->get_table($board) . ')
	sql_query_post_index = UPDATE `' . $CI->db->database . '`.' . $CI->db->protect_identifiers('boards',
			TRUE) . ' SET max_indexed_id = $maxid WHERE id = ' . $board->id . '
}

source ' . $board->shortname . '_ancient : ' . $board->shortname . '_main
{
	sql_query_range = SELECT MIN(doc_id), MAX(doc_id) FROM ' . $CI->radix->get_table($board) . '
	sql_query_post_index = UPDATE `' . $CI->db->database . '`.' . $CI->db->protect_identifiers('boards',
			TRUE) . ' SET max_ancient_id = $maxid WHERE id = ' . $board->id . '
}

source ' . $board->shortname . '_delta : ' . $board->shortname . '_main
{
	sql_query_range = SELECT (SELECT max_ancient_id FROM `' . $CI->db->database . '`.' . $CI->db->protect_identifiers('boards',
			TRUE) . ' WHERE id = ' . $board->id . '), (SELECT MAX(doc_id) FROM ' . $CI->radix->get_table($board) . ')
	sql_query_post_index =
}
		';
	}


	function _generate_sphinx_definition_index($board, $path)
	{
		return "
# /" . $board->shortname . "/
index " . $board->shortname . "_main : main
{
	source = " . $board->shortname . "_main
	path = " . $path . "/" . $board->shortname . "_main
}

index " . $board->shortname . "_ancient : " . $board->shortname . "_main
{
	source = " . $board->shortname . "_ancient
	path = " . $path . "/" . $board->shortname . "_ancient
}

index " . $board->shortname . "_delta : " . $board->shortname . "_main
{
	source = " . $board->shortname . "_delta
	path = " . $path . "/" . $board->shortname . "_delta
}
		";
	}

}

?>
