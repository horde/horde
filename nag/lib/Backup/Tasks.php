<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @license http://www.horde.org/licenses/gpl GPL
 * @package Nag
 */

namespace Nag\Backup;

use Iterator;
use Nag;
use Nag_Driver;

/**
 * Backup iterator for tasks.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Nag
 */
class Tasks implements Iterator
{
    /**
     * The driver instance.
     *
     * @var Nag_Driver
     */
    protected $_driver;

    /**
     * Tasks iterator.
     *
     * @var Nag_Task
     */
    protected $_task;

    /**
     * The current task during iteration.
     *
     * @var Nag_Task
     */
    protected $_current;

    /**
     * Constructor.
     *
     * @param Nag_Driver $driver  A driver instance.
     */
    public function __construct(Nag_Driver $driver)
    {
        $this->_driver = $driver;
    }

    // Iterator methods.

    /**
     */
    public function current()
    {
        if (!$this->_current) {
            return false;
        }
        $hash = $this->_current->toHash();
        if ($hash['recurrence']) {
            $hash['recurrence'] = $hash['recurrence']->toHash();
        }
        return $hash;
    }

    /**
     */
    public function key()
    {
        return $this->_current ? $this->_current->id : false;
    }

    /**
     */
    public function next()
    {
        $this->_current = $this->_task->each();
    }

    /**
     */
    public function rewind()
    {
        global $registry;

        $pushed = $registry->pushApp('nag', array('check_perms' => false));
        $this->_driver->retrieve(Nag::VIEW_ALL, false);
        if ($pushed === true) {
            $registry->popApp();
        }
        $this->_task = $this->_driver->tasks;
        $this->_task->reset();
        $this->_current = $this->_task->each();
    }

    /**
     */
    public function valid()
    {
        return $this->_current !== false;
    }
}
