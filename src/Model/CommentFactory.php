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
    public $_posts = [];

    /**
     * Array of backlinks found in the posts
     *
     * @var  array
     */
    public $_backlinks_arr = [];
}
