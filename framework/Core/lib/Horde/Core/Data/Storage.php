<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Implement temporary data storage for the Horde_Data package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Data_Storage implements Horde_Data_Storage
{
    /* Data storage prefix. */
    const PREFIX = 'data_import';

    /**
     * The HashTable object.
     *
     * @var Horde_Core_HashTable_PersistentSession
     */
    private $_ht;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_ht = new Horde_Core_HashTable_PersistentSession();
    }

    /* Horde_Data_Storage methods. */

    /**
     */
    public function get($key)
    {
        global $injector;

        try {
            return $injector->getInstance('Horde_Pack')->unpack(
                $this->_ht->get($this->_hkey($key))
            );
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     */
    public function set($key, $value = null)
    {
        global $injector;

        if (is_null($value)) {
            $this->_ht->delete($this->_hkey($key));
        } else {
            $this->_ht->set(
                $this->_hkey($key),
                $injector->getInstance('Horde_Pack')->pack($value)
            );
        }
    }

    /**
     */
    public function exists($key)
    {
        return $this->_ht->exists($this->_hkey($key));
    }

    /**
     */
    public function clear()
    {
        $this->_ht->clear();
    }

    /* Internal methods. */

    /**
     * Return the hash key to use.
     *
     * @return string  Hash key.
     */
    private function _hkey($key)
    {
        return implode(':', array(
            self::PREFIX,
            $GLOBALS['registry']->getAuth(),
            $key
        ));
    }

}
