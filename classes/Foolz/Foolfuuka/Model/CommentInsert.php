<?php

namespace Foolz\Foolfuuka\Model;

use \Foolz\Foolframe\Model\DoctrineConnection as DC,
    \Foolz\Cache\Cache;

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
    protected function insertTriggerDaily()
    {
        $item = [
            'day' => (int) (floor($this->timestamp/86400)*86400),
            'images' => (int) ($this->media !== null),
            'sage' => (int) ($this->email === 'sage'),
            'anons' => (int) ($this->name === $this->radix->getValue('anonymous_default_name') && $this->trip === null),
            'trips' => (int) ($this->trip !== null),
            'names' => (int) ($this->name !== $this->radix->getValue('anonymous_default_name') || $this->trip !== null)
        ];

        $result = DC::qb()
            ->select('*')
            ->from($this->radix->getTable('_daily'), 'd')
            ->where('day = :day')
            ->setParameter(':day', $item['day'])
            ->execute()
            ->fetch();

        if ($result === false) {
            try {
                $item['posts'] = 0;
                DC::forge()->insert($this->radix->getTable('_daily'), $item);
            } catch(\Doctrine\DBAL\DBALException $e) {
                throw new \Doctrine\DBAL\DBALException;
            }
        } else {
            DC::qb()
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
    }

    protected function insertTriggerUsers()
    {
        $select = DC::qb()
            ->select('*')
            ->from($this->radix->getTable('_users'), 'u');

        if ($this->trip !== null) {
            $select->where('trip = :trip')
                ->setParameter(':trip', $this->trip);
        } else {
            $select->where('name = :name')
                ->setParameter(':name', $this->name);
        }

        $result = $select
            ->execute()
            ->fetch();

        if ($result === false) {
            try {
                DC::forge()->insert($this->radix->getTable('_users'), [
                    'name' => (string) $this->name,
                    'trip' => (string) $this->trip,
                    'firstseen' => $this->timestamp,
                    'postcount' => 1
                ]);
            } catch (\Doctrine\DBAL\DBALException $e) {
                throw new \Doctrine\DBAL\DBALException;
            }
        } else {
            DC::qb()
                ->update($this->radix->getTable('_users'))
                ->set('postcount', 'postcount + 1')
                ->where('user_id = :user_id')
                ->setParameter(':user_id', $result['user_id'])
                ->execute();
        }
    }

    protected function insertTriggerThreads()
    {
        if ($this->op) {
            DC::forge()->insert($this->radix->getTable('_threads'), [
                'thread_num' => $this->num,
                'time_op' => $this->timestamp,
                'time_last' => $this->timestamp,
                'time_bump' => $this->timestamp,
                'time_ghost' => null,
                'time_ghost_bump' => null,
                'time_last_modified' => $this->timestamp,
                'nreplies' => 1,
                'nimages' => ($this->media->media_id ? 1 : 0),
                'sticky' => 0,
                'locked' => 0
            ]);
        } else {
            if (!$this->subnum) {
                $query = DC::qb()
                    ->update($this->radix->getTable('_threads'))
                    ->set('time_last', 'GREATEST(time_last, :time_last)')
                    ->set('time_last_modified', 'GREATEST(time_last_modified, :time_last)')
                    ->set('nreplies', 'nreplies + 1')
                    ->set('nimages', 'nimages + '. ($this->media->media_id ? 1 : 0))
                    ->setParameter(':time_last', $this->timestamp);

                if ($this->email !== 'sage') {
                    $query
                        ->set('time_bump', 'GREATEST(time_bump, :time_bump)')
                        ->setParameter(':time_bump', $this->timestamp);
                }
            } else {
                $query = DC::qb()
                    ->update($this->radix->getTable('_threads'))
                    ->set('time_ghost', 'COALESCE(time_ghost, :time_ghost)')
                    ->set('time_last_modified', 'GREATEST(time_last_modified, :time_ghost)')
                    ->set('nreplies', 'nreplies + 1')
                    ->setParameter(':time_ghost', $this->timestamp);

                if ($this->email !== 'sage') {
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
        if (!\Auth::has_access('comment.limitless_comment')) {
            // check if the user is banned
            if ($ban = \Ban::isBanned(\Input::ip_decimal(), $this->radix)) {
                if ($ban->board_id == 0) {
                    $banned_string = _i('It looks like you were banned on all boards.');
                } else {
                    $banned_string = _i('It looks like you were banned on /'.$this->radix->shortname.'/.');
                }

                if ($ban->length) {
                    $banned_string .= ' '._i('This ban will last until:').' '.date(DATE_COOKIE, $ban->start + $ban->length).'.';
                } else {
                    $banned_string .= ' '._i('This ban will last forever.');
                }

                if ($ban->reason) {
                    $banned_string .= ' '._i('The reason for this ban is:').' «'.$ban->reason.'».';
                }

                if ($ban->appeal_status == \Ban::APPEAL_NONE) {
                    $banned_string .= ' '._i('If you\'d like to appeal to your ban, go to the %s page.',
                        '<a href="'.\Uri::create($this->radix->shortname.'/appeal').'">'._i('Appeal').'</a>');
                } elseif ($ban->appeal_status == \Ban::APPEAL_PENDING) {
                    $banned_string .= ' '._i('Your appeal is pending.');
                }

                throw new CommentSendingBannedException($banned_string);
            }
        }

        // check if it's a thread and its status
        if ($this->thread_num > 0) {
            try {
                $thread = Board::forge()
                    ->getThread($this->thread_num)
                    ->setRadix($this->radix);

                $status = $thread->getThreadStatus();
            } catch (BoardException $e) {
                throw new CommentSendingException($e->getMessage());
            }

            if ($status['closed']) {
                throw new CommentSendingThreadClosedException(_i('The thread is closed.'));
            }

            $this->ghost = $status['dead'];
            $this->allow_media = ! $status['disable_image_upload'];
        }

        foreach(['name', 'email', 'title', 'delpass', 'comment', 'capcode'] as $key) {
            $this->$key = trim((string) $this->$key);
        }

        // some users don't need to be limited, in here go all the ban and posting limitators
        if (!\Auth::has_access('comment.limitless_comment')) {
            if ($this->thread_num < 1) {
                // one can create a new thread only once every 5 minutes
                $check_op = DC::qb()
                    ->select('*')
                    ->from($this->radix->getTable(), 'r')
                    ->where('r.poster_ip = :poster_ip')
                    ->andWhere('r.timestamp > :timestamp')
                    ->andWhere('r.op = :op')
                    ->setParameters([
                        ':poster_ip' => \Input::ip_decimal(),
                        ':timestamp' => time() - $this->radix->getValue('cooldown_new_thread'),
                        ':op' => true
                    ])
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch();

                if ($check_op) {
                    throw new CommentSendingTimeLimitException(_i('You must wait up to %d minutes to make another new thread.', ceil($this->radix->getValue('cooldown_new_thread') / 60)));
                }
            }

            // check the latest posts by the user to see if he's posting the same message or if he's posting too fast
            $check = DC::qb()
                ->select('*')
                ->from($this->radix->getTable(), 'r')
                ->where('poster_ip = :poster_ip')
                ->orderBy('timestamp', 'DESC')
                ->setMaxResults(1)
                ->setParameter(':poster_ip', \Input::ip_decimal())
                ->execute()
                ->fetch();

            if ($check) {
                if ($this->comment !== null && $check['comment'] === $this->comment) {
                    throw new CommentSendingSameCommentException(_i('You\'re sending the same comment as the last time'));
                }

                $check_time = $this->getRadixTime();

                if ($check_time - $check['timestamp'] < $this->radix->getValue('cooldown_new_comment') && $check_time - $check['timestamp'] > 0) {
                    throw new CommentSendingTimeLimitException(_i('You must wait up to %d seconds to post again.', $this->radix->getValue('cooldown_new_comment')));
                }
            }

            // we want to know if the comment will display empty, and in case we won't let it pass
            $comment_parsed = $this->processComment();
            if ($this->comment !== '' && $comment_parsed === '') {
                throw new CommentSendingDisplaysEmptyException(_i('This comment would display empty.'));
            }

            // clean up to reset eventual auto-built entries
            foreach ($this->_forced_entries as $field) {
                unset($this->$field);
            }

            if ($this->recaptcha_challenge && $this->recaptcha_response && \ReCaptcha::available()) {
                $recaptcha = \ReCaptcha::instance()
                    ->check_answer(\Input::ip(), $this->recaptcha_challenge, $this->recaptcha_response);

                if (!$recaptcha) {
                    throw new CommentSendingWrongCaptchaException(_i('Incorrect CAPTCHA solution.'));
                }
            } elseif (\ReCaptcha::available()) { // if there wasn't a recaptcha input, let's go with heavier checks
                // 3+ links is suspect
                if (substr_count($this->comment, 'http') > 2) {
                    throw new CommentSendingRequestCaptchaException;
                }

                // bots usually fill all the fields
                if ($this->comment && $this->title && $this->email) {
                    throw new CommentSendingRequestCaptchaException;
                }

                // bots usually try various BBC, this checks if there's unparsed BBC after parsing it
                if ($comment_parsed !== '' && substr_count($comment_parsed, '[') + substr_count($comment_parsed, ']') > 4) {
                    throw new CommentSendingRequestCaptchaException;
                }
            }

            // load the spam list and check comment, name, title and email
            $spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents(VENDPATH.'/foolz/foolfuuka/packages/anti-spam/databases/urls.dat')));
            foreach($spam as $s) {
                if (strpos($this->comment, $s) !== false || strpos($this->name, $s) !== false
                    || strpos($this->title, $s) !== false || strpos($this->email, $s) !== false)
                {
                    throw new CommentSendingSpamException(_i('Your post has undesidered content.'));
                }
            }

            // check entire length of comment
            if (mb_strlen($this->comment) > $this->radix->getValue('max_comment_characters_allowed')) {
                throw new CommentSendingTooManyCharactersException(_i('Your comment has too many characters'));
            }

            // check total numbers of lines in comment
            if (count(explode("\n", $this->comment)) > $this->radix->getValue('max_comment_lines_allowed')) {
                throw new CommentSendingTooManyLinesException(_i('Your comment has too many lines.'));
            }
        }

        \Foolz\Plugin\Hook::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.after.input_checks')
            ->setObject($this)
            ->execute();

        // process comment name+trip
        if ($this->name === '') {
            $this->name = $this->radix->getValue('anonymous_default_name');
            $this->trip = null;
        } else {
            $this->processName();
            if ($this->trip === '') {
                $this->trip = null;
            }
        }

        foreach (['email', 'title', 'delpass', 'comment'] as $key) {
            if ($this->$key === '') {
                $this->$key = null;
            }
        }

        // process comment password
        if ($this->delpass === '') {
            throw new CommentSendingNoDelPassException(_i('You must submit a deletion password.'));
        }

        if (!class_exists('PHPSecLib\\Crypt_Hash', false)) {
            import('phpseclib/Crypt/Hash', 'vendor');
        }

        $hasher = new \PHPSecLib\Crypt_Hash();
        $this->delpass = base64_encode($hasher->pbkdf2($this->delpass, \Foolz\Foolframe\Model\Config::get('foolz/foolframe', 'foolauth', 'salt'), 10000, 32));

        if ($this->capcode != '') {
            $allowed_capcodes = ['N'];

            if (\Auth::has_access('comment.mod_capcode')) {
                $allowed_capcodes[] = 'M';
            }

            if (\Auth::has_access('comment.admin_capcode')) {
                $allowed_capcodes[] = 'A';
            }

            if (\Auth::has_access('comment.dev_capcode')) {
                $allowed_capcodes[] = 'D';
            }

            if (!in_array($this->capcode, $allowed_capcodes)) {
                throw new CommentSendingUnallowedCapcodeException(_i('You\'re not allowed to use this capcode.'));
            }
        } else {
            $this->capcode = 'N';
        }

        $microtime = str_replace('.', '', (string) microtime(true));
        $this->timestamp = substr($microtime, 0, 10);
        $this->op = (bool) ! $this->thread_num;

        if ($this->poster_ip === null) {
            $this->poster_ip = \Input::ip_decimal();
        }

        if ($this->radix->getValue('enable_flags') && function_exists('\\geoip_country_code_by_name')) {
            $this->poster_country = \geoip_country_code_by_name(\Foolz\Inet\Inet::dtop($this->poster_ip));
        }

        // process comment media
        if ($this->media !== null) {
            // if uploading an image with OP is prohibited

            if (!$this->thread_num && $this->radix->getValue('op_image_upload_necessity') === 'never') {
                throw new CommentSendingException(_i('You can\'t start a new thread with an image.'));
            }

            if (!$this->allow_media) {
                if ($this->ghost) {
                    throw new CommentSendingImageInGhostException(_i('You can\'t post images when the thread is in ghost mode.'));
                } else {
                    throw new CommentSendingException(_i('This thread has reached its image limit.'));
                }
            }

            try {
                $this->media->insert($microtime, $this->op);
            } catch (MediaInsertException $e) {
                throw new CommentSendingException($e->getMessage());
            }
        } else {
            // if the user is forced to upload an image when making a new thread
            if (!$this->thread_num && $this->radix->getValue('op_image_upload_necessity') === 'always') {
                throw new CommentSendingThreadWithoutMediaException(_i('You can\'t start a new thread without an image.'));
            }

            // in case of no media, check comment field again for null
            if ($this->comment === null) {
                throw new CommentSendingDisplaysEmptyException(_i('This comment would display empty.'));
            }

            $this->media = Media::forgeEmpty($this->radix);
        }

        // 2ch-style codes, only if enabled
        if ($this->thread_num && $this->radix->getValue('enable_poster_hash')) {
            $this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$this->thread_num),'id'),+3), 0, 8);
        }

        $this->timestamp = $this->getRadixTime($this->timestamp);

        \Foolz\Plugin\Hook::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.before.sql')
            ->setObject($this)
            ->execute();

        // being processing insert...

        if ($this->ghost) {
            $num = '
            (
                SELECT MAX(num) AS num
                FROM (
                    (
                        SELECT num
                        FROM '.$this->radix->getTable().' xr
                        WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                    )
                    UNION
                    (
                        SELECT num
                        FROM '.$this->radix->getTable('_deleted').' xrd
                        WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                    )
                ) x
            )';

            $subnum = '
            (
                SELECT MAX(subnum) + 1 AS subnum
                FROM (
                    (
                        SELECT subnum
                        FROM '.$this->radix->getTable().' xxr
                        WHERE num = (
                            SELECT MAX(num)
                            FROM (
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable().' xxxr
                                    WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                                )
                                UNION
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable('_deleted').' xxxrd
                                    WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                                )
                            ) xxx
                        )
                    )
                    UNION
                    (
                        SELECT subnum
                        FROM '.$this->radix->getTable('_deleted').' xxdr
                        WHERE num = (
                            SELECT MAX(num)
                            FROM (
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable().' xxxr
                                    WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                                )
                                UNION
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable('_deleted').' xxxdrd
                                    WHERE thread_num = '.DC::forge()->quote($this->thread_num).'
                                )
                            ) xxxd
                        )
                    )
                ) xx
            )';

            $thread_num = $this->thread_num;
        } else {
            $num = '
            (
                SELECT MAX(num) + 1 AS num
                FROM (
                    (
                        SELECT COALESCE(MAX(num), 0) AS num
                        FROM '.$this->radix->getTable().' xr
                    )
                    UNION
                    (
                        SELECT COALESCE(MAX(num), 0) AS num
                        FROM '.$this->radix->getTable('_deleted').' xdr
                    )
                ) x
            )';

            $subnum = 0;

            if ($this->thread_num > 0) {
                $thread_num = DC::forge()->quote($this->thread_num);
            } else {
                $thread_num = '
                (
                    SELECT MAX(thread_num) + 1 AS thread_num
                    FROM (
                        (
                            SELECT COALESCE(MAX(num), 0) as thread_num
                            FROM '.$this->radix->getTable().' xxr
                        )
                        UNION
                        (
                            SELECT COALESCE(MAX(num), 0) as thread_num
                            FROM '.$this->radix->getTable('_deleted').' xxdr
                        )
                    ) xx
                )';
            }
        }

        try {
            DC::forge()->beginTransaction();

            $query_fields = [
                'num' => $num,
                'subnum' => $subnum,
                'thread_num' => $thread_num,
            ];

            $fields = [
                'media_id' => $this->media->media_id ? $this->media->media_id : 0,
                'op' => (int) $this->op,
                'timestamp' => $this->timestamp,
                'capcode' => $this->capcode,
                'email' => $this->email,
                'name' => $this->name,
                'trip' => $this->trip,
                'title' => $this->title,
                'comment' => $this->comment,
                'delpass' => $this->delpass,
                'spoiler' => (int) $this->media->spoiler,
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

                'timestamp_expired' => 0
            ];

            foreach ($fields as $key => $item) {
                if ($item === null) {
                    $fields[$key] = 'null';
                } else {
                    $fields[$key] = DC::forge()->quote($item);
                }
            }

            $fields = $query_fields + $fields;

            DC::forge()->executeUpdate(
                'INSERT INTO '.$this->radix->getTable().
                    ' ('.implode(', ', array_keys($fields)).') VALUES ('.implode(', ', array_values($fields)).')'
            );

            $last_id = DC::forge()->lastInsertId($this->radix->getTable('_doc_id_seq'));

            $comment = DC::qb()
                ->select('*')
                ->from($this->radix->getTable(), 'r')
                ->where('doc_id = :doc_id')
                ->setParameter(':doc_id', $last_id)
                ->execute()
                ->fetchAll();

            $comment = current($comment);

            $media_fields = Media::getFields();
            // refresh the current comment object with the one finalized fetched from DB
            foreach ($comment as $key => $item) {
                if (in_array($key, $media_fields)) {
                    $this->media->$key = $item;
                } else {
                    $this->$key = $item;
                }
            }

            if (!$this->radix->archive) {
                $this->insertTriggerThreads();
                $this->insertTriggerDaily();
                $this->insertTriggerUsers();
            }

            // update poster_hash for op posts
            if ($this->op && $this->radix->getValue('enable_poster_hash')) {
                $this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$comment['thread_num']),'id'), 3), 0, 8);

                DC::qb()
                    ->update($this->radix->getTable(), 'ph')
                    ->set('poster_hash', DC::forge()->quote($this->poster_hash))
                    ->where('doc_id = :doc_id')
                    ->setParameter(':doc_id', $comment['doc_id'])
                    ->execute();
            }

            // set data for extra fields
            \Foolz\Plugin\Hook::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.result.extra_json_array')
                ->setObject($this)
                ->execute();

            // insert the extra row DURING A TRANSACTION
            $this->extra->extra_id = $last_id;
            $this->extra->insert();

            DC::forge()->commit();

            // clean up some caches
            Cache::item('foolfuuka.model.board.getThreadComments.thread.'
                .md5(serialize([$this->radix->shortname, $this->thread_num])))->delete();

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
            \Log::error('\Foolz\Foolfuuka\Model\CommentInsert: '.$e->getMessage());
            DC::forge()->rollBack();

            throw new CommentSendingDatabaseException(_i('Something went wrong when inserting the post in the database. Try again.'));
        }

        return $this;
    }
}
