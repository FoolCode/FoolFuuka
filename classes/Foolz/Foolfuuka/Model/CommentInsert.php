<?php

namespace Foolz\Foolfuuka\Model;

class CommentSendingException extends \Exception {}
class CommentSendingDuplicateException extends CommentSendingException {}
class CommentSendingThreadWithoutMediaException extends CommentSendingException {}
class CommentSendingUnallowedCapcodeException extends CommentSendingException {}
class CommentSendingNoDelPassException extends CommentSendingException {}
class CommentSendingDisplaysEmptyException extends CommentSendingException {}
class CommentSendingTooManyLinesException extends CommentSendingException {}
class CommentSendingTooManyCharactersException extends CommentSendingException {}
class CommentSendingSpamException extends CommentSendingException {}
class CommentSendingTimeLimitException extends CommentSendingException {}
class CommentSendingSameCommentException extends CommentSendingException {}
class CommentSendingImageInGhostException extends CommentSendingException {}
class CommentSendingBannedException extends CommentSendingException {}
class CommentSendingRequestCaptchaException extends CommentSendingException {}
class CommentSendingWrongCaptchaException extends CommentSendingException {}
class CommentSendingThreadClosedException extends CommentSendingException {}
class CommentSendingDatabaseException extends CommentSendingException {}

class CommentInsert extends Comment
{
	protected function insertTriggerDaily($is_retry = false)
	{
		\DC::forge()->beginTransaction();
		$item = [
			'day' => floor(($this->timestamp/86400)*86400),
			'images' => (int) ($this->media !== null),
			'sage' => (int) ($this->email === 'sage'),
			'anons' => (int) ($this->name === $this->radix->anonymous_default_name && $this->trip === null),
			'trips' => (int) ($this->trip !== null),
			'names' => (int) ($this->name !== $this->radix->anonymous_default_name || $this->trip !== null)
		];

		$result = \DC::qb()
			->select('*')
			->from($this->radix->getTable('_daily'), 'd')
			->where('day = :day')
			->setParameter(':day', $item['day'])
			->execute()
			->fetch();

		if ($result === false)
		{
			try
			{
				\DC::forge()->insert($this->radix->getTable('_daily'), $item);
			}
			catch(\Doctrine\DBAL\DBALException $e)
			{
				if ( ! $is_retry)
				{
					// maybe we're trying to insert on something just inserted
					return $this->insertTriggerDaily(true);
				}
			}
		}
		else
		{
			\DC::qb()
				->update($this->radix->getTable('_daily'))
				->set('images', 'images + :images')
				->set('sage', 'sage + :sage')
				->set('anons', 'anons + :anons')
				->set('trips', 'trips + :trips')
				->set('names', 'names + :names')
				->where('day = :day')
				->setParameter(':day', $item['day'])
				->setParameter(':images', $item['images'])
				->setParameter(':sage', $item['sage'])
				->setParameter(':anons', $item['anons'])
				->setParameter(':trips', $item['trips'])
				->setParameter(':names', $item['names'])
				->execute();
		}
		\DC::forge()->commit();
	}

	protected function insertTriggerUsers($is_retry = false)
	{
		\DC::forge()->beginTransaction();

		$select = \DC::qb()
			->select('*')
			->from($this->radix->getTable('_users'), 'u');

		if ($this->trip !== null)
		{
			$select->where('trip = :trip')
				->setParameter(':trip', $this->trip);
		}
		else
		{
			$select->where('name = :name')
				->setParameter(':name', $this->name);
		}

		$result = $select
			->execute()
			->fetch();

		if ($result === false)
		{
			try
			{
				\DC::forge()->insert($this->radix->getTable('_users'), [
					'name' => (string) $this->name,
					'trip' => (string) $this->trip,
					'firstseen' => $this->timestamp,
					'postcount' => 1
				]);
			}
			catch (\Doctrine\DBAL\DBALException $e)
			{
				if ( ! $is_retry)
				{
					return $this->insertTriggerUsers(true);
				}
			}
		}
		else
		{
			\DC::qb()
				->update($this->radix->getTable('_users'))
				->set('postcount', 'postcount + 1')
				->set('firstseen', ':firstseen')
				->where('user_id = :user_id')
				->setParameter(':firstseen',
					$result['firstseen'] > $this->timestamp ? $result['firstseen'] : $this->timestamp)
				->setParameter(':user_id', $result['user_id'])
				->execute();
		}

		\DC::forge()->commit();
	}

