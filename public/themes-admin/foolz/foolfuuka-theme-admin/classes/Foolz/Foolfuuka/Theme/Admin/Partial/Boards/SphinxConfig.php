<?php

namespace Foolz\Foolfuuka\Theme\Admin\Partial\Boards;

class SphinxConfig extends \Foolz\Foolframe\View\View
{
    public function toString()
    {
        extract($this->getParamManager()->getParams());
        ?>

<div class="admin-container">
    <div class="admin-container-header">
        <?= _i('Configuration File') ?>
    </div>

<?php if ($example === false) : ?>
    <i class="icon-exclamation-sign text-error"></i> <?= _i('Error: You do not have any boards created. Please make one before generating a configuration file again.') ?>
<?php else : ?>
    <i class="icon-warning-sign text-warning"></i> <?= _i('Notice: Due to security concerns, the database connection values have been left empty.') ?>

    <hr/>

<textarea class="input-block-level" rows="30" id="sphinx-config">
###########################################
## Sphinx Configuration File for FoolFuuka
###########################################

source main
{
  type     = mysql
  sql_host =
  sql_user =
  sql_pass =
  sql_db   =
  sql_port =
  mysql_connect_flags = <?= $mysql['flag'] ?>

  sql_query_pre  = SET NAMES utf8mb4

  sql_range_step = 10000
  sql_query      = \
      SELECT doc_id, <?= $example->id ?> AS board, timestamp, num, subnum, title, comment, name, trip, email, \
        media_filename, media_id as mid, media_hash, poster_ip as pip, poster_hash as pid, thread_num AS tnum, \
        ASCII(capcode) AS cap, (media_filename != '' AND media_filename IS NOT NULL) AS has_image, (subnum != 0) \
        AS is_internal, spoiler AS is_spoiler, deleted AS is_deleted, sticky as is_sticky, op AS is_op \
      FROM `<?= $example->shortname ?>` LIMIT 1

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

  sql_query_post_index =
  sql_query_info       =
}

<?php foreach ($boards as $key => $board) : ?>
# /<?= $board->shortname ?>/
source <?= $board->shortname.'_main' ?> : main
{
  sql_query      = \
      SELECT doc_id, <?= $board->id ?> AS board, timestamp, num, subnum, title, comment, name, trip, email, \
        media_filename, media_id as mid, media_hash, poster_ip as pip, poster_hash as pid, thread_num AS tnum, \
        ASCII(capcode) AS cap, (media_filename != '' AND media_filename IS NOT NULL) AS has_image, (subnum != 0) \
        AS is_internal, spoiler AS is_spoiler, deleted AS is_deleted, sticky as is_sticky, op AS is_op \
      FROM `<?= $board->shortname ?>` WHERE doc_id >= $start AND doc_id <= $end
  sql_query_info = SELECT * FROM `<?= $board->shortname ?>` WHERE doc_id = $id

  sql_query_range      = SELECT (SELECT val FROM `index_counters` WHERE id = 'max_ancient_id_<?= $board->shortname ?>'), (SELECT MAX(doc_id) FROM `<?= $board->shortname ?>`)
  sql_query_post_index = REPLACE INTO `index_counters` (id, val) VALUES ('max_indexed_id_<?= $board->shortname ?>', $maxid)
}

source <?= $board->shortname.'_ancient' ?> : <?= $board->shortname.'_main' ?>
{
  sql_query_range      = SELECT MIN(doc_id), MAX(doc_id) FROM `<?= $board->shortname ?>`
  sql_query_post_index = REPLACE INTO `index_counters` (id, val) VALUES ('max_ancient_id_<?= $board->shortname ?>', $maxid)
}

source <?= $board->shortname.'_delta' ?> : <?= $board->shortname.'_main' ?>
{
  sql_query_range      = SELECT (SELECT val FROM `index_counters` WHERE id = 'max_indexed_id_<?= $board->shortname ?>'), (SELECT MAX(doc_id) FROM `<?= $board->shortname ?>`)
  sql_query_post_index =
}

<?php endforeach; ?>

#############################################################################
## index definition
#############################################################################

index main
{
  source       = main
  path         = <?= rtrim($sphinx['working_directory'], '/') ?>/data/main
  docinfo      = extern
  mlock        = 0
  morphology   = none
  charset_type = utf-8
  min_word_len = <?= $sphinx['min_word_len'] ?>

  # optional, default value depends on charset_type
  #
  # defaults are configured to include English and Russian characters only
  # you need to change the table to include additional ones
  # this behavior MAY change in future versions
  #
  # 'sbcs' default value is
  # charset_table    = 0..9, A..Z->a..z, _, a..z, U+A8->U+B8, U+B8, U+C0..U+DF->U+E0..U+FF, U+E0..U+FF
  #
  # 'utf-8' default value is
  # charset_table    = 0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F
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

  min_prefix_len    = 3
  prefix_fields     = comment, title
  enable_star       = 1
  html_strip        = 0
}

<?php foreach ($boards as $key => $board) : ?>
# /<?= $board->shortname ?>/
index <?= $board->shortname.'_main' ?> : main
{
  source = <?= $board->shortname ?>_main
  path   = <?=rtrim($sphinx['working_directory'], '/') ?>/data/<?= $board->shortname ?>_main
}

index <?= $board->shortname.'_ancient' ?> : <?= $board->shortname.'_main' ?>
{
  source = <?= $board->shortname ?>_ancient
  path   = <?=rtrim($sphinx['working_directory'], '/') ?>/data/<?= $board->shortname ?>_ancient
}

index <?= $board->shortname.'_delta' ?> : <?= $board->shortname.'_main' ?>
{
  source = <?= $board->shortname ?>_delta
  path   = <?=rtrim($sphinx['working_directory'], '/') ?>/data/<?= $board->shortname ?>_delta
}

<?php endforeach; ?>

#############################################################################
## indexer settings
#############################################################################

indexer
{
  # memory limit, in bytes, kiloytes (16384K) or megabytes (256M)
  # optional, default is 32M, max is 2047M, recommended is 256M to 1024M
  mem_limit             = <?= $sphinx['mem_limit'] ?>

  # maximum IO calls per second (for I/O throttling)
  # optional, default is 0 (unlimited)
  #
  # max_iops            = 40

  # maximum IO call size, bytes (for I/O throttling)
  # optional, default is 0 (unlimited)
  #
  # max_iosize          = 1048576

  # maximum xmlpipe2 field length, bytes
  # optional, default is 2M
  #
  max_xmlpipe2_field    = 4M

  # write buffer size, bytes
  # several (currently up to 4) buffers will be allocated
  # write buffers are allocated in addition to mem_limit
  # optional, default is 1M
  #
  write_buffer          = 8M

  # maximum file field adaptive buffer size
  # optional, default is 8M, minimum is 1M
  #
  max_file_field_buffer = 32M
}

#############################################################################
## searchd settings
#############################################################################

searchd
{
  # [hostname:]port[:protocol], or /unix/socket/path to listen on
  # known protocols are 'sphinx' (SphinxAPI) and 'mysql41' (SphinxQL)
  #
  # multi-value, multiple listen points are allowed
  # optional, defaults are 9312:sphinx and 9306:mysql41, as below
  #
  # listen      = 127.0.0.1
  # listen      = 192.168.0.1:9312
  # listen      = 9312
  # listen      = /var/run/searchd.sock
  listen       = 9312:sphinx
  listen       = 9306:mysql41

  # log file, searchd run info is logged here
  # optional, default is 'searchd.log'
  log          = <?=rtrim($sphinx['working_directory'], '/') ?>/searchd.log

  # query log file, all search queries are logged here
  # optional, default is empty (do not log queries)
  query_log    = <?=rtrim($sphinx['working_directory'], '/') ?>/sphinx-query.log

  # client read timeout, seconds
  # optional, default is 5
  read_timeout    = 5

  # request timeout, seconds
  # optional, default is 5 minutes
  client_timeout  = 300

  # maximum amount of children to fork (concurrent searches to run)
  # optional, default is 0 (unlimited)
  max_children    = <?= $sphinx['max_children'] ?>

  # PID file, searchd process ID file name
  # mandatory
  pid_file        = <?=rtrim($sphinx['working_directory'], '/') ?>/searchd.pid

  # max amount of matches the daemon ever keeps in RAM, per-index
  # WARNING, THERE'S ALSO PER-QUERY LIMIT, SEE SetLimits() API CALL
  # default is 1000 (just like Google)
  max_matches     = <?= $sphinx['max_matches'] ?>


  # seamless rotate, prevents rotate stalls if precaching huge datasets
  # optional, default is 1
  seamless_rotate = 1

  # whether to forcibly preopen all indexes on startup
  # optional, default is 1 (preopen everything)
  preopen_indexes = 1

  # whether to unlink .old index copies on succesful rotation.
  # optional, default is 1 (do unlink)
  unlink_old      = 1

  # attribute updates periodic flush timeout, seconds
  # updates will be automatically dumped to disk this frequently
  # optional, default is 0 (disable periodic flush)
  #
  # attr_flush_period  = 900

  # instance-wide ondisk_dict defaults (per-index value take precedence)
  # optional, default is 0 (precache all dictionaries in RAM)
  #
  # ondisk_dict_default  = 1

  # MVA updates pool size
  # shared between all instances of searchd, disables attr flushes!
  # optional, default size is 1M
  mva_updates_pool  = 4M

  # max allowed network packet size
  # limits both query packets from clients, and responses from agents
  # optional, default size is 8M
  max_packet_size   = 32M

  # crash log path
  # searchd will (try to) log crashed query to 'crash_log_path.PID' file
  # optional, default is empty (do not create crash logs)
  #
  # crash_log_path    = <?=rtrim($sphinx['working_directory'], '/') ?>/log/crash

  # max allowed per-query filter count
  # optional, default is 256
  max_filters        = 256

  # max allowed per-filter values count
  # optional, default is 4096
  max_filter_values  = 4096

  # socket listen queue length
  # optional, default is 5
  #
  listen_backlog    = 25

  # per-keyword read buffer size
  # optional, default is 256K
  #
  # read_buffer    = 256K

  # unhinted read size (currently used when reading hits)
  # optional, default is 32K
  #
  # read_unhinted    = 32K

  # max allowed per-batch query count (aka multi-query count)
  # optional, default is 32
  max_batch_queries  = 32

  # max common subtree document cache size, per-query
  # optional, default is 0 (disable subtree optimization)
  #
  # subtree_docs_cache  = 4M

  # max common subtree hit cache size, per-query
  # optional, default is 0 (disable subtree optimization)
  #
  # subtree_hits_cache  = 8M

  # multi-processing mode (MPM)
  # known values are none, fork, prefork, and threads
  # optional, default is fork
  #
  workers      = threads # for RT to work

  # max threads to create for searching local parts of a distributed index
  # optional, default is 0, which means disable multi-threaded searching
  # should work with all MPMs (ie. does NOT require workers=threads)
  #
  # dist_threads    = 4

  # binlog files path; use empty string to disable binlog
  # optional, default is build-time configured data directory
  #
  # binlog_path    = # disable logging
  binlog_path    = <?=rtrim($sphinx['working_directory'], '/') ?>/data

  # binlog flush/sync mode
  # 0 means flush and sync every second
  # 1 means flush and sync every transaction
  # 2 means flush every transaction, sync every second
  # optional, default is 2
  #
  # binlog_flush    = 2

  # binlog per-file size limit
  # optional, default is 128M, 0 means no limit
  #
  # binlog_max_log_size  = 256M

  # per-thread stack size, only affects workers=threads mode
  # optional, default is 64K
  #
  thread_stack      = 128K

  # per-keyword expansion limit (for dict=keywords prefix searches)
  # optional, default is 0 (no limit)
  #
  # expansion_limit    = 1000

  # RT RAM chunks flush period
  # optional, default is 0 (no periodic flush)
  #
  # rt_flush_period    = 900

  # query log file format
  # optional, known values are plain and sphinxql, default is plain
  #
  # query_log_format    = sphinxql

  # version string returned to MySQL network protocol clients
  # optional, default is empty (use Sphinx version)
  #
  # mysql_version_string  = 5.0.37

  # trusted plugin directory
  # optional, default is empty (disable UDFs)
  #
  # plugin_dir      = /usr/local/sphinx/lib

  # default server-wide collation
  # optional, default is libc_ci
  #
  collation_server    = utf8_general_ci

  # server-wide locale for libc based collations
  # optional, default is C
  #
  collation_libc_locale  = en_US.UTF-8

  # threaded server watchdog (only used in workers=threads mode)
  # optional, values are 0 and 1, default is 1 (watchdog on)
  #
  # watchdog        = 1

  # SphinxQL compatibility mode (legacy columns and their names)
  # optional, default is 0 (SQL compliant syntax and result sets)
  #
  compat_sphinxql_magics  = 0
}
# --eof--</textarea>
</div>

<script type="text/javascript">
    jQuery('#sphinx-config').click(function() {
        jQuery(this).select();
    });
</script>
<?php endif; ?>
<?php
    }
}
