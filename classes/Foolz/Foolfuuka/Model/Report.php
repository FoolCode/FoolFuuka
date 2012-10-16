<?php

namespace Foolz\Foolfuuka\Model;

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
 * Thrown if the user sent too many reports in a timeframe
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
 * Manages Reports
 */
class Report extends \Model\Model_Base
{
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
	 * An array of preloaded reports
	 *
	 * @var  array|null
	 */
	protected static $preloaded = null;

	/**
	 * Creates a Report object from an associative array
	 *
	 * @param   array  $array  An associative array
	 * @return  \Foolz\Foolfuuka\Model\Report
	 */
	public static function fromArray(array $array)
	{
		$new = new static();
		foreach ($array as $key => $item)
		{
			$new->$key = $item;
		}

		$new->reason_processed = htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $new->reason));

		if ( ! isset($new->radix))
		{
			$new->radix = \Radix::get_by_id($new->board_id);
		}

		return $new;
	}

	/**
	 * Takes an array of associative arrays to create an array of Report
	 *
	 * @param   array  $array  An array of associative arrays, typically the result of a getAll
	 * @return  array  An array of Report
	 */
	public static function fromArrayDeep(array $array)
	{
		$result = array();

		foreach ($array as $item)
		{
			$result[] = static::fromArray($item);
		}

		return $result;
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
	 * Loads all the reports from the cache or the database
	 */
	public static function p_preload()
	{
		if (static::$preloaded !== null)
		{
			return;
		}

		try
		{
			static::$preloaded = \Cache::get('foolfuuka.model.report.preload.preloaded');
		}
		catch (\CacheNotFoundException $e)
		{
			static::$preloaded = \DC::qb()
				->select('*')
				->from('reports', 'r')
				->execute()
				->fetchAll();

			\Cache::set('foolfuuka.model.report.preload.preloaded', static::$preloaded, 1800);
		}
	}

	/**
	 * Clears the cached objects for the entire class
	 */
	public static function p_clearCache()
	{
		static::$preloaded = null;
		\Cache::delete('foolfuuka.model.report.preload.preloaded');
	}

	/**
	 * Returns an array of Reports by a comment's doc_id
	 *
	 * @param   \Foolz\Foolfuuka\Model\Radix  $board  The Radix on which the Comment resides
	 * @param   int  $doc_id  The doc_id of the Comment
	 *
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Report
	 */
	public static function getByDocId($radix, $doc_id)
	{
		static::preload();
		$result = array();

		foreach(static::$preloaded as $item)
		{
			if ($item['board_id'] === $radix->id && $item['doc_id'] === $doc_id)
			{
				$result[] = $item;
			}
		}

		return static::fromArrayDeep($result);
	}

	/**
	 * Returns an array of Reports by a Media's media_id
	 *
	 * @param   \Foolz\Foolfuuka\Model\Radix  $board  The Radix on which the Comment resides
	 * @param   int  $media_id  The media_id of the Media
	 *
	 * @return  array  An array of \Foolz\Foolfuuka\Model\Report
	 */
	public static function getByMediaId($radix, $media_id)
	{
		static::preload();
		$result = array();

		foreach(static::$preloaded as $item)
		{
			if ($item['board_id'] === $radix->id && $item['media_id'] === $media_id)
			{
				$result[] = $item;
			}
		}

		return static::fromArrayDeep($result);
	}

	/**
	 * Fetches and returns all the Reports
	 *
	 * @return  array  An array of Report
	 */
	public static function getAll()
	{
		static::preload();
		return static::fromArrayDeep(static::$preloaded);
	}

	/**
	 * Returns the number of Reports
	 *
	 * @return  int  The number of Report
	 */
	public static function count()
	{
		static::preload();
		return count(static::$preloaded);
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
	 * @throws  ReportSentTooManyException      If the user sent too many reports in a timeframe
	 */
	public static function p_add($radix, $id, $reason, $ip_reporter = null, $mode = 'doc_id')
	{
		$new = new static();
		$new->radix = $radix;
		$new->board_id = $radix->id;

		if ($mode === 'media_id')
		{
			try
			{
				Media::get_by_media_id($new->radix, $id);
			}
			catch (MediaNotFoundException $e)
			{
				throw new ReportMediaNotFoundException(__('The media you are reporting could not be found.'));
			}

			$new->media_id = (int) $id;
		}
		else
		{
			try
			{
				Board::forge()
					->get_post()
					->set_radix($new->radix)
					->set_options('doc_id', $id)
					->get_comments();
			}
			catch (BoardException $e)
			{
				throw new ReportCommentNotFoundException(__('The report you are reporting could not be found.'));
			}

			$new->doc_id =  (int) $id;
		}

		if (mb_strlen($reason) > 10000)
		{
			throw new ReportReasonTooLongException(__('Your report reason was too long.'));
		}

		$new->reason = $reason;

		if ($ip_reporter === null)
		{
			$ip_reporter = \Input::ip_decimal();
		}
		$new->ip_reporter = $ip_reporter;

		// check how many reports have been sent in the last hour to prevent spam
		$row = \DC::qb()
			->select('COUNT(*) as count')
			->from('reports', 'r')
			->where('created > :time')
			->setParameter(':time', time() - 86400)
			->execute()
			->fetch();


		if ($row['count'] > 25)
		{
			throw new ReportSentTooManyException(__('You sent too many reports in a hour.'));
		}

		$new->created = time();

		\DC::forge()->insert('reports', array(
			'board_id' => $new->board_id,
			'doc_id' => $new->doc_id,
			'media_id' => $new->media_id,
			'reason' => $new->reason,
			'ip_reporter' => $new->ip_reporter,
			'created' => $new->created,
		));

		static::clearCache();

		return $new;
	}

	/**
	 * Deletes a Report
	 *
	 * @param   int  $id  The ID of the Report
	 *
	 * @throws  \Foolz\Foolfuuka\Model\ReportNotFoundException
	 */
	public static function p_delete($id)
	{
		\DC::qb()
			->delete('reports')
			->where('id', ':id')
			->setParameter(':id', $id)
			->execute();

		static::clearCache();
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

		if ($this->media_id !== null)
		{
			// custom "get the first doc_id with the media"
			$doc_id_res = \DC::dq()
				->select('doc_id')
				->where('media_id = :media_id')
				->orderBy('timestamp', 'desc')
				->setParameter('media_id', $this->media_id)
				->execute()
				->fetch();

			if ($doc_id_res !== null)
			{
				$this->doc_id = $doc_id_res->doc_id;
			}
			else
			{
				throw new ReportMediaNotFoundException(__('The report you are managing could not be found.'));
			}
		}

		try
		{
			$comments = Board::forge()->get_post()
				->set_radix($this->radix)
				->set_options('doc_id', $this->doc_id)
				->get_comments();
			$this->comment = current($comments);
		}
		catch (BoardException $e)
		{
			throw new ReportCommentNotFoundException(__('The report you are managing could not be found.'));
		}

		return $this->comment;
	}

}