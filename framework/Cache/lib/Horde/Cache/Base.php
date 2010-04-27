<?php
/**
 * The Horde_Cache_Base:: class provides the abstract class definition for
 * Horde_Cache drivers.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Cache
 */
abstract class Horde_Cache_Base
implements Horde_Cache
{
    /**
     * Cache parameters.
     *
     * @var array
     */
    protected $_params = array(
        'lifetime' => 86400
    );

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Construct a new Horde_Cache object.
     *
     * @param array $params  Parameter array:
     * <pre>
     * 'lifetime' - (integer) Lifetime of data, in seconds.
     *              DEFAULT: 86400 seconds
     * 'logger' - (Horde_Log_Logger) Log object to use for log/debug messages.
     * </pre>
     */
    public function __construct($params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Attempts to directly output a cached object.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return boolean  True if output or false if no object was found.
     */
    public function output($key, $lifetime = 1)
    {
        $data = $this->get($key, $lifetime);
        if ($data === false) {
            return false;
        }

        echo $data;
        return true;
    }

    /**
     * Determine the default lifetime for data.
     *
     * @param mixed $lifetime  The lifetime to use or null for default.
     *
     * @return integer  The lifetime, in seconds.
     */
    protected function _getLifetime($lifetime)
    {
        return is_null($lifetime) ? $this->_params['lifetime'] : $lifetime;
    }

}
