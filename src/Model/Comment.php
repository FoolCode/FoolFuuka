<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Config;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Cache\Cache;
use Foolz\Foolframe\Model\Logger;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Preferences;
use Foolz\Foolframe\Model\Uri;
use Foolz\Plugin\Hook;
use Foolz\Plugin\PlugSuit;

class CommentException extends \Exception {}
class CommentDeleteWrongPassException extends CommentException {}

class Comment extends Model
{
    use PlugSuit;

    /**
     * If the backlinks must be full URLs or just the hash
     * Notice: this is global because it's used in a PHP callback
     *
     * @var  boolean
     */
    protected $_backlinks_hash_only_url = false;

    /**
     * The bbcode parser object when created
     *
     * @var null|object
     */
    protected static $_bbcode_parser = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var CommentFactory
     */
    protected $comment_factory;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var string
     */
    public $controller_method = 'thread';

    /**
     * @var CommentBulk
     */
    public $bulk;

    /**
     * @var Radix
     */
    public $radix;

    /**
     * @var CommentData
     */
    public $comment;

    /**
     * @var MediaData
     */
    public $media;

    /**
     * @var null|Report[]
     */
    public $reports = null;

    public function __get($key)
    {
        if ($key === 'media') {
            return $this->bulk->media;
        }

        return $this->comment->$key;
    }

    public function __construct(\Foolz\Foolframe\Model\Context $context, CommentBulk $bulk = null)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->config = $context->getService('config');
        $this->preferences = $context->getService('preferences');
        $this->logger = $context->getService('logger');
        $this->uri = $context->getService('uri');
        $this->comment_factory = $context->getService('foolfuuka.comment_factory');
        $this->media_factory = $context->getService('foolfuuka.media_factory');
        $this->ban_factory = $context->getService('foolfuuka.ban_factory');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->report_coll = $context->getService('foolfuuka.report_collection');