	protected function insertTriggerThreads($is_retry = false)
	{
		\DC::forge()->beginTransaction();
		if ($this->op)
		{
			\DC::forge()->insert($this->radix->getTable('_threads'), [
				'thread_num' => $this->num,
				'time_op' => $this->timestamp,
				'time_last' => $this->timestamp,
				'time_bump' => $this->timestamp,
				'time_ghost' => null,
				'time_ghost_bump' => null,
				'nreplies' => 1,
				'nimages' => ($this->media->media_id ? 1 : 0)
			]);
		}
		else
		{
			if ( ! $this->subnum)
			{
				$query = \DC::qb()
					->update($this->radix->getTable('_threads'))
					->set('time_last', 'GREATEST(time_last, :time_last)')
					->set('nreplies', 'nreplies + 1')
					->set('nimages', 'nimages + '. ($this->media->media_id ? 1 : 0))
					->setParameter(':time_last', $this->timestamp);

				if ($this->email !== 'sage')
				{
					$query
						->set('time_bump', 'GREATEST(time_bump, :time_bump)')
						->setParameter(':time_bump', $this->timestamp);
				}

			}
			else
			{
				$query = \DC::qb()
					->update($this->radix->getTable('_threads'))
					->set('time_ghost', 'COALESCE(time_ghost, :time_ghost)')
					->set('nreplies', 'nreplies + 1')
					->setParameter(':time_ghost', $this->timestamp);

				if ($this->email !== 'sage')
				{
					$query
						->set('time_ghost_bump', 'GREATEST(COALESCE(time_ghost_bump, 0), :time_ghost_bump)')
						->setParameter(':time_ghost_bump', $this->timestamp);
				}
			}

			$query
				->where('thread_num = :thread_num')
				->setParameter(':thread_num', $this->thread_num)
				->execute();
		}
		\DC::forge()->commit();
	}

	/**
	 * Send the comment and attached media to database
	 *
	 * @param object $board
	 * @param array $data the comment data
	 * @param array $options modifiers
	 * @return array error key with explanation to show to user, or success and post row
	 */
	protected function p_insert()
	{
		$this->ghost = false;
		$this->allow_media = true;

		// some users don't need to be limited, in here go all the ban and posting limitators
		if( ! \Auth::has_access('comment.limitless_comment'))
		{
			// check if the user is banned
			if ($ban = \Ban::isBanned(\Input::ip_decimal(), $this->radix))
			{
				if ($ban->board_id == 0)
				{
					$banned_string = __('It looks like you were banned on all boards.');
				}
				else
				{
					$banned_string = __('It looks like you were banned on /'.$this->radix->shortname.'/.');
				}

				if ($ban->length)
				{
					$banned_string .= ' '.__('This ban will last until:').' '.date(DATE_COOKIE, $ban->start + $ban->length).'.';
				}
				else
				{
					$banned_string .= ' '.__('This ban will last forever.');
				}

				if ($ban->reason)
				{
					$banned_string .= ' '.__('The reason for this ban is:').' «'.$ban->reason.'».';
				}

				if ($ban->appeal_status == \Ban::APPEAL_NONE)
				{
					$banned_string .= ' '.\Str::tr(__('If you\'d like to appeal to your ban, go to the :appeal page.'),
						array('appeal' => '<a href="'.\Uri::create($this->radix->shortname.'/appeal').'">'.__('appeal').'</a>'));
				}
				else if ($ban->appeal_status == \Ban::APPEAL_PENDING)
				{
					$banned_string .= ' '.__('Your appeal is pending.');
				}

				throw new CommentSendingBannedException($banned_string);
			}
		}


		// check if it's a thread and its status
		if ($this->thread_num > 0)
		{
			try
			{
				$thread = Board::forge()->get_thread($this->thread_num)->set_radix($this->radix);
				$thread->get_comments();
				$status = $thread->check_thread_status();
			}
			catch (BoardException $e)
			{
				throw new CommentSendingException($e->getMessage());
			}

			if ($status['closed'])
			{
				throw new CommentSendingThreadClosedException(__('The thread is closed.'));
			}

			$this->ghost = $status['dead'];
			$this->allow_media = ! $status['disable_image_upload'];
		}

		foreach(array('name', 'email', 'title', 'delpass', 'comment', 'capcode') as $key)
		{
			$this->$key = trim((string) $this->$key);
		}

		// some users don't need to be limited, in here go all the ban and posting limitators
		if( ! \Auth::has_access('comment.limitless_comment'))
		{
			if ($this->thread_num < 1)
			{
				// one can create a new thread only once every 5 minutes
				$check_op = \DB::select()
					->from(\DB::expr($this->radix->getTable()))
					->where('poster_ip', \Input::ip_decimal())
					->where('timestamp', '>', time() - 300)
					->where('op', 1)
					->limit(1)
					->execute();

				if(count($check_op))
				{
					throw new CommentSendingTimeLimitException(__('You must wait up to 5 minutes to make another new thread.'));
				}
			}

			// check the latest posts by the user to see if he's posting the same message or if he's posting too fast
			$check = \DB::select()
				->from(\DB::expr($this->radix->getTable()))
				->where('poster_ip', \Input::ip_decimal())
				->order_by('timestamp', 'desc')
				->limit(1)
				->as_object()
				->execute();

			if (count($check))
			{
				$row = $check->current();

				if ($this->comment !== null && $row->comment === $this->comment)
				{
					throw new CommentSendingSameCommentException(__('You\'re sending the same comment as the last time'));
				}

				$check_time = time();

				if ($this->radix->archive)
				{
					// archives are in new york time
					$newyork = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('America/New_York'));
					$utc = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('UTC'));
					$diff = $newyork->diff($utc)->h;
					$check_time = $check_time - ($diff * 60 * 60);
				}

				if ($check_time - $row->timestamp < 10 && $check_time - $row->timestamp > 0)
				{
					throw new CommentSendingTimeLimitException(__('You must wait up to 10 seconds to post again.'));
				}
			}

