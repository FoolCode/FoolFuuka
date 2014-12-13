<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Cache\Cache;
use Foolz\Inet\Inet;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Neutron\ReCaptcha\ReCaptcha;

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
    public $recaptcha_challenge = null;
    public $recaptcha_response = null;
    public $ghost = false;
    public $ghost_exist = false;
    public $allow_media = false;

    protected function insertTriggerDaily()
    {
        $item = [
            'day' => (int) (floor($this->comment->timestamp/86400)*86400),
            'images' => (int) ($this->bulk->media !== null),
            'sage' => (int) ($this->comment->email === 'sage'),
            'anons' => (int) ($this->comment->name === $this->radix->getValue('anonymous_default_name') && $this->comment->trip === null),
            'trips' => (int) ($this->comment->trip !== null),
            'names' => (int) ($this->comment->name !== $this->radix->getValue('anonymous_default_name') || $this->comment->trip !== null)
        ];

        $result = $this->dc->qb()
            ->select('*')
            ->from($this->radix->getTable('_daily'), 'd')
            ->where('day = :day')
            ->setParameter(':day', $item['day'])
            ->execute()
            ->fetch();

        if ($result === false) {
            try {
                $item['posts'] = 0;
                $this->dc->getConnection()->insert($this->radix->getTable('_daily'), $item);
            } catch(\Doctrine\DBAL\DBALException $e) {
                throw new \Doctrine\DBAL\DBALException;
            }
        } else {
            $this->dc->qb()
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
        $select = $this->dc->qb()
            ->select('*')
            ->from($this->radix->getTable('_users'), 'u');

        if ($this->comment->trip !== null) {
            $select->where('trip = :trip')
                ->setParameter(':trip', $this->comment->trip);
        } else {
            $select->where('name = :name')
                ->setParameter(':name', $this->comment->name);
        }

        $result = $select
            ->execute()
            ->fetch();

        if ($result === false) {
            try {
                $this->dc->getConnection()->insert($this->radix->getTable('_users'), [
                    'name' => (string) $this->comment->name,
                    'trip' => (string) $this->comment->trip,
                    'firstseen' => $this->comment->timestamp,
                    'postcount' => 1
                ]);
            } catch (\Doctrine\DBAL\DBALException $e) {
                throw new \Doctrine\DBAL\DBALException;
            }
        } else {
            $this->dc->qb()
                ->update($this->radix->getTable('_users'))
                ->set('postcount', 'postcount + 1')
                ->where('user_id = :user_id')
                ->setParameter(':user_id', $result['user_id'])
                ->execute();
        }
    }

    protected function insertTriggerThreads()
    {
        if ($this->comment->op) {
            $this->dc->getConnection()->insert($this->radix->getTable('_threads'), [
                'thread_num' => $this->comment->num,
                'time_op' => $this->comment->timestamp,
                'time_last' => $this->comment->timestamp,
                'time_bump' => $this->comment->timestamp,
                'time_ghost' => null,
                'time_ghost_bump' => null,
                'time_last_modified' => $this->comment->timestamp,
                'nreplies' => 1,
                'nimages' => ($this->media->media_id ? 1 : 0),
                'sticky' => 0,
                'locked' => 0
            ]);
        } else {
            if (!$this->comment->subnum) {
                $query = $this->dc->qb()
                    ->update($this->radix->getTable('_threads'))
                    ->set('time_last', 'GREATEST(time_last, :time_last)')
                    ->set('time_last_modified', 'GREATEST(time_last_modified, :time_last)')
                    ->set('nreplies', 'nreplies + 1')
                    ->set('nimages', 'nimages + '. ($this->media->media_id ? 1 : 0))
                    ->setParameter(':time_last', $this->comment->timestamp);

                if ($this->comment->email !== 'sage') {
                    $query
                        ->set('time_bump', 'GREATEST(time_bump, :time_bump)')
                        ->setParameter(':time_bump', $this->comment->timestamp);
                }
            } else {
                $query = $this->dc->qb()
                    ->update($this->radix->getTable('_threads'))
                    ->set('time_ghost', 'COALESCE(time_ghost, :time_ghost)')
                    ->set('time_last_modified', 'GREATEST(time_last_modified, :time_ghost)')
                    ->set('nreplies', 'nreplies + 1')
                    ->setParameter(':time_ghost', $this->comment->timestamp);

                if ($this->email !== 'sage') {
                    $query
                        ->set('time_ghost_bump', 'GREATEST(COALESCE(time_ghost_bump, 0), :time_ghost_bump)')
                        ->setParameter(':time_ghost_bump', $this->comment->timestamp);
                }
            }

            $query
                ->where('thread_num = :thread_num')
                ->setParameter(':thread_num', $this->comment->thread_num)
                ->execute();
        }
    }

    /**
     * Send the comment and attached media to database
     *
     * @param Media $media
     * @param array $data extra data
     * @throws CommentSendingDatabaseException
     * @throws CommentSendingBannedException
     * @throws CommentSendingThreadWithoutMediaException
     * @throws CommentSendingSpamException
     * @throws CommentSendingImageInGhostException
     * @throws CommentSendingNoDelPassException
     * @throws CommentSendingThreadClosedException
     * @throws CommentSendingRequestCaptchaException
     * @throws CommentSendingTimeLimitException
     * @throws CommentSendingException
     * @throws CommentSendingTooManyCharactersException
     * @throws \RuntimeException
     * @throws CommentSendingDisplaysEmptyException
     * @throws CommentSendingTooManyLinesException
     * @throws CommentSendingSameCommentException
     * @throws CommentSendingWrongCaptchaException
     * @throws CommentSendingUnallowedCapcodeException
     * @return array error key with explanation to show to user, or success and post row
     */
    public function p_insert(Media $media = null, $data = [])
    {
        if (isset($data['recaptcha_challenge'])) {
            $this->recaptcha_challenge = $data['recaptcha_challenge'];
            $this->recaptcha_response = $data['recaptcha_response'];
        }

        $this->ghost = false;
        $this->ghost_exist = false;
        $this->allow_media = true;

        // some users don't need to be limited, in here go all the ban and posting limitators
        if (!$this->getAuth()->hasAccess('comment.limitless_comment')) {
            // check if the user is banned
            if ($ban = $this->ban_factory->isBanned($this->comment->poster_ip, $this->radix)) {
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

                if ($ban->appeal_status == Ban::APPEAL_NONE) {
                    $banned_string .= ' '._i('If you\'d like to appeal to your ban, go to the %s page.',
                        '<a href="'.$this->uri->create($this->radix->shortname.'/appeal').'">'._i('Appeal').'</a>');
                } elseif ($ban->appeal_status == Ban::APPEAL_PENDING) {
                    $banned_string .= ' '._i('Your appeal is pending.');
                }

                throw new CommentSendingBannedException($banned_string);
            }
        }

        // check if it's a thread and its status
        if ($this->comment->thread_num > 0) {
            try {
                $thread = Board::forge($this->getContext())
                    ->getThread($this->comment->thread_num)
                    ->setRadix($this->radix);

                $status = $thread->getThreadStatus();
            } catch (BoardException $e) {
                throw new CommentSendingException($e->getMessage());
            }

            if ($status['closed']) {
                throw new CommentSendingThreadClosedException(_i('The thread is closed.'));
            }

            $this->ghost = $status['dead'];
            $this->ghost_exist = $status['ghost_exist'];
            $this->allow_media = ! $status['disable_image_upload'];
        }

        foreach(['name', 'email', 'title', 'comment', 'capcode'] as $key) {
            $this->comment->$key = trim((string) $this->comment->$key);
        }

        $this->comment->setDelpass(trim((string) $this->comment->getDelPass()));

        // some users don't need to be limited, in here go all the ban and posting limitators
        if (!$this->getAuth()->hasAccess('comment.limitless_comment')) {
            if ($this->comment->thread_num < 1) {
                // one can create a new thread only once every 5 minutes
                $check_op = $this->dc->qb()
                    ->select('*')
                    ->from($this->radix->getTable(), 'r')
                    ->where('r.poster_ip = :poster_ip')
                    ->andWhere('r.timestamp > :timestamp')
                    ->andWhere('r.op = :op')
                    ->setParameters([
                        ':poster_ip' => $this->comment->poster_ip,
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
            $check = $this->dc->qb()
                ->select('*')
                ->from($this->radix->getTable(), 'r')
                ->where('poster_ip = :poster_ip')
                ->orderBy('timestamp', 'DESC')
                ->setMaxResults(1)
                ->setParameter(':poster_ip', $this->comment->poster_ip)
                ->execute()
                ->fetch();

            if ($check) {
                if ($this->comment->comment !== null && $check['comment'] === $this->comment->comment) {
                    throw new CommentSendingSameCommentException(_i('You\'re sending the same comment as the last time'));
                }

                $check_time = $this->getRadixTime();

                if ($check_time - $check['timestamp'] < $this->radix->getValue('cooldown_new_comment') && $check_time - $check['timestamp'] > 0) {
                    throw new CommentSendingTimeLimitException(_i('You must wait up to %d seconds to post again.', $this->radix->getValue('cooldown_new_comment')));
                }
            }

            // we want to know if the comment will display empty, and in case we won't let it pass
            $comment_parsed = $this->processComment();
            if ($this->comment->comment !== '' && $comment_parsed === '') {
                throw new CommentSendingDisplaysEmptyException(_i('This comment would display empty.'));
            }

            // clean up to reset eventual auto-built entries
            $this->comment->clean();

            if ($this->recaptcha_challenge && $this->recaptcha_response && $this->preferences->get('foolframe.auth.recaptcha_public', false)) {
                $recaptcha = ReCaptcha::create($this->preferences->get('foolframe.auth.recaptcha_public'), $this->preferences->get('foolframe.auth.recaptcha_private'));
                $recaptcha_result = $recaptcha->checkAnswer(
                    Inet::dtop($this->comment->poster_ip),
                    $this->recaptcha_challenge,
                    $this->recaptcha_response
                );

                if (!$recaptcha_result->isValid()) {
                    throw new CommentSendingWrongCaptchaException(_i('Incorrect CAPTCHA solution.'));
                }
            } elseif ($this->preferences->get('foolframe.auth.recaptcha_public')) { // if there wasn't a recaptcha input, let's go with heavier checks
                if (substr_count($this->comment->comment, 'http') >= $this->radix->getValue('captcha_comment_link_limit')) {
                    throw new CommentSendingRequestCaptchaException;
                }

                // bots usually fill all the fields
                if ($this->comment->comment && $this->comment->title && $this->comment->email) {
                    throw new CommentSendingRequestCaptchaException;
                }

                // bots usually try various BBC, this checks if there's unparsed BBC after parsing it
                if ($comment_parsed !== '' && substr_count($comment_parsed, '[') + substr_count($comment_parsed, ']') > 4) {
                    throw new CommentSendingRequestCaptchaException;
                }
            }

            // load the spam list and check comment, name, title and email
            $spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents(ASSETSPATH.'packages/anti-spam/databases/urls.dat')));
            foreach($spam as $s) {
                if (strpos($this->comment->comment, $s) !== false || strpos($this->comment->name, $s) !== false
                    || strpos($this->comment->title, $s) !== false || strpos($this->comment->email, $s) !== false)
                {
                    throw new CommentSendingSpamException(_i('Your post has undesidered content.'));
                }
            }

            // check entire length of comment
            if (mb_strlen($this->comment->comment, 'utf-8') > $this->radix->getValue('max_comment_characters_allowed')) {
                throw new CommentSendingTooManyCharactersException(_i('Your comment has too many characters'));
            }

            // check total numbers of lines in comment
            if (count(explode("\n", $this->comment->comment)) > $this->radix->getValue('max_comment_lines_allowed')) {
                throw new CommentSendingTooManyLinesException(_i('Your comment has too many lines.'));
            }
        }

        \Foolz\Plugin\Hook::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.after.input_checks')
            ->setObject($this)
            ->execute();

        // process comment name+trip
        if ($this->comment->name === '') {
            $this->comment->name = $this->radix->getValue('anonymous_default_name');
            $this->comment->trip = null;
        } else {
            $this->processName();
            if ($this->comment->trip === '') {
                $this->comment->trip = null;
            }
        }

        foreach (['email', 'title', 'comment'] as $key) {
            if ($this->comment->$key === '') {
                $this->comment->$key = null;
            }
        }

        // process comment password
        if ($this->comment->getDelpass() === '') {
            throw new CommentSendingNoDelPassException(_i('You must submit a deletion password.'));
        }

        $pass = password_hash($this->comment->getDelpass(), PASSWORD_BCRYPT, ['cost' => 10]);
        if ($this->comment->getDelpass() === false) {
            throw new \RuntimeException('Password hashing failed');
        }
        $this->comment->setDelpass($pass);

        if ($this->comment->capcode != '') {
            $allowed_capcodes = ['N'];

            if ($this->getAuth()->hasAccess('comment.mod_capcode')) {
                $allowed_capcodes[] = 'M';
            }

            if ($this->getAuth()->hasAccess('comment.admin_capcode')) {
                $allowed_capcodes[] = 'A';
            }

            if ($this->getAuth()->hasAccess('comment.dev_capcode')) {
                $allowed_capcodes[] = 'D';
            }

            if (!in_array($this->comment->capcode, $allowed_capcodes)) {
                throw new CommentSendingUnallowedCapcodeException(_i('You\'re not allowed to use this capcode.'));
            }
        } else {
            $this->comment->capcode = 'N';
        }

        $microtime = str_replace('.', '', (string) microtime(true));
        $this->comment->timestamp = substr($microtime, 0, 10);
        $this->comment->op = (bool) ! $this->comment->thread_num;

        if ($this->radix->getValue('enable_flags')) {
            $reader = new Reader($this->preferences->get('foolframe.maxmind.geoip2_db_path'));

            try {
                $record = $reader->country(Inet::dtop($this->comment->poster_ip));
                $this->comment->poster_country = strtolower($record->country->isoCode);
            } catch(AddressNotFoundException $e) {
                $this->comment->poster_country = 'xx';
            }
        }

        // process comment media
        if ($media !== null) {
            // if uploading an image with OP is prohibited

            if (!$this->comment->thread_num && $this->radix->getValue('op_image_upload_necessity') === 'never') {
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
                $media->insert($microtime, $this->comment->op);
                $this->media = $media->media;
            } catch (MediaInsertException $e) {
                throw new CommentSendingException($e->getMessage());
            }
        } else {
            // if the user is forced to upload an image when making a new thread
            if (!$this->comment->thread_num && $this->radix->getValue('op_image_upload_necessity') === 'always') {
                throw new CommentSendingThreadWithoutMediaException(_i('You can\'t start a new thread without an image.'));
            }

            // in case of no media, check comment field again for null
            if ($this->comment->comment === null) {
                throw new CommentSendingDisplaysEmptyException(_i('This comment would display empty.'));
            }

            $this->media = $this->bulk->media = new MediaData();
        }

        // 2ch-style codes, only if enabled
        if ($this->comment->thread_num && $this->radix->getValue('enable_poster_hash')) {
            $this->comment->poster_hash = substr(substr(crypt(md5($this->comment->poster_ip.'id'.$this->comment->thread_num),'id'),+3), 0, 8);
        }

        $this->comment->timestamp = $this->getRadixTime($this->comment->timestamp);

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
                        WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
                    )
                    UNION
                    (
                        SELECT num
                        FROM '.$this->radix->getTable('_deleted').' xrd
                        WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
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
                                    WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
                                )
                                UNION
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable('_deleted').' xxxrd
                                    WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
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
                                    WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
                                )
                                UNION
                                (
                                    SELECT num
                                    FROM '.$this->radix->getTable('_deleted').' xxxdrd
                                    WHERE thread_num = '.$this->dc->getConnection()->quote($this->comment->thread_num).'
                                )
                            ) xxxd
                        )
                    )
                ) xx
            )';

            $thread_num = $this->comment->thread_num;
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

            if ($this->comment->thread_num > 0) {
                $thread_num = $this->dc->getConnection()->quote($this->comment->thread_num);
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
            $query_fields = [
                'num' => $num,
                'subnum' => $subnum,
                'thread_num' => $thread_num,
            ];

            $fields = [
                'media_id' => $this->media->media_id ? $this->media->media_id : 0,
                'op' => (int) $this->op,
                'timestamp' => $this->comment->timestamp,
                'capcode' => $this->comment->capcode,
                'email' => $this->comment->email,
                'name' => $this->comment->name,
                'trip' => $this->comment->trip,
                'title' => $this->comment->title,
                'comment' => $this->comment->comment,
                'delpass' => $this->comment->getDelpass(),
                'spoiler' => (int) $this->media->spoiler,
                'poster_ip' => $this->comment->poster_ip,
                'poster_hash' => $this->comment->poster_hash,
                'poster_country' => $this->comment->poster_country,
                'preview_orig' => $this->media->preview_orig,
                'preview_w' => $this->media->preview_w,
                'preview_h' => $this->media->preview_h,
                'media_filename' => $this->media->media_filename,
                'media_w' => $this->media->media_w,
                'media_h' => $this->media->media_h,
                'media_size' => $this->media->media_size,
                'media_hash' => $this->media->media_hash,
                'media_orig' => $this->media->media_orig,
                'exif' => $this->media->exif !== null ? @json_encode($this->media->exif) : null,
                'timestamp_expired' => 0
            ];

            foreach ($fields as $key => $item) {
                if ($item === null) {
                    $fields[$key] = 'null';
                } else {
                    $fields[$key] = $this->dc->getConnection()->quote($item);
                }
            }

            $fields = $query_fields + $fields;

            $this->dc->getConnection()->beginTransaction();

            $this->dc->getConnection()->executeUpdate(
                'INSERT INTO '.$this->radix->getTable().
                    ' ('.implode(', ', array_keys($fields)).') VALUES ('.implode(', ', array_values($fields)).')'
            );

            $last_id = $this->dc->getConnection()->lastInsertId($this->radix->getTable('_doc_id_seq'));

            $comment = $this->dc->qb()
                ->select('*')
                ->from($this->radix->getTable(), 'r')
                ->where('doc_id = :doc_id')
                ->setParameter(':doc_id', $last_id)
                ->execute()
                ->fetchAll();

            $this->bulk->import($comment[0], $this->radix);

            if (!$this->radix->archive) {
                $this->insertTriggerThreads();
                $this->insertTriggerDaily();
                $this->insertTriggerUsers();
            }

            // update poster_hash for op posts
            if ($this->comment->op && $this->radix->getValue('enable_poster_hash')) {
                $this->comment->poster_hash = substr(substr(crypt(md5($this->comment->poster_ip.'id'.$this->comment->thread_num),'id'), 3), 0, 8);

                $this->dc->qb()
                    ->update($this->radix->getTable(), 'ph')
                    ->set('poster_hash', $this->dc->getConnection()->quote($this->comment->poster_hash))
                    ->where('doc_id = :doc_id')
                    ->setParameter(':doc_id', $this->comment->doc_id)
                    ->execute();
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

            throw new CommentSendingDatabaseException(_i('Something went wrong when inserting the post in the database. Try again.'));
        }

        return $this;
    }
}
