<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Abstract interface for the object used to connect to remote mail server.
 *
 * NOTE: This class is NOT intended to be accessed outside of the package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 *
 * @property-read boolean $connected  Is there a connection to the server?
 * @property-read boolean $secure  Is the connection secure?
 */
abstract class Horde_Imap_Client_Base_Connection
{
    /**
     * Is there a connection to the server?
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Debug object.
     *
     * @var object
     */
    protected $_debug;

    /**
     * Is the connection secure?
     *
     * @var boolean
     */
    protected $_secure = false;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Base $base  The base client object.
     * @param object $debug                 The debug handler.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function __construct(Horde_Imap_Client_Base $base, $debug)
    {
        if (($secure = $base->getParam('secure')) &&
            !extension_loaded('openssl')) {
            if ($secure !== true) {
                throw new InvalidArgumentException('Secure connections require the PHP openssl extension.');
            }

            $base->setParam('secure', false);
        }

        $this->_debug = $debug;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'connected':
            return $this->_connected;

        case 'secure':
            return $this->_secure;
        }
    }

    /**
     * This object can not be cloned.
     */
    public function __clone()
    {
        throw new LogicException('Object cannot be cloned.');
    }

    /**
     * This object can not be serialized.
     */
    public function __sleep()
    {
        throw new LogicException('Object can not be serialized.');
    }

    /**
     * Start a TLS connection to the server.
     *
     * @return boolean  Whether TLS was successfully started.
     */
    abstract public function startTls();

    /**
     * Close the connection to the server.
     */
    abstract public function close();

}
