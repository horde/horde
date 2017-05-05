<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Backup
 */

namespace Horde\Backup;

use Iterator;

/**
 * An application's list of users with backup data.
 *
 * Each application must extend this class and implement the Iterator
 * methods so that the backup data can be returned on demand.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Backup
 */
class Users implements Iterator
{
    /**
     * The user list.
     *
     * @var Iterator
     */
    protected $_users;

    /**
     * User creation callback.
     *
     * @var callback
     */
    protected $_getUser;

    /**
     * Constructor.
     *
     * @param Iterator $users    A user list.
     * @param callable $getUser  Callback to create a \Horde\Backup\User
     *                           instance.
     */
    public function __construct(Iterator $users, callable $getUser)
    {
        $this->_users = $users;
        $this->_getUser = $getUser;
    }

    /**
     * A single user's backup data.
     *
     * @return \Horde\Backup\User
     */
    public function current()
    {
        return call_user_func($this->_getUser, $this->key());
    }

    /**
     * A user name.
     *
     * @return string
     */
    public function key()
    {
        return $this->_users->current();
    }

    /**
     */
    public function next()
    {
        $this->_users->next();
    }

    /**
     */
    public function rewind()
    {
        $this->_users->rewind();
    }

    /**
     */
    public function valid()
    {
        return $this->_users->valid();
    }
}
