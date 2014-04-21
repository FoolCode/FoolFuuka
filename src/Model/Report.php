<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Plugin\PlugSuit;

/**
 * Generic exception for Report
 */
class ReportException extends \Exception {}

/**
 * Thrown if the exception is not found
 */
class ReportNotFoundException extends ReportException {}

/**
 * Thrown if there's too many character in the reason
 */
class ReportReasonTooLongException extends ReportException {}

/**
 * Thrown if the user sent too many moderation in a timeframe
 */
class ReportSentTooManyException extends ReportException {}

/**
 * Thrown if the comment the user was reporting wasn't found
 */
class ReportCommentNotFoundException extends ReportException {}

/**
 * Thrown if the media the user was reporting wasn't found
 */
class ReportMediaNotFoundException extends ReportException {}

/**
 * Thrown if the report reason is null.
 */
class ReportReasonNullException extends ReportException {}

/**
 * Thrown if the media reporter’s IP has already submitted a report for that post.
 */
class ReportAlreadySubmittedException extends ReportException {}

/**
 * Thrown if the reporter’s IP has been banned.
 */
class ReportSubmitterBannedException extends ReportException {}

/**
 * Manages Reports
 */
class Report extends Model
{

    use PlugSuit;

    /**
     * Autoincremented ID
     *
     * @var  int
     */
    public $id = null;

    /**
     * The ID of the Radix
     *
     * @var  int
     */
    public $board_id = null;

    /**
     * The ID of the Comment
     *
     * @var  int|null  Null if it's not a Comment being reported
     */
    public $doc_id = null;

    /**
     * The ID of the Media
     *
     * @var  int|null  Null if it's not a Media being reported
     */
    public $media_id = null;

    /**
     * The explanation of the report
     *
     * @var  string|null
     */
    public $reason = null;

    /**
     * The IP of the reporter in decimal format
     *
     * @var  string|null
     */
    public $ip_reporter = null;

    /**
     * Creation time in UNIX time
     *
     * @var  int|null
     */
    public $created = null;

    /**
     * The reason escaped for safe echoing in the HTML
     *
     * @var  string|null
     */
    public $reason_processed = null;

    /**
     * The Radix object
     *
     * @var  \Foolz\Foolfuuka\Model\Radix|null
     */
    public $radix = null;

    /**
     * The Comment object
     *
     * @var  \Foolz\Foolfuuka\Model\Comment|null
     */
    public $comment = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    public function __construct(\Foolz\Foolframe\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
    }

    /**
     * Returns the reason escaped for HTML output
     *
     * @return  string
     */
    public function getReasonProcessed()
    {
        return $this->reason_processed;
    }

    /**
     * Returns the Comment by doc_id or the first Comment found with a matching media_id
     *
     * @return  \Foolz\Foolfuuka\Model\Comment
     * @throws  \Foolz\Foolfuuka\Model\ReportMediaNotFoundException
     * @throws  \Foolz\Foolfuuka\Model\ReportCommentNotFoundException
     */
    public function p_getComment()
    {
        if ($this->media_id !== null) {
            // custom "get the first doc_id with the media"
            $doc_id_res = $this->dc->qb()
                ->select('doc_id')
                ->from($this->radix_coll->getById($this->board_id)->getTable(), 'a')
                ->where('media_id = :media_id')
                ->orderBy('timestamp', 'desc')
                ->setParameter('media_id', $this->media_id)
                ->execute()
                ->fetch();

            if ($doc_id_res !== null) {
                $this->doc_id = $doc_id_res->doc_id;
            } else {
                throw new ReportMediaNotFoundException(_i('The reported media file could not be found.'));
            }
        }

        try {
            $comments = Board::forge($this->getContext())
                ->getPost()
                ->setRadix($this->radix)
                ->setOptions('doc_id', $this->doc_id)
                ->getComments();
            $this->comment = current($comments);
        } catch (BoardException $e) {
            throw new ReportCommentNotFoundException(_i('The reported post could not be found.'));
        }

        return $this->comment;
    }
}
