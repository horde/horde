<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @license http://www.horde.org/licenses/apache ASL
 * @package Mnemo
 */

namespace Mnemo\Backup;

use ArrayIterator;
use Iterator;
use Mnemo_Driver;

/**
 * Backup iterator for notes.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Mnemo
 */
class Notes implements Iterator
{
    /**
     * The driver instance.
     *
     * @var Mnemo_Driver
     */
    protected $_driver;

    /**
     * Iterator over the driver instance.
     *
     * @var ArrayIterator
     */
    protected $_iterator;

    /**
     * Constructor.
     *
     * @param Mnemo_Driver $driver  A driver instance.
     */
    public function __construct(Mnemo_Driver $driver)
    {
        $this->_driver = $driver;
    }

    // Iterator methods.

    /**
     */
    public function current()
    {
        return array_intersect_key(
            $this->_iterator->current(),
            array(
                'memolist_id' => true,
                'uid' => true,
                'desc' => true,
                'body' => true,
                'tags' => true,
            )
        );
    }

    /**
     */
    public function key()
    {
        return $this->_iterator->key();
    }

    /**
     */
    public function next()
    {
        $this->_iterator->next();
    }

    /**
     */
    public function rewind()
    {
        $this->_driver->retrieve();
        $this->_iterator = new ArrayIterator($this->_driver->listMemos());
    }

    /**
     */
    public function valid()
    {
        return $this->_iterator->valid();
    }
}