			// we want to know if the comment will display empty, and in case we won't let it pass
			$comment_parsed = $this->process_comment();
			if($this->comment !== '' && $comment_parsed === '')
			{
				throw new CommentSendingDisplaysEmptyException(__('This comment would display empty.'));
			}

			// clean up to reset eventual auto-built entries
			foreach ($this->_forced_entries as $field)
			{
				unset($this->$field);
			}

			if ($this->recaptcha_challenge && $this->recaptcha_response && \ReCaptcha::available())
			{
				$recaptcha = \ReCaptcha::instance()
					->check_answer(\Input::ip(), $this->recaptcha_challenge, $this->recaptcha_response);

				if ( ! $recaptcha)
				{
					throw new CommentSendingWrongCaptchaException(__('Incorrect CAPTCHA solution.'));
				}
			}
			else // if there wasn't a recaptcha input, let's go with heavier checks
			{
				// 3+ links is suspect
				if (substr_count($this->comment, 'http') > 2)
				{
					throw new CommentSendingRequestCaptchaException;
				}

				// bots usually fill all the fields
				if ($this->comment && $this->title && $this->email)
				{
					throw new CommentSendingRequestCaptchaException;
				}

				// bots usually try various BBC, this checks if there's unparsed BBC after parsing it
				if ($comment_parsed !== '' && substr_count($comment_parsed, '[') + substr_count($comment_parsed, ']') > 4)
				{
					throw new CommentSendingRequestCaptchaException;
				}
			}

