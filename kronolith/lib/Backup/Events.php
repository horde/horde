<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @license http://www.horde.org/licenses/gpl GPL
 * @package Kronolith
 */

namespace Kronolith\Backup;

use ArrayIterator;
use EmptyIterator;
use Iterator;
use Kronolith_Driver;

/**
 * Backup iterator for events.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Kronolith
 */
class Events implements Iterator
{
    /**
     * The driver instance.
     *
     * @var Kronolith_Driver
     */
    protected $_driver;

    /**
     * The calendar ID.
     *
     * @var string
     */
    protected $_calendar;

    /**
     * The creator's user name.
     *
     * @var string
     */
    protected $_user;

    /**
     * Iterator over the day lists.
     *
     * @var ArrayIterator
     */
    protected $_iterator;

    /**
     * Iterator over the events of the current day.
     *
     * @var ArrayIterator
     */
    protected $_events;

    /**
     * Constructor.
     *
     * @param Kronolith_Driver $driver  A driver instance.
     * @param string $calendar          A calendar ID.
     * @param string $user              The creator to limit the returned
     *                                  events.
     */
    public function __construct(Kronolith_Driver $driver, $calendar, $user)
    {
        $this->_driver   = $driver;
        $this->_calendar = $calendar;
        $this->_user     = $user;
    }

    // Iterator methods.

    /**
     */
    public function current()
    {
        global $registry;

        $pushed = $registry->pushApp('kronolith', array('check_perms' => false));
        $event = $this->_events->current();
        if (!$event) {
            return false;
        }
        $event = $event->toHash();
        if ($pushed === true) {
            $registry->popApp();
        }
        return $event;
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
        return $this->_events->key();
    }

    /**
     */
    public function next()
    {
        $this->_next();
        while ($this->valid() && $this->current()['creator'] != $this->_user) {
            $this->_next();
        }
    }

    /**
     */
    protected function _next()
    {
        $this->_events->next();
        if (!$this->_events->valid()) {
            $this->_iterator->next();
            if ($current = $this->_iterator->current()) {
                $this->_events = new ArrayIterator($current);
            } else {
                $this->_events = new EmptyIterator();
            }
        }
    }

    /**
     */
    public function rewind()
    {
        global $registry;

        //$pushed = $registry->pushApp('kronolith', array('check_perms' => false));
        $this->_driver->open($this->_calendar);
        $this->_iterator = new ArrayIterator(
            $this->_driver->listEvents(
                null, null, array('cover_dates' => false, 'fetch_tags' => true)
            )
        );
        if ($current = $this->_iterator->current()) {
            $this->_events = new ArrayIterator($current);
        } else {
            $this->_events = new EmptyIterator();
        }
        if ($pushed === true) {
            $registry->popApp();
        }
    }

    /**
     */
    public function valid()
    {
        return $this->_iterator->valid();
    }
}
