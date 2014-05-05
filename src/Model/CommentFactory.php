<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Model;

class CommentFactory extends Model
{
    /**
     * Array of post numbers found in the database
     *
     * @var  array
     */
    public $posts = [];

    /**
     * Array of backlinks found in the posts
     *
     * @var  array
     */
    public $backlinks_arr = [];
}