			// load the spam list and check comment, name, title and email
			$spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents(DOCROOT.'assets/anti-spam/databases')));
			foreach($spam as $s)
			{
				if(strpos($this->comment, $s) !== false || strpos($this->name, $s) !== false
					|| strpos($this->title, $s) !== false || strpos($this->email, $s) !== false)
				{
					throw new CommentSendingSpamException(__('Your post has undesidered content.'));
				}
			}

			// check entire length of comment
			if (mb_strlen($this->comment) > 4096)
			{
				throw new CommentSendingTooManyCharactersException(__('Your comment has too many characters'));
			}

			// check total numbers of lines in comment
			if (count(explode("\n", $this->comment)) > 20)
			{
				throw new CommentSendingTooManyLinesException(__('Your comment has too many lines.'));
			}
		}

		\Foolz\Plugin\Hook::forge('fu.comment.insert.alter_input_after_checks')
			->setObject($this)
			->execute();

		// process comment name+trip
		if ($this->name === '')
		{
			$this->name = $this->radix->anonymous_default_name;
			$this->trip = null;
		}
		else
		{
			$this->process_name();
			if ($this->trip === '')
			{
				$this->trip = null;
			}
		}

		foreach(array('email', 'title', 'delpass', 'comment') as $key)
		{
			if ($this->$key === '')
			{
				$this->$key = null;
			}
		}

		// process comment password
		if ($this->delpass === '')
		{
			throw new CommentSendingNoDelPassException(__('You must submit a deletion password.'));
		}

		if ( ! class_exists('PHPSecLib\\Crypt_Hash', false))
		{
			import('phpseclib/Crypt/Hash', 'vendor');
		}

		$hasher = new \PHPSecLib\Crypt_Hash();
		$this->delpass = base64_encode($hasher->pbkdf2($this->delpass, \Config::get('auth.salt'), 10000, 32));

		if ($this->capcode != '')
		{
			$allowed_capcodes = array('N');

			if(\Auth::has_access('comment.mod_capcode'))
			{
				$allowed_capcodes[] = 'M';
			}

			if(\Auth::has_access('comment.admin_capcode'))
			{
				$allowed_capcodes[] = 'A';
			}

			if(\Auth::has_access('comment.dev_capcode'))
			{
				$allowed_capcodes[] = 'D';
			}

			if(!in_array($this->capcode, $allowed_capcodes))
			{
				throw new CommentSendingUnallowedCapcodeException(__('You\'re not allowed to use this capcode.'));
			}
		}
		else
		{
			$this->capcode = 'N';
		}

		$microtime = str_replace('.', '', (string) microtime(true));
		$this->timestamp = substr($microtime, 0, 10);
		$this->op = (bool) ! $this->thread_num;

		if ($this->poster_ip === null)
		{
			$this->poster_ip = \Input::ip_decimal();
		}

		if ($this->radix->enable_flags && function_exists('\\geoip_country_code_by_name'))
		{
			$this->poster_country = \geoip_country_code_by_name(\Inet::dtop($this->poster_ip));
		}

		// process comment media
		if ($this->media !== null)
		{
			if ( ! $this->allow_media)
			{
				throw new CommentSendingImageInGhostException(__('You can\'t post images when the thread is in ghost mode.'));
			}

			try
			{
				$this->media->insert($microtime, $this->op);
			}
			catch (MediaInsertException $e)
			{
				throw new CommentSendingException($e->getMessage());
			}
		}
		else
		{
			// if no media is present and post is op, stop processing
			if (!$this->thread_num)
			{
				throw new CommentSendingThreadWithoutMediaException(__('You can\'t start a new thread without an image.'));
			}

			// in case of no media, check comment field again for null
			if ($this->comment === null)
			{
				throw new CommentSendingDisplaysEmptyException(__('This comment would display empty.'));
			}

			$this->media = Media::forge_empty($this->radix);
		}

		// 2ch-style codes, only if enabled
		if ($this->thread_num && $this->radix->enable_poster_hash)
		{
			$this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$this->thread_num),'id'),+3), 0, 8);
		}

		if ($this->radix->archive)
		{
			// archives are in new york time
			$newyork = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('America/New_York'));
			$utc = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$this->timestamp = $this->timestamp - ($diff * 60 * 60);
		}

		\Foolz\Plugin\Hook::forge('fu.comment.insert.alter_input_before_sql')
			->setObject($this)
			->execute();

		// being processing insert...

		if($this->ghost)
		{
			$num = \DB::expr('
				(SELECT MAX(num)
				FROM
				(
					SELECT num
					FROM '.$this->radix->getTable().'
					WHERE thread_num = '.intval($this->thread_num).'
				) AS x)
			');

			$subnum = \DB::expr('
				(SELECT MAX(subnum)+1
				FROM
				(
					SELECT subnum
					FROM ' . $this->radix->getTable() . '
					WHERE
						num = (
							SELECT MAX(num)
							FROM ' . $this->radix->getTable() . '
							WHERE thread_num = '.intval($this->thread_num).'

						)
				) AS x)
			');

			$thread_num = $this->thread_num;
		}
		else
		{
			$num = \DB::expr('
				(SELECT COALESCE(MAX(num), 0)+1 AS num
				FROM
				(
					SELECT num
					FROM '.$this->radix->getTable().'
				) AS x)
			');

			$subnum = 0;

			if($this->thread_num > 0)
			{
				$thread_num = $this->thread_num;
			}
			else
			{
				$thread_num = \DB::expr('
					(SELECT COALESCE(MAX(num), 0)+1 AS thread_num
					FROM
					(
						SELECT num
						FROM '.$this->radix->getTable().'
					) AS x)
				');
			}
		}

		$try_max = 3;
		$try_count = 0;
		$try_done = false;

		while (true)
		{
			try
			{
				\DB::start_transaction();
				
				list($last_id, $num_affected) =
					\DB::insert(\DB::expr($this->radix->getTable()))
					->set([
						'media_id' => $this->media->media_id ? $this->media->media_id : 0,
						'num' => $num,
						'subnum' => $subnum,
						'thread_num' => $thread_num,
						'op' => $this->op,
						'timestamp' => $this->timestamp,
						'capcode' => $this->capcode,
						'email' => $this->email,
						'name' => $this->name,
						'trip' => $this->trip,
						'title' => $this->title,
						'comment' => $this->comment,
						'delpass' => $this->delpass,
						'spoiler' => $this->media->spoiler,
						'poster_ip' => $this->poster_ip,
						'poster_hash' => $this->poster_hash,
						'poster_country' => $this->poster_country,
						'preview_orig' => $this->media->preview_orig,
						'preview_w' => $this->media->preview_w,
						'preview_h' => $this->media->preview_h,
						'media_filename' => $this->media->media_filename,
						'media_w' => $this->media->media_w,
						'media_h' => $this->media->media_h,
						'media_size' => $this->media->media_size,
						'media_hash' => $this->media->media_hash,
						'media_orig' => $this->media->media_orig,
						'exif' => $this->media->exif !== null ? json_encode($this->media->exif) : null,
					])->execute();

				// check that it wasn't posted multiple times
				$check_duplicate = \DB::select()->from(\DB::expr($this->radix->getTable()))
					->where('poster_ip', \Input::ip_decimal())->where('comment', $this->comment)
					->where('timestamp', '>=', $this->timestamp)->as_object()->execute();

				if(count($check_duplicate) > 1)
				{
					\DB::rollback_transaction();
					throw new CommentSendingDuplicateException(__('You are sending the same post twice.'));
				}

				$comment = $check_duplicate->current();

				$media_fields = Media::get_fields();
				// refresh the current comment object with the one finalized fetched from DB
				foreach ($comment as $key => $item)
				{
					if (in_array($key, $media_fields))
					{
						$this->media->$key = $item;
					}
					else
					{
						$this->$key = $item;
					}
				}

				$this->insertTriggerThreads();
				$this->insertTriggerDaily();
				$this->insertTriggerUsers();

				// update poster_hash for non-ghost posts
				if ( ! $this->ghost && $this->op && $this->radix->enable_poster_hash)
				{
					$this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$comment->thread_num),'id'),+3), 0, 8);

					\DB::update(\DB::expr($this->radix->getTable()))
						->value('poster_hash', $this->poster_hash)->where('doc_id', $comment->doc_id)->execute();
				}

				// set data for extra fields
				\Foolz\Plugin\Hook::forge('fu.comment.insert.extra_json_array')
					->setObject($this)
					->execute();

				// insert the extra row DURING A TRANSACTION
				$this->extra->extra_id = $last_id;
				$this->extra->insert();

				\DB::commit_transaction();
			}
			catch (\Database_Exception $e)
			{
				// 1213 is the deadlock exception
				if ($e->getCode() !== 1213)
				{
					throw new CommentSendingDatabaseException(__('Something went wrong when inserting the post in the database. Try again.'));
				}

				$try_count++;

				if ($try_count > $try_max)
				{
					throw new CommentSendingDatabaseException(__('Something went wrong when inserting the post in the database. Try again.'));
				}

				continue;
			}

			break;
		}

		// success, now check if there's extra work to do

		// we might be using the local MyISAM search table which doesn't support transactions
		// so we must be really careful with the insertion
		if($this->radix->myisam_search)
		{
			\DB::insert(\DB::expr($this->radix->getTable('_search')))
				->set(array(
					'doc_id' => $comment->doc_id,
					'num' => $comment->num,
					'subnum' => $comment->subnum,
					'thread_num' => $comment->thread_num,
					'media_filename' => $comment->media_filename,
					'comment' => $comment->comment
				))->execute();
		}

		return $this;
	}
}