        if ($bulk !== null) {
            $this->setBulk($bulk);
        }
    }

    public function setBulk(CommentBulk $bulk)
    {
        $this->radix = $bulk->getRadix();
        $this->bulk = $bulk;
        $this->comment = $bulk->comment;
        $this->media = $bulk->media;

        $this->reports = null;

        // format 4chan archive timestamp
        if ($this->radix->archive && !$this->comment->isArchiveTimezone()) {
            $timestamp = new \DateTime(date('Y-m-d H:i:s', $this->comment->timestamp), new \DateTimeZone('America/New_York'));
            $timestamp->setTimezone(new \DateTimeZone('UTC'));
            $this->comment->timestamp = strtotime($timestamp->format('Y-m-d H:i:s'));

            if ($this->comment->timestamp_expired > 0) {
                $timestamp_expired = new \DateTime(date('Y-m-d H:i:s', $this->comment->timestamp_expired), new \DateTimeZone('America/New_York'));
                $timestamp_expired->setTimezone(new \DateTimeZone('UTC'));
                $this->comment->timestamp_expired = strtotime($timestamp_expired->format('Y-m-d H:i:s'));
            }

            $this->comment->setArchiveTimezone(true);
        }

        if ($this->comment->poster_country !== null) {
            $this->comment->poster_country_name = $this->config->get('foolz/foolfuuka', 'geoip_codes', 'codes.'.strtoupper($this->comment->poster_country));
        }

        $num = $this->comment->num.($this->comment->subnum ? ','.$this->comment->subnum : '');
        $this->comment_factory->_posts[$this->comment->thread_num][] = $num;
    }

    public function setControllerMethod($string)
    {
        $this->controller_method = $string;
    }

    public function getOriginalTimestamp()
    {
        return $this->comment->timestamp;
    }

    public function getFourchanDate()
    {
        if ($this->comment->fourchan_date === false) {
            $fourtime = new \DateTime('@'.$this->getOriginalTimestamp());
            $fourtime->setTimezone(new \DateTimeZone('America/New_York'));

            $this->comment->fourchan_date = $fourtime->format('n/j/y(D)G:i');
        }

        return $this->comment->fourchan_date;
    }

    public function getCommentSanitized()
    {
        if ($this->comment->comment_sanitized === false) {
            $this->comment->comment_sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $this->comment->comment);
        }

        return $this->comment->comment_sanitized;
    }

    public function getCommentProcessed()
    {
        if ($this->comment->comment_processed === false) {
            $this->comment->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->processComment());
        }

        return $this->comment->comment_processed;
    }

    public function getFormatted($params = [])
    {
        if ($this->comment->formatted === false) {
            $this->comment->formatted = $this->buildComment($params);
        }

        return $this->formatted;
    }

    public function getReports()
    {
        if ($this->reports === null) {
            if ($this->getAuth()->hasAccess('comment.reports')) {
                $reports = $this->report_coll->getByDocId($this->radix, $this->comment->doc_id);

                if ($this->bulk->media) {
                    $reports += $this->report_coll->getByMediaId($this->radix, $this->bulk->media->media_id);
                }

                $this->reports = $reports;
            } else {
                $this->reports = [];
            }
        }

        return $this->reports;

    }

    public static function process($string)
    {
        return htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $string));
    }

    public function getTitleProcessed()
    {
        if ($this->comment->title_processed === false) {
            $this->comment->title_processed = static::process($this->comment->title);
        }

        return $this->comment->title_processed;
    }

    public function getNameProcessed()
    {
        if ($this->comment->name_processed === false) {
            $this->comment->name_processed = static::process($this->comment->name);
        }

        return $this->comment->name_processed;
    }

    public function getEmailProcessed()
    {
        if ($this->comment->email_processed === false) {
            $this->comment->email_processed = static::process($this->comment->email);
        }

        return $this->comment->email_processed;
    }

    public function getTripProcessed()
    {
        if ($this->comment->trip_processed === false) {
            $this->comment->trip_processed = static::process($this->comment->trip);
        }

        return $this->comment->trip_processed;
    }

    public function getPosterHashProcessed()
    {
        if ($this->comment->poster_hash_processed === false) {
            $this->comment->poster_hash_processed = static::process($this->comment->poster_hash);
        }

        return $this->comment->poster_hash_processed;
    }

    public function getPosterCountryNameProcessed()
    {
        if ($this->comment->poster_country_name_processed === false) {
            if (!isset($this->comment->poster_country_name)) {
                $this->comment->poster_country_name_processed = null;
            } else {
                $this->comment->poster_country_name_processed = static::process($this->comment->poster_country_name);
            }
        }

        return $this->comment->poster_country_name_processed;
    }

    /**
     * Processes the comment, strips annoying data from moot, converts BBCode,
     * converts > to greentext, >> to internal link, and >>> to external link
     *
     * @param bool $process_backlinks_only If only the backlink parsing must be run
     *
     * @return string the processed comment
     */
    public function processComment($process_backlinks_only = false)
    {
        // default variables
        $find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
        $html = '\\1<span class="greentext">\\2</span>\\3';

        $html = Hook::forge('Foolz\Foolfuuka\Model\Comment::processComment.result.greentext')
            ->setParam('html', $html)
            ->execute()
            ->get($html);

        $comment = $this->comment->comment;

        // this stores an array of moot's formatting that must be removed
        $special = [
            '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
            '<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
        ];

        // remove moot's special formatting
        if ($this->comment->capcode == 'A' && mb_strpos($comment, $special[0], null, 'utf-8') == 0) {
            $comment = str_replace($special[0], '', $comment);

            if (mb_substr($comment, -6, 6, 'utf-8') == '</div>') {
                $comment = mb_substr($comment, 0, mb_strlen($comment, 'utf-8') - 6, 'utf-8');
            }
        }

        if ($this->comment->capcode == 'A' && mb_strpos($comment, $special[1], null, 'utf-8') == 0) {
            $comment = str_replace($special[1], '', $comment);

            if (mb_substr($comment, -10, 10, 'utf-8') == '[/spoiler]') {
                $comment = mb_substr($comment, 0, mb_strlen($comment, 'utf-8') - 10, 'utf-8');
            }
        }

        $comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', false);

        // format entire comment
        $comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
            [$this, 'processInternalLinks'], $comment);

        $comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/([\w-]+(?:,\d+)?)?(\/?)))'i",
            [$this, 'processExternalLinks'], $comment);

        if ($process_backlinks_only) {
            return '';
        }

        $comment = preg_replace($find, $html, $comment);
        $comment = static::parseBbcode($comment, ($this->radix->archive && !$this->comment->subnum));
        $comment = static::autoLinkify($comment, 'url', true);

        // additional formatting
        if ($this->radix->archive && !$this->comment->subnum) {
            // admin bbcode
            $admin_find = "'\[banned\](.*?)\[/banned\]'i";
            $admin_html = '<span class="banned">\\1</span>';

            $comment = preg_replace($admin_find, $admin_html, $comment);
            $comment = preg_replace("'\[(/?(banned|moot|spoiler|code)):lit\]'i", "[$1]", $comment);
        }

        $comment = nl2br(trim($comment));

        if (preg_match_all('/\<pre\>(.*?)\<\/pre\>/', $comment, $match)) {
            foreach ($match as $a) {
                foreach ($a as $b) {
                    $comment = str_replace('<pre>'.$b.'</pre>', "<pre>".str_replace("<br />", "", $b)."</pre>", $comment);
                }
            }
        }

        return $this->comment->comment_processed = $comment;
    }

    protected static function parseBbcode($str, $special_code, $strip = true)
    {
        if (static::$_bbcode_parser === null) {
            $bbcode = new \StringParser_BBCode();

            $codes = [];

            // add list of bbcode for formatting
            $codes[] = ['code', 'simple_replace', null, ['start_tag' => '<code>', 'end_tag' => '</code>'], 'code',
                ['block', 'inline'], []];
            $codes[] = ['spoiler', 'simple_replace', null,
                ['start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'], 'inline', ['block', 'inline'],
                ['code']];
            $codes[] = ['sub', 'simple_replace', null, ['start_tag' => '<sub>', 'end_tag' => '</sub>'], 'inline',
                ['block', 'inline'], ['code']];
            $codes[] = ['sup', 'simple_replace', null, ['start_tag' => '<sup>', 'end_tag' => '</sup>'], 'inline',
                ['block', 'inline'], ['code']];
            $codes[] = ['b', 'simple_replace', null, ['start_tag' => '<b>', 'end_tag' => '</b>'], 'inline',
                ['block', 'inline'], ['code']];
            $codes[] = ['i', 'simple_replace', null, ['start_tag' => '<em>', 'end_tag' => '</em>'], 'inline',
                ['block', 'inline'], ['code']];
            $codes[] = ['m', 'simple_replace', null, ['start_tag' => '<tt class="code">', 'end_tag' => '</tt>'],
                'inline', ['block', 'inline'], ['code']];
            $codes[] = ['o', 'simple_replace', null, ['start_tag' => '<span class="overline">', 'end_tag' => '</span>'],
                'inline', ['block', 'inline'], ['code']];
            $codes[] = ['s', 'simple_replace', null,
                ['start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'], 'inline', ['block', 'inline'],
                ['code']];
            $codes[] = ['u', 'simple_replace', null,
                ['start_tag' => '<span class="underline">', 'end_tag' => '</span>'], 'inline', ['block', 'inline'],
                ['code']];
            $codes[] = ['EXPERT', 'simple_replace', null,
                ['start_tag' => '<span class="expert">', 'end_tag' => '</span>'], 'inline', ['block', 'inline'],
                ['code']];

            foreach($codes as $code) {
                if ($strip) {
                    $code[1] = 'callback_replace';
                    $code[2] = '\\Comment::stripUnusedBbcode'; // this also fixes pre/code
                }

                $bbcode->addCode($code[0], $code[1], $code[2], $code[3], $code[4], $code[5], $code[6]);
            }

            static::$_bbcode_parser = $bbcode;
        }

        // if $special == true, add special bbcode
        if ($special_code === true) {
            /* @todo put this into form bootstrap
            if ($CI->theme->get_selected_theme() == 'fuuka') {
                $bbcode->addCode('moot', 'simple_replace', null,
                    ['start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'),
                    'inline', array['block', 'inline'], []);
            } else {*/
                static::$_bbcode_parser->addCode('moot', 'simple_replace', null, ['start_tag' => '', 'end_tag' => ''], 'inline',
                    ['block', 'inline'], []);
            /* } */
        }

        return static::$_bbcode_parser->parse($str);
    }

    public static function stripUnusedBbcode($action, $attributes, $content, $params, &$node_object)
    {
        if ($content === '' || $content === false) {
            return '';
        }

        // if <code> has multiple lines, wrap it in <pre> instead
        if ($params['start_tag'] == '<code>') {
            if (count(array_filter(preg_split('/\r\n|\r|\n/', $content))) > 1) {
                return '<pre>'.$content.'</pre>';
            }
        }

        // limit nesting level
        $parent_count = 0;
        $temp_node_object = $node_object;
        while ($temp_node_object->_parent !== null) {
            $parent_count++;
            $temp_node_object = $temp_node_object->_parent;

            if (in_array($params['start_tag'], ['<sub>', '<sup>']) && $parent_count > 1) {
                return $content;
            } elseif ($parent_count > 4) {
                return $content;
            }
        }

        return $params['start_tag'].$content.$params['end_tag'];
    }

    /**
     * A callback function for preg_replace_callback for internal links (>>)
     * Notice: this function generates some class variables
     *
     * @param array $matches the matches sent by preg_replace_callback
     * @return string the complete anchor
     */
    public function processInternalLinks($matches)
    {
        // don't process when $this->num is 0
        if ($this->comment->num == 0) {
            return $matches[0];
        }

        $num = $matches[2];

        // create link object with all relevant information
        $data = new \stdClass();
        $data->num = str_replace(',', '_', $matches[2]);
        $data->board = $this->radix;
        $data->post = $this;

        $current_p_num_c = $this->comment->num.($this->comment->subnum ? ','.$this->comment->subnum : '');
        $current_p_num_u = $this->comment->num.($this->comment->subnum ? '_'.$this->comment->subnum : '');

        $build_url = [
            'tags' => ['', ''],
            'hash' => '',
            'attr' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
            'attr_op' => 'class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
            'attr_backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $current_p_num_u . '"',
        ];

        $build_url = Hook::forge('Foolz\Foolfuuka\Model\Comment::processInternalLinks.result.html')
            ->setObject($this)
            ->setParam('data', $data)
            ->setParam('build_url', $build_url)
            ->execute()
            ->get($build_url);

        $this->comment_factory->_backlinks_arr[$data->num][$current_p_num_u] = ['build_url' => $build_url, 'data' => $data];

        if (array_key_exists($num, $this->comment_factory->_posts)) {
            return implode('<a href="' . $this->uri->create([$data->board->shortname, $this->controller_method, $num]) . '#' . $data->num . '" '
                . $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
        }

        foreach ($this->comment_factory->_posts as $key => $thread) {
                return implode('<a href="' . $this->uri->create([$data->board->shortname, $this->controller_method, $key]) . '#' . $build_url['hash'] . $data->num . '" '
                    . $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
        }

        return implode('<a href="' . $this->uri->create([$data->board->shortname, 'post', $data->num]) . '" '
            . $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);

        // return un-altered
        return $matches[0];
    }

    public function getBacklinks()
    {
        $num = $this->subnum ? $this->num.'_'.$this->subnum : $this->num;

        if (isset($this->comment_factory->_backlinks_arr[$num])) {
            ksort($this->comment_factory->_backlinks_arr[$num], SORT_STRING);
            $array = [];
            foreach ($this->comment_factory->_backlinks_arr[$num] as $current_p_num_c => $value) {
                $build_url = $value['build_url'];
                $data = $value['data'];
                $array[] = implode(
                    '<a href="' . $this->uri->create([$data->board->shortname, $this->controller_method, $data->post->thread_num]) . '#' . $build_url['hash'] . $current_p_num_u . '" ' .
                    $build_url['attr_backlink'] . '>&gt;&gt;' . $current_p_num_c . '</a>', $build_url['tags']
                );
            }
            return $array;
        }

        return [];
    }

    /**
     * A callback function for preg_replace_callback for external links (>>>//)
     * Notice: this function generates some class variables
     *
     * @param array $matches the matches sent by preg_replace_callback
     * @return string the complete anchor
     */
    public function processExternalLinks($matches)
    {
        // create $data object with all results from $matches
        $data = new \stdClass();
        $data->link = $matches[2];
        $data->shortname = $matches[3];
        $data->board = $this->radix_coll->getByShortname($data->shortname);
        $data->query = $matches[4];

        $build_href = [
            // this will wrap the <a> element with a container element [open, close]
            'tags' => ['open' => '', 'close' => ''],

            // external links; defaults to 4chan
            'short_link' => '//boards.4chan.org/'.$data->shortname.'/',
            'query_link' => '//boards.4chan.org/'.$data->shortname.'/res/'.$data->query,

            // additional attributes + backlinking attributes
            'attributes' => '',
            'backlink_attr' => ' class="backlink" data-function="highlight" data-backlink="true" data-board="'
                .(($data->board)?$data->board->shortname:$data->shortname).'" data-post="'.$data->query.'"'
        ];

        $build_href = Hook::forge('Foolz\Foolfuuka\Model\Comment::processExternalLinks.result.html')
            ->setObject($this)
            ->setParam('data', $data)
            ->setParam('build_href', $build_href)
            ->execute()
            ->get($build_href);

        if (!$data->board) {
            if ($data->query) {
                return implode('<a href="'.$build_href['query_link'].'"'.$build_href['attributes'].'>&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
            }

            return implode('<a href="'.$build_href['short_link'].'">&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
        }

        if ($data->query) {
            return implode('<a href="'.$this->uri->create([$data->board->shortname, 'post', $data->query]).'"'
                .$build_href['attributes'].$build_href['backlink_attr'].'>&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
        }

        return implode('<a href="' . $this->uri->create($data->board->shortname) . '">&gt;&gt;&gt;' . $data->link . '</a>', $build_href['tags']);
    }

    /**
     * Returns a string with all text links transformed into clickable links
     *
     * @param string $str
     * @param string $type
     * @param boolean $popup
     *
     * @return string
     */
    public static function autoLinkify($str, $type = 'both', $popup = false)
    {
        if ($type != 'email') {
            $target = ($popup == true) ? ' target="_blank"' : '';

            $str = preg_replace("#((^|\s|\(|\])(((http(s?)://)|(www\.))(\w+[^\s\)\<]+)))#i", '$2<a href="$3"'.$target.'>$3</a>', $str);
        }

        return $str;
    }

    /**
     * Returns the timestamp fixed for the radix time
     *
     * @param int|null $time If a timestamp is supplied, it will calculate the time in relation to that moment
     *
     * @return int resulting timestamp
     */
    public function getRadixTime($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        if ($this->radix->archive) {
            $datetime = new \DateTime(date('Y-m-d H:i:s', $time), new \DateTimeZone('UTC'));
            $datetime->setTimezone(new \DateTimeZone('America/New_York'));

            return strtotime($datetime->format('Y-m-d H:i:s'));
        } else {
            return $time;
        }
    }

    /**
     * Delete the post and eventually the entire thread if it's OP
     * Also deletes the images when it's the only post with that image
     *
     * @param null $password
     * @param bool $force
     * @param bool $thread
     * @throws CommentSendingDatabaseException
     * @throws CommentDeleteWrongPassException
     * @return array|bool
     */
    protected function p_delete($password = null, $force = false, $thread = false)
    {
        if (!$this->getAuth()->hasAccess('comment.passwordless_deletion') && $force !== true) {
            if (!password_verify($password, $this->comment->getDelpass())) {
                throw new CommentDeleteWrongPassException(_i('You did not provide the correct deletion password.'));
            }
        }

        try {
            $this->dc->getConnection()->beginTransaction();

            // throw into _deleted table
            $this->dc->getConnection()->executeUpdate(
                'INSERT INTO '.$this->radix->getTable('_deleted').' '.
                    $this->dc->qb()
                        ->select('*')
                        ->from($this->radix->getTable(), 't')
                        ->where('doc_id = '.$this->dc->getConnection()->quote($this->comment->doc_id))
                        ->getSQL()
            );

            // delete post
            $this->dc->qb()
                ->delete($this->radix->getTable())
                ->where('doc_id = :doc_id')
                ->setParameter(':doc_id', $this->comment->doc_id)
                ->execute();

            // remove any extra data
            $this->dc->qb()
                ->delete($this->radix->getTable('_extra'))
                ->where('extra_id = :doc_id')
                ->setParameter(':doc_id', $this->comment->doc_id)
                ->execute();

            // purge reports
            $this->dc->qb()
                ->delete($this->dc->p('reports'))
                ->where('board_id = :board_id')
                ->andWhere('doc_id = :doc_id')
                ->setParameter(':board_id', $this->radix->id)
                ->setParameter(':doc_id', $this->comment->doc_id)
                ->execute();

            // clear cache
            $this->radix_coll->clearCache();

            // remove image file
            if (isset($this->media)) {
                $media = new Media($this->getContext(), $this->bulk);
                $media->delete();
            }

            // if this is OP, delete replies too
            if ($this->comment->op) {
                // delete thread data
                $this->dc->qb()
                    ->delete($this->radix->getTable('_threads'))
                    ->where('thread_num = :thread_num')
                    ->setParameter(':thread_num', $this->comment->thread_num)
                    ->execute();

                // process each comment
                $comments = $this->dc->qb()
                    ->select('doc_id')
                    ->from($this->radix->getTable(), 'b')
                    ->where('thread_num = :thread_num')
                    ->setParameter(':thread_num', $this->comment->thread_num)
                    ->execute()
                    ->fetchAll();

                foreach ($comments as $comment) {
                    $post = Board::forge($this->getContext())
                        ->getPost()
                        ->setOptions('doc_id', $comment['doc_id'])
                        ->setRadix($this->radix)
                        ->getComments();

                    $post = current($post);
                    $post->delete(null, true, true);
                }
            } else {
                // if this is not triggered by a thread deletion, update the thread table
                if ($thread === false && !$this->radix->archive) {
                    $time_last = '
                    (
                        COALESCE(GREATEST(
                            time_op,
                            (
                                SELECT MAX(timestamp) FROM '.$this->radix->getTable().' xr
                                WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).' AND subnum = 0
                            )
                        ), time_op)
                    )';

                    $time_bump = '
                    (
                        COALESCE(GREATEST(
                            time_op,
                            (
                                SELECT MAX(timestamp) FROM '.$this->radix->getTable().' xr
                                WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).' AND subnum = 0
                                    AND (email <> \'sage\' OR email IS NULL)
                            )
                        ), time_op)
                    )';

                    $time_ghost = '
                    (
                        SELECT MAX(timestamp) FROM '.$this->radix->getTable().' xr
                        WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).' AND subnum <> 0
                    )';

                    $time_ghost_bump = '
                    (
                        SELECT MAX(timestamp) FROM '.$this->radix->getTable().' xr
                        WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).' AND subnum <> 0
                            AND (email <> \'sage\' OR email IS NULL)
                    )';

                    // update thread information
                    $this->dc->qb()
                        ->update($this->radix->getTable('_threads'))
                        ->set('time_last', $time_last)
                        ->set('time_bump', $time_bump)
                        ->set('time_ghost', $time_ghost)
                        ->set('time_ghost_bump', $time_ghost_bump)
                        ->set('time_last_modified', ':time')
                        ->set('nreplies', 'nreplies - 1')
                        ->set('nimages', ($this->media === null ? 'nimages - 1' : 'nimages'))
                        ->where('thread_num = :thread_num')
                        ->setParameter(':time', $this->getRadixTime())
                        ->setParameter(':thread_num', $this->comment->thread_num)
                        ->execute();
                }
            }

            $this->dc->getConnection()->commit();

            // clean up some caches
            Cache::item('foolfuuka.model.board.getThreadComments.thread.'
                .md5(serialize([$this->radix->shortname, $this->comment->thread_num])))->delete();

            // clean up the 10 first pages of index and gallery that are cached
            for ($i = 1; $i <= 10; $i++) {
                Cache::item('foolfuuka.model.board.getLatestComments.query.'
                    .$this->radix->shortname.'.by_post.'.$i)->delete();

                Cache::item('foolfuuka.model.board.getLatestComments.query.'
                    .$this->radix->shortname.'.by_thread.'.$i)->delete();

                Cache::item('foolfuuka.model.board.getThreadsComments.query.'
                    .$this->radix->shortname.'.'.$i)->delete();
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->logger->error('\Foolz\Foolfuuka\Model\CommentInsert: '.$e->getMessage());
            $this->dc->getConnection()->rollBack();

            throw new CommentSendingDatabaseException(_i('Something went wrong when deleting the post in the database. Try again.'));
        }

        return $this;
    }

    /**
     * Processes the name with unprocessed tripcode and returns name and processed tripcode
     *
     * @return array name without tripcode and processed tripcode concatenated with processed secure tripcode
     */
    protected function p_processName()
    {
        $name = $this->comment->name;

        // define variables
        $matches = [];
        $normal_trip = '';
        $secure_trip = '';

        if (preg_match("'^(.*?)(#)(.*)$'", $this->comment->name, $matches)) {
            $matches_trip = [];
            $name = trim($matches[1]);

            preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

            if (count($matches_trip) > 1) {
                $normal_trip = $this->processTripcode($matches_trip[1]);
                $normal_trip = $normal_trip ? '!'.$normal_trip : '';
            }

            if (count($matches_trip) > 2) {
                $secure_trip = '!!'.$this->processSecureTripcode($matches_trip[2]);
            }
        }

        $this->comment->name = $name;
        $this->comment->trip = $normal_trip . $secure_trip;

        return ['name' => $name, 'trip' => $normal_trip . $secure_trip];
    }

    /**
     * Processes the tripcode
     *
     * @param string $plain the word to generate the tripcode from
     * @return string the processed tripcode
     */
    protected function p_processTripcode($plain)
    {
        if (trim($plain) == '') {
            return '';
        }

        $trip = mb_convert_encoding($plain, 'SJIS', 'UTF-8');

        $salt = substr($trip.'H.', 1, 2);
        $salt = preg_replace('/[^.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

        return substr(crypt($trip, $salt), -10);
    }

    /**
     * Process the secure tripcode
     *
     * @param string $plain the word to generate the secure tripcode from
     * @return string the processed secure tripcode
     */
    protected function p_processSecureTripcode($plain)
    {
        return substr(base64_encode(sha1($plain . base64_decode($this->config->get('foolz/foolfuuka', 'config', 'comment.secure_tripcode_salt')), true)), 0, 11);
    }
}
