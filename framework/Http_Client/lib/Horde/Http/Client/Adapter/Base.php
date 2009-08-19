<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http_Client
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http_Client
 */
class Horde_Http_Client_Adapter_Base
{
    /**
     * Proxy server
     * @var string
     */
    protected $_proxyServer = null;

    /**
     * Proxy username
     * @var string
     */
    protected $_proxyUser = null;

    /**
     * Proxy password
     * @var string
     */
    protected $_proxyPass = null;

    /**
     * HTTP timeout
     * @var float
     */
    protected $_timeout = 5;

    /**
     * Constructor
     */
    public function __construct($args = array())
    {
        foreach ($args as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Get an adapter parameter
     *
     * @param string $name  The parameter to get.
     * @return mixed        Parameter value.
     */
    public function __get($name)
    {
        return isset($this->{'_' . $name}) ? $this->{'_' . $name} : null;
    }

    /**
     * Set an adapter parameter
     *
     * @param string $name   The parameter to set.
     * @param mixed  $value  Parameter value.
     */
    public function __set($name, $value)
    {
        $this->{'_' . $name} = $value;
    }
}
