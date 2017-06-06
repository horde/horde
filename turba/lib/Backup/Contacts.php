<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @license http://www.horde.org/licenses/apache ASL
 * @package Turba
 */

namespace Turba\Backup;

use ArrayIterator;
use EmptyIterator;
use Iterator;
use Turba_Driver;

/**
 * Backup iterator for contacts.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class Contacts implements Iterator
{
    /**
     * The driver instance.
     *
     * @var Turba_Driver
     */
    protected $_driver;

    /**
     * The blob attributes.
     *
     * @var array
     */
    protected $_blobs;

    /**
     * The contacts iterator.
     *
     * @var Iterator
     */
    protected $_list;

    /**
     * Constructor.
     *
     * @param Turba_Driver $driver  A driver instance.
     */
    public function __construct(Turba_Driver $driver)
    {
        $this->_driver = $driver;
        $this->_blobs = array_keys($this->_driver->getBlobs());
    }

    // Iterator methods.

    /**
     */
    public function current()
    {
        $current = $this->_list->current();
        if (!$current) {
            return false;
        }
        $hash = $current->getAttributes();
        foreach ($this->_blobs as $blob) {
            if (strlen($hash[$blob])) {
                $hash[$blob] = base64_encode($hash[$blob]);
            }
        }
        return array(
            'addressbook' => $this->_driver->getName(),
            'contact' => $hash
        );
    }

    /**
     */
    public function key()
    {
        $current = $this->_list->current();
        if (!$current) {
            return false;
        }
        return $current->getValue('__key');
    }

    /**
     */
    public function next()
    {
        $this->_list->next();
    }

    /**
     */
    public function rewind()
    {
        if (!isset($this->_driver->map['__owner'])) {
            $this->_list = new EmptyIterator();
            return;
        }

        $this->_list = new ArrayIterator(
            $this->_driver->search(array())->objects
        );
    }

    /**
     */
    public function valid()
    {
        return $this->_list->valid();
    }
}
