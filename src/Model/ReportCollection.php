<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Cache\Cache;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Uri;
use Foolz\Plugin\PlugSuit;

class ReportCollection extends Model
{

    use PlugSuit;

    /**
     * An array of preloaded moderation
     *
     * @var  array|null
     */
    protected $preloaded = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    /**
     * @var BanFactory
     */
    protected $ban_factory;

    public function __construct(\Foolz\Foolframe\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->uri = $context->getService('uri');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->media_factory = $context->getService('foolfuuka.media_factory');
        $this->ban_factory = $context->getService('foolfuuka.ban_factory');

        $this->preload();
    }

    /**
     * Creates a Report object from an associative array
     *
     * @param   array  $array  An associative array
     * @return  \Foolz\Foolfuuka\Model\Report
     */
    public function fromArray(array $array)
    {
        $new = new Report($this->getContext());
        foreach ($array as $key => $item) {
            $new->$key = $item;
        }

        $new->reason_processed = htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $new->reason));

        if (!isset($new->radix)) {
            $new->radix = $this->radix_coll->getById($new->board_id);
        }

        return $new;
    }

    /**
     * Takes an array of associative arrays to create an array of Report
     *
     * @param   array  $array  An array of associative arrays, typically the result of a getAll
     * @return  array  An array of Report
     */
    public function fromArrayDeep(array $array)
    {
        $result = [];

        foreach ($array as $item) {
            $result[] = $this->fromArray($item);
        }

        return $result;
    }

    /**
     * Loads all the moderation from the cache or the database
     */
    public function p_preload()
    {
        if ($this->preloaded !== null) {
            return;
        }

        try {
            $this->preloaded = Cache::item('foolfuuka.model.report.preload.preloaded')->get();
        } catch (\OutOfBoundsException $e) {
            $this->preloaded = $this->dc->qb()
                ->select('*')
                ->from($this->dc->p('reports'), 'r')
                ->execute()
                ->fetchAll();

            Cache::item('foolfuuka.model.report.preload.preloaded')->set($this->preloaded, 1800);
        }
    }

    /**
     * Clears the cached objects for the entire class
     */
    public function p_clearCache()
    {
        $this->preloaded = null;
        Cache::item('foolfuuka.model.report.preload.preloaded')->delete();
    }

    /**
     * Returns an array of Reports by a comment's doc_id
     *
     * @param   \Foolz\Foolfuuka\Model\Radix  $board  The Radix on which the Comment resides
     * @param   int  $doc_id  The doc_id of the Comment
     *
     * @return  array  An array of \Foolz\Foolfuuka\Model\Report
     */
    public function getByDocId($radix, $doc_id)
    {
        $this->preload();
        $result = [];

        foreach($this->preloaded as $item) {
            if ($item['board_id'] === $radix->id && $item['doc_id'] === $doc_id) {
                $result[] = $item;
            }
        }

        return $this->fromArrayDeep($result);
    }

    /**
     * Returns an array of Reports by a Media's media_id
     *
     * @param   \Foolz\Foolfuuka\Model\Radix  $board  The Radix on which the Comment resides
     * @param   int  $media_id  The media_id of the Media
     *
     * @return  array  An array of \Foolz\Foolfuuka\Model\Report
     */
    public function getByMediaId($radix, $media_id)
    {
        $this->preload();
        $result = [];

        foreach ($this->preloaded as $item) {
            if ($item['board_id'] === $radix->id && $item['media_id'] === $media_id) {
                $result[] = $item;
            }
        }

        return $this->fromArrayDeep($result);
    }

    /**
     * Fetches and returns all the Reports
     *
     * @return  array  An array of Report
     */
    public function getAll()
    {
        $this->preload();

        return $this->fromArrayDeep($this->preloaded);
    }

    /**
     * Returns the number of Reports
     *
     * @return  int  The number of Report
     */
    public function count()
    {
        $this->preload();

        return count($this->preloaded);
    }

    /**
     * Adds a new report to the database
     *
     * @param   \Foolz\Foolfuuka\Model\Radix  $radix  The Radix to which the Report is referred to
     * @param   int     $id           The ID of the object being reported (doc_id or media_id)
     * @param   string  $reason       The reason for the report
     * @param   string  $ip_reporter  The IP in decimal format
     * @param   string  $mode         The type of column (doc_id or media_id)
     *
     * @return  \Foolz\Foolfuuka\Model\Report   The created report
     * @throws  ReportMediaNotFoundException    If the reported media_id doesn't exist
     * @throws  ReportCommentNotFoundException  If the reported doc_id doesn't exist
     * @throws  ReportReasonTooLongException    If the reason inserted was too long
     * @throws  ReportSentTooManyException      If the user sent too many moderation in a timeframe
     * @throws  ReportReasonNullException       If the report reason is null
     * @throws  ReportAlreadySubmittedException If the reporter’s IP has already submitted a report for the post.
     * @throws  ReportSubmitterBannedException  If the reporter’s IP has been banned.
     */
    public function p_add($radix, $id, $reason, $ip_reporter, $mode = 'doc_id')
    {
        $new = new Report($this->getContext());
        $new->radix = $radix;
        $new->board_id = $radix->id;

        if ($mode === 'media_id') {
            try {
                $this->media_factory->getByMediaId($new->radix, $id);
            } catch (MediaNotFoundException $e) {
                throw new ReportMediaNotFoundException(_i('The media file you are reporting could not be found.'));
            }

            $new->media_id = (int) $id;
        } else {
            try {
                Board::forge($this->getContext())
                    ->getPost()
                    ->setRadix($new->radix)
                    ->setOptions('doc_id', $id)
                    ->getComments();
            } catch (BoardException $e) {
                throw new ReportCommentNotFoundException(_i('The post you are reporting could not be found.'));
            }

            $new->doc_id =  (int) $id;
        }

        if (trim($reason) === null) {
            throw new ReportReasonNullException(_i('A reason must be included with your report.'));
        }

        if (mb_strlen($reason, 'utf-8') > 2048) {
            throw new ReportReasonTooLongException(_i('The reason for you report was too long.'));
        }

        $new->reason = $reason;
        $new->ip_reporter = $ip_reporter;

        // check how many moderation have been sent in the last hour to prevent spam
        $row = $this->dc->qb()
            ->select('COUNT(*) as count')
            ->from($this->dc->p('reports'), 'r')
            ->where('created > :time')
            ->andWhere('ip_reporter = :ip_reporter')
            ->setParameter(':time', time() - 86400)
            ->setParameter(':ip_reporter', $new->ip_reporter)
            ->execute()
            ->fetch();

        if ($row['count'] > 25) {
            throw new ReportSentTooManyException(_i('You have submitted too many reports within an hour.'));
        }

        $reported = $this->dc->qb()
            ->select('COUNT(*) as count')
            ->from($this->dc->p('reports'), 'r')
            ->where('board_id = :board_id')
            ->andWhere('ip_reporter = :ip_reporter')
            ->andWhere('doc_id = :doc_id')
        //    ->orWhere('media_id = :media_id')
            ->setParameters([
                ':board_id' => $new->board_id,
                ':doc_id' => $new->doc_id,
        //        ':media_id' => $new->media_id,
                ':ip_reporter' => $new->ip_reporter
            ])
            ->execute()
            ->fetch();

        if ($reported['count'] > 0) {
            throw new ReportSubmitterBannedException(_i('You can only submit one report per post.'));
        }

        if ($ban = $this->ban_factory->isBanned($new->ip_reporter, $new->radix)) {
            if ($ban->board_id == 0) {
                $banned_string = _i('It looks like you were banned on all boards.');
            } else {
                $banned_string = _i('It looks like you were banned on /'.$new->radix->shortname.'/.');
            }

            if ($ban->length) {
                $banned_string .= ' '._i('This ban will last until:').' '.date(DATE_COOKIE, $ban->start + $ban->length).'.';
            } else {
                $banned_string .= ' '._i('This ban will last forever.');
            }

            if ($ban->reason) {
                $banned_string .= ' '._i('The reason for this ban is:').' «'.$ban->reason.'».';
            }

            if ($ban->appeal_status == Ban::APPEAL_NONE) {
                $banned_string .= ' '._i('If you\'d like to appeal to your ban, go to the :appeal page.',
                        '<a href="'.$this->uri->create($new->radix->shortname.'/appeal').'">'._i('appeal').'</a>');
            } elseif ($ban->appeal_status == Ban::APPEAL_PENDING) {
                $banned_string .= ' '._i('Your appeal is pending.');
            }

            throw new ReportSubmitterBannedException($banned_string);
        }

        $new->created = time();

        $this->dc->getConnection()->insert($this->dc->p('reports'), [
            'board_id' => $new->board_id,
            'doc_id' => $new->doc_id,
            'media_id' => $new->media_id,
            'reason' => $new->reason,
            'ip_reporter' => $new->ip_reporter,
            'created' => $new->created,
        ]);

        $this->clearCache();

        return $new;
    }

    /**
     * Deletes a Report
     *
     * @param   int  $id  The ID of the Report
     *
     * @throws  \Foolz\Foolfuuka\Model\ReportNotFoundException
     */
    public function p_delete($id)
    {
        $this->dc->qb()
            ->delete($this->dc->p('reports'))
            ->where('id = :id')
            ->setParameter(':id', $id)
            ->execute();

        $this->clearCache();
    }
}
