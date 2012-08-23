<?php
namespace Foolfuuka\Model;

class ReportException extends \FuelException {}
class ReportNotFoundException extends ReportException {}
class ReportReasonTooLongException extends ReportException {}
class ReportSentTooManyException extends ReportException {}
class ReportCommentNotFoundException extends ReportException {}

class Report extends \Model\Model_Base
{	
	public $id = null;
	public $board_id = null;
	public $doc_id = null;
	public $reason = null;
	public $ip_reporter = null;
	public $created = null;
	
	public $reason_processed = null;
	
	public $board = null;
	public $comment = null;
	
	protected static $_preloaded = null;
	
	
	public static function forge($obj)
	{
		if (is_array($obj))
		{
			$result = array();
			
			foreach ($obj as $item)
			{
				$result[] = static::forge($item);
			}
			
			return $result;
		}
		
		$new = new static();
		foreach ($obj as $key => $item)
		{
			$new->$key = $item;
		}
		
		$new->reason_processed = e(@iconv('UTF-8', 'UTF-8//IGNORE', $new->reason));
		
		if ( ! isset($new->board))
		{
			$new->board = \Radix::get_by_id($new->board_id);
		}
		
		return $new;
	}
	
	
	public static function p_preload()
	{
		if (static::$_preloaded !== null)
		{
			return true;
		}
		
		try
		{
			static::$_preloaded = \Cache::get('foolfuuka.model.report.preload.preloaded');
		}
		catch (\CacheNotFoundException $e)
		{
			static::$_preloaded = \DB::select()
				->from('reports')
				->as_object()
				->execute()
				->as_array('id');
			
			\Cache::set('foolfuuka.model.report.preload.preloaded', static::$_preloaded, 1800);
		}
	}
	
	
	public static function p_clear_cache()
	{
		static::$_preloaded = null;
		\Cache::delete('foolfuuka.model.report.preload.preloaded');
	}
	
	
	public static function get_by_id($id)
	{
		return static::get_by('id', $id);
	}
	
	
	public static function get_by_doc_id($board, $doc_id)
	{
		return static::get_by('doc_id', $board, $doc_id);
	}
	
	
	protected static function p_get_by($by, $first, $second = null)
	{
		static::preload();
		
		$result = array();
		
		switch ($by)
		{
			case 'id':
				if (isset(static::$_preloaded['id']))
				{
					$result[] = static::$_preloaded['id'];
				}
				break;
			case 'doc_id':
				foreach(static::$_preloaded as $item)
				{
					if ($item->board_id === $first->id && $item->doc_id === $second)
					{
						$result[] = $item;
					}
				}
				break;
		}
		
		if ($result === null)
		{
			throw new ReportNotFoundException(__('The report could not be found.'));
		}
		
		return static::forge($result);
	}
	
	
	public static function get_all()
	{
		static::preload();
		
		return static::forge(static::$_preloaded);
	}
	
	public static function count()
	{
		return count(static::$_preloaded);
	}
	
	
	public static function p_add(&$board, $doc_id, $reason, $ip_reporter = null)
	{
		$new = new static();
		$new->board =& $board;
		$new->board_id = $board->id;
		
		try
		{
			Board::forge()->get_post()->set_radix($new->board)->set_options('doc_id', $doc_id)->get_comments();
		}
		catch (BoardException $e)
		{
			throw new ReportCommentNotFoundException(__('The report you are reporting could not be found.'));
		}
		
		$new->doc_id =  (int) $doc_id;

			
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
		$count = \DB::select(\DB::expr('COUNT(*) as count'))
			->from('reports')
			->where('created', '>', time() - 86400)
			->as_object()
			->execute()
			->current()->count;
		
		if ($count > 25)
		{
			throw new ReportSentTooManyException(__('You sent too many reports in a hour.'));
		}
		
		$new->created = time();
		
		\DB::insert('reports')
			->set(array(
				'board_id' => $new->board_id,
				'doc_id' => $new->doc_id,
				'reason' => $new->reason,
				'ip_reporter' => $new->ip_reporter,
				'created' => $new->created,
			))
			->execute();
		
		static::clear_cache();
		
		return $new;
	}
	
	
	public static function p_delete($id)
	{
		$count = \DB::delete('reports')
			->where('id', $id)
			->execute();
		
		if ( ! $count)
		{
			throw new ReportNotFoundException(__('The report could not be found in the database to be deleted.'));
		}
		
		static::clear_cache();
	}
	
	
	public function p_get_comment()
	{
		try
		{
			$comments = Board::forge()->get_post()
				->set_radix($this->board)
				->set_options('doc_id', $this->doc_id)
				->get_comments();
			$this->comment = $comments[0];
		}
		catch (BoardException $e)
		{
			throw new ReportCommentNotFoundException(__('The report you are reporting could not be found.'));
		}
		
		return $this->comment;
	}
		
}