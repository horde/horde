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
     * Cache parameters:
     *   - driver (string)
     *   - lifetime (integer)
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Cache lifetime.
     */

    /**
     * Constructor.
     *
     * @param string $driver     Cache driver to use.
     * @param integer $lifetime  Cache lifetime.
     */
    public function __construct($driver, $lifetime = null)
    {
        $params = array('driver' => $driver);
        if (!is_null($lifetime)) {
            $params['lifetime'] = intval($lifetime);
        }

        $this->_initOb($params);
    }

    /**
     */
    protected function _initOb($params)
    {
        global $injector;

        $this->_params = $params;

        switch ($this->_params['driver']) {
        case 'cache':
            $ob = new Horde_Imap_Client_Cache_Backend_Cache(array_filter(array(
                'cacheob' => $injector->getInstance('Horde_Cache'),
                'lifetime' => (isset($this->_params['lifetime']) ? $this->_params['lifetime'] : null)
            )));
            break;

        case 'hashtable':
            $ob = new Horde_Imap_Client_Cache_Backend_Hashtable(array_filter(array(
                'hashtable' => $injector->getInstance('Horde_HashTable'),
                'lifetime' => (isset($this->_params['lifetime']) ? $this->_params['lifetime'] : null)
            )));
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
        return json_encode($this->_params);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_initOb(json_decode($data, true));
    }

}
