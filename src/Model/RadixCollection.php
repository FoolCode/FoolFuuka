<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Cache\Cache;
use Foolz\Foolframe\Model\Config;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Preferences;
use Foolz\Plugin\Hook;
use Foolz\Profiler\Profiler;
use Symfony\Component\Validator\Constraints as Assert;

class RadixCollection extends Model
{
    /**
     * An array of all the preloaded boards
     *
     * @var Radix[]
     */
    protected $preloaded_radixes = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var Profiler
     */
    protected $profiler;

    public function __construct(\Foolz\Foolframe\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->preferences = $context->getService('preferences');
        $this->config = $context->getService('config');
        $this->profiler = $context->getService('profiler');
    }

    /**
     * The structure of the radix table to be used with validation and form creator
     *
     * @param  \Foolz\Foolfuuka\Model\Radix|null  $radix  If available insert to customize the structure
     *
     * @return  array  The structure
     */
    public function structure($radix = null)
    {
        $dc = $this->dc;

        $structure = [
            'open' => ['type' => 'open'],
            'id' => [
                'type' => 'hidden',
                'database' => true,
                'validation_func' => function($input, $form_internal) use ($dc) {
                    // check that the ID exists
                    $row = $dc->qb()
                        ->select('COUNT(*) as count')
                        ->from($dc->p('boards'), 'b')
                        ->where('id = :id')
                        ->setParameter(':id', $input['id'])
                        ->execute()
                        ->fetch();

                    if ($row['count'] != 1) {
                        return [
                            'error_code' => 'ID_NOT_FOUND',
                            'error' => _i('Couldn\'t find the board with the submitted ID.'),
                            'critical' => true
                        ];
                    }

                    return ['success' => true];
                }
            ],
            'name' => [
                'database' => true,
                'type' => 'input',
                'label' => _i('Name'),
                'help' => _i('Insert the title of the board.'),
                'placeholder' => _i('Required'),
                'class' => 'span3',
                'validation' => [new Assert\NotBlank(), new Assert\Length(['max' => 128])]
            ],
            'shortname' => [
                'database' => true,
                'type' => 'input',
                'label' => _i('Shortname'),
                'help' => _i('Insert the shortname for the board. Reserved: "admin". '),
                'placeholder' => _i('Req.'),
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Length(['max' => 32])],
                'validation_func' => function($input, $form_internal) use ($dc) {
                    // if we're working on the same object
                    if (isset($input['id'])) {
                        // existence ensured by CRITICAL in the ID check
                        $row = $dc->qb()
                            ->select('shortname')
                            ->from($dc->p('boards'), 'b')
                            ->where('id = :id')
                            ->setParameter(':id', $input['id'])
                            ->execute()
                            ->fetch();

                        if ($row === false) {
                            return [
                                'error_code' => 'ID_NOT_FOUND',
                                'error' => _i('Couldn\'t find the board with the submitted ID.')
                            ];
                        }

                        // no change?
                        if ($input['shortname'] === $row['shortname']) {
                            // no change
                            return ['success' => true];
                        }
                    }

                    if (!preg_match('/^\w+$/', $input['shortname'], $matches)) {
                        return [
                            'error_code' => 'INVALID SHORTNAME',
                            'error' => _i('The shortname is must be composed of letters, numbers and underscores.')
                        ];
                    }

                    $row = $dc->qb()
                        ->select('shortname')
                        ->from($dc->p('boards'), 'r')
                        ->where('shortname = :s')
                        ->setParameter(':s', $input['shortname'])
                        ->execute()
                        ->fetch();

                    // check that there isn't already a board with that name
                    if ($row !== false) {
                        return [
                            'error_code' => 'ALREADY_EXISTS',
                            'error' => _i('The shortname is already used for another board.')
                        ];
                    }
                }
            ],
            'rules' => [
                'database' => true,
                'boards_preferences' => true,
                'type' => 'textarea',
                'label' => _i('General rules'),
                'help' => _i('Full board rules displayed in a separate page, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
                'class' => 'span6',
                'placeholder' => _i('MarkDown goes here')
            ],
            'separator-3' => [
                'type' => 'separator'
            ],
            'posting_rules' => [
                'database' => true,
                'boards_preferences' => true,
                'type' => 'textarea',
                'label' => _i('Posting rules'),
                'help' => _i('Posting rules displayed in the posting area, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
                'class' => 'span6',
                'placeholder' => _i('MarkDown goes here')
            ],
            'separator-1' => ['type' => 'separator'],
            'threads_per_page' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('Maximum number of threads to display in the index pages'),
                'type' => 'input',
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Type('digit')],
            ],
            'archive' => [
                'database' => true,
                'type' => 'checkbox',
                'help' => _i('Is this a 4chan archiving board?'),
                'sub' => [
                    'paragraph' => [
                        'type' => 'paragraph',
                        'help' => _i('Options for archive boards')
                    ],
                    'board_url' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'type' => 'input',
                        'label' => _i('URL to the 4chan board (facultative)'),
                        'placeholder' => 'http://boards.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
                        'class' => 'span4',
                        'validation' => [new Assert\Length(['max' => 256])]
                    ],
                    'thumbs_url' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'type' => 'input',
                        'label' => _i('URL to the board thumbnails (facultative)'),
                        'placeholder' => 'http://0.thumbs.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
                        'class' => 'span4',
                        'validation' => [new Assert\Length(['max' => 256])]
                    ],
                    'images_url' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'type' => 'input',
                        'label' => _i('URL to the board images (facultative)'),
                        'placeholder' => 'http://images.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
                        'class' => 'span4',
                        'validation' => [new Assert\Length(['max' => 256])]
                    ],
                    'archive_full_images' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'help' => _i('Is the archive storing full images?'),
                        'type' => 'checkbox',
                    ],
                ],
                'sub_inverse' => [
                    'paragraph' => [
                        'type' => 'paragraph',
                        'help' => _i('Options for normal boards')
                    ],
                    'op_image_upload_necessity' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'help' => _i('Select if users have to upload an image when starting a new thread?'),
                        'type' => 'select',
                        'default_value' => 'always',
                        'options' => ['always' => _i('Always'), 'optional' => _i('Optional'), 'never' => _i('Never')]
                    ],
                    'thumbnail_op_width' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Opening post thumbnails maximum width in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'thumbnail_op_height' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Opening post thumbnails maximum height in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'thumbnail_reply_width' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Reply thumbnails maximum width in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'thumbnail_reply_height' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Reply thumbnails maximum height in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'max_image_size_kilobytes' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Full image maximum size in kilobytes.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'max_image_size_width' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Full image maximum width in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'max_image_size_height' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('Full image maximum height in pixels.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'max_posts_count' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('The maximum replies for each thread.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => 25])],
                    ],
                    'max_images_count' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('The maximum image replies for each thread.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\Type('digit')],
                    ],
                    'cooldown_new_thread' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('The minimum delay to start new threads for each user in seconds.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\Type('digit')],
                    ],
                    'thread_lifetime' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('The amount of time a thread will stay alive in seconds.'),
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\Type('digit')],
                    ],
                    'min_image_repost_time' => [
                        'database' => true,
                        'boards_preferences' => true,
                        'label' => _i('The minimum delay between posting the same image in seconds.<br>(0 means no limit, -1 means never allowing a repost)'),
                       //this would probably look better under the text box.
                        'type' => 'input',
                        'class' => 'span1',
                        'validation' => [new Assert\NotBlank(), new Assert\GreaterThan(['value' => -2])],
                    ],
                ]
            ],
            'anonymous_default_name' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('The default name if a user doesn\'t enter a name.'),
                'type' => 'input',
                'class' => 'span3',
                'validation' => [new Assert\NotBlank()]
            ],
            'max_comment_characters_allowed' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('The maximum number of characters allowed in the post.'),
                'type' => 'input',
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Type('digit')]
            ],
            'max_comment_lines_allowed' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('The maximum number of lines in the post.'),
                'type' => 'input',
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Type('digit')]
            ],
            'cooldown_new_comment' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('The minimum delay between posts for each user in seconds.'),
                'type' => 'input',
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Type('digit')]
            ],
            'captcha_comment_link_limit' => [
                'database' => true,
                'boards_preferences' => true,
                'label' => _i('Maximum allowed `HTTP` links before triggering spam detection.'),
                'type' => 'input',
                'class' => 'span1',
                'validation' => [new Assert\NotBlank(), new Assert\Type('digit')]
            ],
            'transparent_spoiler' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Spoilers images to be semi-transparent. (Mods and Admins are not affected)'),
                'type' => 'checkbox',
            ],
            'enable_flags' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Display country flags of posters? (<a href="http://www.maxmind.com/en/geolocation_landing" target="_blank">Required GeoIP</a>)'),
                'type' => 'checkbox',
            ],
            'enable_animated_gif_thumbs' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Enable animated gif’s thumbnail creation. (CPU-heavy)'),
                'type' => 'checkbox',
            ],
            'display_exif' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Display EXIF data from images. (EXIF data is always stored in the database)'),
                'type' => 'checkbox',
                'disabled' => 'disabled',
            ],
            'enable_poster_hash' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Enable an IP-based code to poster hashes. (Temporarily distinguish Anonymous users)'),
                'type' => 'checkbox',
            ],
            'disable_ghost' => [
                'database' => true,
                'boards_preferences' => true,
                'help' => _i('Disable ghost replies.'),
                'type' => 'checkbox',
            ],
            'hide_thumbnails' => [
                'database' => true,
                'type' => 'checkbox',
                'help' => _i('Disable thumbnails.')
            ],
            'sphinx' => [
                'database' => true,
                'type' => 'checkbox',
                'help' => _i('Use SphinxSearch as search engine.')
            ],
            'hidden' => [
                'database' => true,
                'type' => 'checkbox',
                'help' => _i('Hide the board from public access. (Mods and Admins are not affected)')
            ],
        ];

        $structure = Hook::forge('Foolz\FoolFuuka\Model\RadixCollection::structure#var.structure')
            ->setParam('structure', $structure)
            ->execute()
            ->get($structure);

        $structure = array_merge($structure, [
            'separator-2' => ['type' => 'separator-short'],
            'submit' => [
                'type' => 'submit',
                'class' => 'btn-primary',
                'value' => _i('Submit')
            ],
            'close' => ['type' => 'close']
        ]);

        foreach ($structure as $key => $item) {
            $default = $this->config->get('foolz/foolfuuka', 'package', 'preferences.radix.'.$key);

            if ($default !== null) {
                $structure[$key]['default_value'] = $default;
            }

            $subs = ['sub', 'sub_inverse'];

            foreach ($subs as $inv) {
                if (isset($item[$inv])) {
                    foreach ($item[$inv] as $k => $i) {
                        $default = $this->config->get('foolz/foolfuuka', 'package', 'preferences.radix.'.$k);

                        if (!is_null($default)) {
                            $structure[$key][$inv][$k]['default_value'] = $default;
                        }
                    }
                }
            }
        }

        return $structure;
    }

    /**
     * Clears the APC/memcached cache
     */
    public function clearCache()
    {
        Cache::item('foolfuuka.model.radix.preload')->delete();
        Cache::item('foolfuuka.model.radix.load_preferences')->delete();
    }

    /**
     * Saves the data for a board. Plains the structure, runs the validation.
     * If 'id' is not set, it creates a new board.
     *
     * @param  array  $data  Associative array with the values for the structure
     */
    public function save($data)
    {
        // filter _boards data from _boards_preferences data
        $structure = $this->structure();
        $data_boards_preferences = [];

        foreach ($structure as $key => $item) {
            if ($item['type'] === 'internal') {
                // we don't use this function to edit internal preferences
                continue;
            }

            // mix the sub and sub_inverse and flatten the array
            if (isset($item['sub_inverse']) && isset($item['sub'])) {
                $item['sub'] = array_merge($item['sub'], $item['sub_inverse']);
            }

            if (isset($item['sub'])) {
                foreach ($item['sub'] as $k => $i) {
                    if (isset($i['boards_preferences'])) {
                        if (isset($data[$k])) {
                            $data_boards_preferences[$k] = $data[$k];
                        } else {
                            if ($i['type'] === 'checkbox') {
                                $data_boards_preferences[$k] = false;
                            } else {
                                $data_boards_preferences[$k] = null;
                            }
                        }

                        unset($data[$k]);
                    } elseif (isset($i['database'])) {
                        if (!isset($data[$k])) {
                            if ($i['type'] === 'checkbox') {
                                $data[$k] = false;
                            } else {
                                $data[$k] = null;
                            }
                        }
                    }
                }
            }

            if (isset($item['boards_preferences'])) {
                if (isset($data[$key])) {
                    $data_boards_preferences[$key] = $data[$key];
                } else {
                    if ($item['type'] === 'checkbox') {
                        $data_boards_preferences[$key] = false;
                    } else {
                        $data_boards_preferences[$key] = null;
                    }
                }

                unset($data[$key]);
            } elseif (isset($item['database'])) {
                if (!isset($data[$key])) {
                    if ($item['type'] === 'checkbox') {
                        $data[$key] = false;
                    } else {
                        $data[$key] = null;
                    }
                }
            }
        }

        $this->dc->getConnection()->beginTransaction();

        // data must be already sanitized through the form array
        if (isset($data['id'])) {
            if (!$radix = $this->getById($data['id'])) {
                // @todo proper error
                return;
            }

            // save normal values
            $update = $this->dc->qb()
                ->update($this->dc->p('boards'));

            foreach ($data as $k => $i) {
                $update->set($k, $this->dc->getConnection()->quote($i));
            }

            $update->where('id = :id')
                ->setParameter(':id', $data['id'])
                ->execute();

            // save extra preferences
            foreach ($data_boards_preferences as $name => $value) {
                $this->savePreferences($data['id'], $name, $value);
            }
        } else {
            // @TODO maybe this unset could be avoided
            unset($data['id']);

            $this->dc->getConnection()->insert($this->dc->p('boards'), $data);
            $id = $this->dc->getConnection()->lastInsertId($this->dc->p('boards_id_seq'));

            // save extra preferences
            foreach ($data_boards_preferences as $name => $value) {
                $this->savePreferences($id, $name, $value);
            }

            $this->clearCache();
            $this->preload();

            $board = $this->getById($id);
            $board->createTables();
        }

        $this->dc->getConnection()->commit();

        $this->clearCache();
        $this->preload();
    }


    /**
     * Insert custom preferences. One must use this for 'internal' preferences
     *
     * @param  \Foolz\Foolfuuka\Model\Radix|int  $board_id  can also be the board object
     * @param  string  $name   The name of the value to insert
     * @param  mixed   $value  The value to insert
     */
    public function savePreferences($board_id, $name, $value)
    {
        if (is_object($board_id)) {
            $board_id = $board_id->id;
        }

        $result = $this->dc->qb()
            ->select('COUNT(*) as count')
            ->from($this->dc->p('boards_preferences'), 'p')
            ->where('board_id = :board_id', 'name = :name')
            ->setParameter(':board_id', $board_id)
            ->setParameter(':name', $name)
            ->execute()
            ->fetch();

        if ($result['count']) {
            $this->dc->qb()
                ->update($this->dc->p('boards_preferences'))
                ->set('value', $this->dc->getConnection()->quote($value))
                ->where('board_id = :board_id', 'name = :name')
                ->setParameter(':board_id', $board_id)
                ->setParameter(':name', $name)
                ->execute();
        } else {
            $this->dc->getConnection()->insert($this->dc->p('boards_preferences'), [
                'board_id' => $board_id,
                'name' => $name,
                'value' => $value
            ]);
        }

        // only set if object exists
        if (isset($this->preloaded_radixes[$board_id])) {
            // avoid the complete reloading
            $this->preloaded_radixes[$board_id]->$name = $value;
        }

        $this->clearCache();
    }

    /**
     * Maintenance function to remove leftover _removed folders
     *
     * @param   boolean  $echo  echo CLI output
     *
     * @return  boolean  true on success, false on failure
     */
    public function removeLeftoverDirs($echo = false)
    {
        $all = $this->getAll();

        $array = [];

        // get all directories
        if ($handle = opendir($this->preferences->get('foolfuuka.boards.directory'))) {
            while (false !== ($file = readdir($handle))) {
                if (in_array($file, ['..', '.']))
                    continue;

                if (is_dir($this->preferences->get('foolfuuka.boards.directory').'/'.$file)) {
                    $array[] = $file;
                }
            }
            closedir($handle);
        } else {
            return false;
        }

        // make sure it's a removed folder
        foreach ($array as $key => $dir) {
            if (strpos($dir, '_removed') === false) {
                unset($array[$key]);
            }

            foreach ($all as $a) {
                if ($a->shortname === $dir) {
                    unset($array[$key]);
                }
            }
        }

        // exec the deletion
        foreach ($array as $dir) {
            $cmd = 'rm -Rv '.$this->preferences->get('foolfuuka.boards.directory').'/'.$dir;
            if ($echo) {
                echo $cmd.PHP_EOL;
                passthru($cmd);
                echo PHP_EOL;
            } else {
                exec($cmd).PHP_EOL;
            }
        }

        return true;
    }

    /**
     * Puts the table in readily available variables
     */
    public function preload()
    {
        $this->profiler->log('Radix::preload Start');

        try {
            $result = Cache::item('foolfuuka.model.radix.preload')->get();
        } catch (\OutOfBoundsException $e) {
            $result = $this->dc->qb()
                ->select('*')
                ->from($this->dc->p('boards'), 'b')
                ->orderBy('shortname', 'ASC')
                ->execute()
                ->fetchAll();

            Cache::item('foolfuuka.model.radix.preload')->set($result, 900);
        }

        if (!is_array($result) || empty($result)) {
            $this->preloaded_radixes = [];
            return false;
        }

        $result_object = [];

        foreach ($result as $item) {
            // don't process hidden boards
            if (!$this->getAuth()->hasAccess('boards.see_hidden') && $item['hidden'] == 1) {
                continue;
            }

            $structure = $this->structure($item);

            $result_object[$item['id']] = new Radix($this->getContext(), $this);

            // set the plain database data as keys
            foreach ($item as $k => $i) {
                $result_object[$item['id']]->$k = $i;

                // we set it also in the values so we can just use it from there as commodity
                $result_object[$item['id']]->setValue($k, $i);
            }

            // url values for commodity
            $result_object[$item['id']]->setValue('formatted_title', ($item['name']) ?
                '/'.$item['shortname'].'/ - '.$item['name'] : '/'.$item['shortname'].'/');

            // load the basic value of the preferences
            foreach ($structure as $key => $arr) {
                if (!isset($result_object[$item['id']]->$key) && isset($arr['boards_preferences'])) {
                    $result_object[$item['id']]->setValue($key, $this->config->get('foolz/foolfuuka', 'package', 'preferences.radix.'.$key, false));
                }

                foreach (['sub', 'sub_inverse'] as $sub) {
                    if (isset($arr[$sub])) {
                        foreach ($arr[$sub] as $k => $a) {
                            if (!isset($result_object[$item['id']]->$k) && isset($a['boards_preferences'])) {
                                $result_object[$item['id']]->setValue($k, $this->config->get('foolz/foolfuuka', 'package', 'preferences.radix.'.$k, false));
                            }
                        }
                    }
                }
            }
        }

        // load the preferences from the board_preferences table
        $this->profiler->log('Radix::load_preferences Start');
        try {
            $preferences = Cache::item('foolfuuka.model.radix.load_preferences')->get();
        } catch (\OutOfBoundsException $e) {
            $preferences = $this->dc->qb()
                ->select('*')
                ->from($this->dc->p('boards_preferences'), 'p')
                ->execute()
                ->fetchAll();

            Cache::item('foolfuuka.model.radix.load_preferences')->set($preferences, 900);
        }

        foreach ($preferences as $value) {
            // in case of leftover values, it would try instantiating a new stdClass and that would trigger error
            if (isset($result_object[$value['board_id']])) {
                $result_object[$value['board_id']]->setValue($value['name'], $value['value']);
            }
        }

        $this->preloaded_radixes = $result_object;
        $this->profiler->logMem('Radix $this->preloaded_radixes', $this->preloaded_radixes);
        $this->profiler->log('Radix::load_preferences End');

        // take them all and then filter/do whatever (we use this to split the boards through various subdomains)
        // only public is affected! admins and mods will see all boards at all the time
        $this->preloaded_radixes = Hook::forge('Foolz\FoolFuuka\Model\RadixCollection::preload#var.radixes')
            ->setObject($this)
            ->setParam('preloaded_radixes', $this->preloaded_radixes)
            ->execute()
            ->get($this->preloaded_radixes);

        $this->profiler->log('Radix::preload End');
        $this->profiler->logMem('Radix $this->preloaded_radixes w/ preferences', $this->preloaded_radixes);
    }

    /**
     * Returns all the radixes as array of objects
     *
     * @return  \Foolz\Foolfuuka\Model\Radix[]  the objects of the preloaded radixes
     */
    public function getAll()
    {
        if (!$this->preloaded_radixes) {
            $this->preload();
        }

        return $this->preloaded_radixes;
    }


    /**
     * Returns the single radix
     *
     * @param   int  $radix_id  the ID of the board
     *
     * @return  \Foolz\Foolfuuka\Model\Radix  false on failure, else the board object
     */
    public function getById($radix_id)
    {
        $items = $this->getAll();

        if (isset($items[$radix_id])) {
            return $items[$radix_id];
        }

        return false;
    }


    /**
     * Returns the single radix by type selected
     *
     * @param  string   $value   the value searched
     * @param  string   $type    the variable name on which to match
     * @param  boolean  $switch  true if it must be equal or false if not equal
     *
     * @return  \Foolz\Foolfuuka\Model\Radix  false if not found or the board object
     */
    public function getByType($value, $type, $switch = true)
    {
        $items = $this->getAll();

        foreach ($items as $item) {
            if ($switch == ($item->$type === $value)) {
                return $item;
            }
        }

        return false;
    }


    /**
     * Returns the single radix by shortname
     *
     * @param  string  $shortname  The shortname of the board
     *
     * @return  \Foolz\Foolfuuka\Model\Radix  the board with the shortname, false if not found
     */
    public function getByShortname($shortname)
    {
        return $this->getByType($shortname, 'shortname');
    }


    /**
     * Returns only the type specified (exam)
     *
     * @param  string   $type    the variable name
     * @param  boolean  $switch  the value to match
     *
     * @return  \Foolz\Foolfuuka\Model\Radix[]  the Radix objects
     */
    public function filterByType($type, $switch)
    {
        $items = $this->getAll();
        foreach ($items as $key => $item) {
            if ($item->$type != $switch) {
                unset($items[$key]);
            }
        }

        return $items;
    }


    /**
     * Returns an array of objects that are archives
     *
     * @return  \Foolz\Foolfuuka\Model\Radix[]  the board objects that are archives
     */
    public function getArchives()
    {
        return $this->filterByType('archive', true);
    }


    /**
     * Returns an array of objects that are boards (not archives)
     *
     * @return  \Foolz\Foolfuuka\Model\Radix[]  the board objects that are boards
     */
    public function getBoards()
    {
        return $this->filterByType('archive', false);
    }
}
