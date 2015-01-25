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
class CommentUpdateException extends CommentException {}
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

        $num = $this->getPostNum(',');
        $this->comment_factory->posts[$this->comment->thread_num][] = $num;
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

    public function getPostNum($separator = ',')
    {
        return $this->comment->num.($this->comment->subnum ? $separator.$this->comment->subnum : '');
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
        $greentext = Hook::forge('Foolz\Foolfuuka\Model\Comment::processComment#var.greentext')
            ->setParam('html', '\\1<span class="greentext">\\2</span>\\3')
            ->execute()
            ->get('\\1<span class="greentext">\\2</span>\\3');

        $comment = Hook::forge('Foolz\FoolFuuka\Model\Comment::processComment#var.originalComment')
            ->setObject($this)
            ->setParam('comment', $this->comment->comment)
            ->execute()
            ->get($this->comment->comment);

        // sanitize comment
        $comment = htmlentities($comment, ENT_COMPAT, 'UTF-8', false);

        // process comment for greentext, bbcode, links
        $comment = preg_replace('/(\r?\n|^)(&gt;.*?)(?=$|\r?\n)/i', $greentext, $comment);
        $comment = $this->processCommentBBCode($comment);
        $comment = $this->processCommentLinks($comment);

        // process internal and external links
        $comment = preg_replace_callback('/(&gt;&gt;(\d+(?:,\d+)?))/i',
            [$this, 'processInternalLinks'], $comment);
        $comment = preg_replace_callback('/(&gt;&gt;&gt;(\/(\w+)\/([\w-]+(?:,\d+)?)?(\/?)))/i',
            [$this, 'processExternalLinks'], $comment);

        if ($process_backlinks_only) {
            return '';
        }

        $comment = nl2br(trim($comment));

        $comment = Hook::forge('Foolz\FoolFuuka\Model\Comment::processComment#var.processedComment')
            ->setObject($this)
            ->setParam('comment', $comment)
            ->execute()
            ->get($comment);

        return $this->comment->comment_processed = $comment;
    }

    protected function processCommentBBCode($comment)
    {
        $parser = new \JBBCode\Parser();
        $definitions = array();

        $builder = new \JBBCode\CodeDefinitionBuilder('code', '<pre class="code">{param}</pre>');
        $builder->setParseContent(false);
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('spoiler', '<span class="spoiler">{param}</span>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('sub', '<sub>{param}</sub>');
        $builder->setNestLimit(1);
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('sup', '<sup>{param}</sup>');
        $builder->setNestLimit(1);
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('eqn', '<script type="math/tex; mode=display">{param}</script>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('math', '<script type="math/tex">{param}</script>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('b', '<strong>{param}</strong>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('i', '<em>{param}</em>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('o', '<span class="overline">{param}</span>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('s', '<span class="strikethrough">{param}</span>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('u', '<span class="underline">{param}</span>');
        array_push($definitions, $builder->build());

        $builder = new \JBBCode\CodeDefinitionBuilder('fortune', '<span class="fortune" style="color: {color}">{param}</span>');
        $builder->setUseOption(true);
        array_push($definitions, $builder->build());

        $definitions = Hook::forge('Foolz\Foolfuuka\Model\Comment::processCommentBBCode#var.definitions')
            ->setObject($this)
            ->setParam('definitions', $definitions)
            ->execute()
            ->get($definitions);

        foreach ($definitions as $definition) {
            $parser->addCodeDefinition($definition);
        }

        // work around for dealing with quotes in BBCode tags
        $comment = str_replace('&quot;', '"', $comment);
        $comment = $parser->parse($comment)->getAsBBCode();
        $comment = str_replace('"', '&quot;', $comment);

        return $parser->parse($comment)->getAsHTML();
    }

    /**
     * Returns a string with all text links transformed into clickable links
     *
     * @param string $comment
     *
     * @return string
     */
    public function processCommentLinks($comment)
    {
        return preg_replace_callback('/(?i)\b((?:((?:ht|f)tps?:(?:\/{1,3}|[a-z0-9%]))|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b\/?(?!@)))/i', [$this, 'processLinkify'], $comment);
    }

    public function processLinkify($matches)
    {
        // if protocol is not set, use http by default
        if (!isset($matches[2])) {
            return '<a href="http://'.$matches[1].'" target="_blank">'.$matches[1].'</a>';
        }

        return '<a href="'.$matches[1].'" target="_blank">'.$matches[1].'</a>';
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

        $current_p_num_c = $this->getPostNum(',');
        $current_p_num_u = $this->getPostNum('_');

        $build_url = [
            'tags' => ['', ''],
            'hash' => '',
            'attr' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
            'attr_op' => 'class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
            'attr_backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $current_p_num_u . '"',
        ];

        $build_url = Hook::forge('Foolz\Foolfuuka\Model\Comment::processInternalLinks#var.link')
            ->setObject($this)
            ->setParam('data', $data)
            ->setParam('build_url', $build_url)
            ->execute()
            ->get($build_url);

        $this->comment_factory->backlinks_arr[$data->num][$current_p_num_u] = ['build_url' => $build_url, 'data' => $data, 'current_p_num_c' => $current_p_num_c];

        if (isset($this->comment_factory->posts[$this->comment->thread_num]) && in_array($num, $this->comment_factory->posts[$this->comment->thread_num])) {
            return implode('<a href="'.$this->uri->create([$data->board->shortname, $this->controller_method, $this->comment->thread_num]).'#'.$data->num.'" '
                .(array_key_exists($num, $this->comment_factory->posts) ? $build_url['attr_op'] : $build_url['attr'])
                .'>&gt;&gt;'.$num.'</a>', $build_url['tags']);
        }

        return implode('<a href="'.$this->uri->create([$data->board->shortname, 'post', $data->num]).'" '
            .$build_url['attr'].'>&gt;&gt;'.$num.'</a>', $build_url['tags']);
    }

    public function getBacklinks()
    {
        $num = $this->subnum ? $this->num.'_'.$this->subnum : $this->num;

        if (isset($this->comment_factory->backlinks_arr[$num])) {
            ksort($this->comment_factory->backlinks_arr[$num], SORT_STRING);
            $array = [];
            foreach ($this->comment_factory->backlinks_arr[$num] as $current_p_num_u => $value) {
                $build_url = $value['build_url'];
                $data = $value['data'];
                $current_p_num_c = $value['current_p_num_c'];
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

        $build_href = Hook::forge('Foolz\Foolfuuka\Model\Comment::processExternalLinks#var.link')
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

    protected function p_setThreadData($field = null, $value = null)
    {
        if (!$this->comment->op) {
            throw new CommentUpdateException(_i('Invalid Comment.'));
        }

        if ($field === null || $value === null) {
            throw new CommentUpdateException(_i('Missing parameters.'));
        }

        try {
            $this->dc->getConnection()->beginTransaction();

            // we don't want to modify archived data
            if (!$this->radix->archive) {
                $this->dc->qb()
                    ->update($this->radix->getTable())
                    ->set('sticky', $value)
                    ->where('doc_id = :doc_id')
                    ->setParameter(':doc_id', $this->comment->doc_id)
                    ->execute();
            }

            $this->dc->qb()
                ->update($this->radix->getTable('_threads'))
                ->set('time_last_modified', ':update')
                ->set($field, $value)
                ->where('thread_num = :thread')
                ->setParameter(':thread', $this->comment->thread_num)
                ->setParameter(':update', $this->getRadixTime())
                ->execute();

            $this->dc->getConnection()->commit();
            $this->clearCache();
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->logger->error('\Foolz\Foolfuuka\Model\Comment: '.$e->getMessage());
            $this->dc->getConnection()->rollBack();

            throw new CommentUpdateException(_i('Unable to update the comment thread data.'));
        }

        return $this;
    }

    protected function p_setSticky($value = true)
    {
        return $this->setThreadData('sticky', $value);
    }

    protected function p_setLocked($value = true)
    {
        return $this->setThreadData('locked', $value);
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

            // check that the post isn't already in deleted
            $has_deleted = $this->dc->qb()
                ->select('COUNT(*) as found')
                ->from($this->radix->getTable('_deleted'), 'd')
                ->where('doc_id = :doc_id')
                ->setParameter(':doc_id', $this->comment->doc_id)
                ->execute()
                ->fetch();

            if (!$has_deleted['found']) {
                // throw into _deleted table
                $this->dc->getConnection()->executeUpdate(
                    'INSERT INTO '.$this->radix->getTable('_deleted').' '.
                    $this->dc->qb()
                        ->select('*')
                        ->from($this->radix->getTable(), 't')
                        ->where('doc_id = '.$this->dc->getConnection()->quote($this->comment->doc_id))
                        ->getSQL()
                );
            }

            // delete post
            $this->dc->qb()
                ->delete($this->radix->getTable())
                ->where('doc_id = :doc_id')
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
                $media_sql = $this->dc->qb()
                    ->select('COUNT(*)')
                    ->from($this->radix->getTable(), 't')
                    ->where('media_id = :media_id')
                    ->setParameter(':media_id', $this->media->media_id)
                    ->getSQL();

                $this->dc->qb()
                    ->update($this->radix->getTable('_images'))
                    ->set('total', '('.$media_sql.')')
                    ->where('media_id = :media_id')
                    ->setParameter(':media_id', $this->media->media_id)
                    ->execute();

                $has_image = $this->dc->qb()
                    ->select('total')
                    ->from($this->radix->getTable('_images'), 'ti')
                    ->where('media_id = :media_id')
                    ->setParameter(':media_id', $this->media->media_id)
                    ->execute()
                    ->fetch();

                if (!$has_image || !$has_image['total']) {
                    $media = new Media($this->getContext(), $this->bulk);
                    $media->delete();
                }
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
                    $post = new Comment($this->getContext(), $post);
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
                        ->set('nimages', ($this->media === null ? 'nimages' : 'nimages - 1'))
                        ->where('thread_num = :thread_num')
                        ->setParameter(':time', $this->getRadixTime())
                        ->setParameter(':thread_num', $this->comment->thread_num)
                        ->execute();
                }
            }

            $this->dc->getConnection()->commit();
            $this->clearCache();
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

    protected function clearCache()
    {
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

        return $this;
    }
}
