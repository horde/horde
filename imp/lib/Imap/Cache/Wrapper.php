<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A serializable wrapper for the IMAP cache backend. Ensures that IMAP object
 * uses global Horde object for caching.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Imap_Cache_Wrapper implements Serializable
{
    /**
     * Cache object.
     *
     * @var Horde_Imap_Client_Cache_Backend
     */
    public $backend;

    /**
     * Cache driver to use.
     *
     * @var string
     */
    protected $_driver;

    /**
     * Constructor.
     *
     * @param string $driver  Cache driver to use.
     */
    public function __construct($driver)
    {
        $this->_driver = $driver;
        $this->_initOb();
    }

    /**
     */
    protected function _initOb()
    {
        global $injector;

        switch ($this->_driver) {
        case 'cache':
            $ob = new Horde_Imap_Client_Cache_Backend_Cache(array(
                'cacheob' => $injector->getInstance('Horde_Cache')
            ));
            break;

        case 'hashtable':
            $ob = new Horde_Imap_Client_Cache_Backend_Hashtable(array(
                'hashtable' => $injector->getInstance('Horde_HashTable')
            ));
            break;

        case 'nosql':
            $ob = new Horde_Imap_Client_Cache_Backend_Mongo(array(
                'mongo_db' => $injector->getInstance('Horde_Nosql_Adapter')
            ));
            break;

        case 'sql':
            $ob = new Horde_Imap_Client_Cache_Backend_Db(array(
                'db' => $injector->getInstance('Horde_Db_Adapter')
            ));
            break;
        }

        $this->backend = $ob;
    }

    /**
     * Redirects calls to the logger object.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->backend, $name), $arguments);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $this->_driver;
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_driver = $data;
        $this->_initOb();
    }

}
