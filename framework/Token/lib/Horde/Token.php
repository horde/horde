<?php
/**
 * The Horde_Token:: class provides a common abstracted interface into the
 * various token generation mediums. It also includes all of the
 * functions for retrieving, storing, and checking tokens.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Token
 */
class Horde_Token
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Hash of parameters necessary to use the chosen backend.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete Horde_Token instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Token subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Token/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Token  The newly created concrete Horde_Token instance.
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        $driver = basename($driver);
        $class = __CLASS__;
        if ($driver == 'none') {
            $class .= '_' . ucfirst($driver);
        }

        if (!class_exists($class)) {
            /* If driver class doesn't exist or the driver is not
             * available just default to the parent class, and it is
             * not necessary to warn about degraded service. */
            $class = __CLASS__;
        }

        return new $class($params);
    }

    /**
     * Attempts to return a reference to a concrete Horde_Token instance based
     * on $driver.
     *
     * It will only create a new instance if no Horde_Token instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple types of token generators (and, thus,
     * multiple Horde_Token instances) are required.
     *
     * This method must be invoked as:
     * <code>$var = Horde_Token::singleton();</code>
     *
     * @param mixed $driver  The type of concrete Horde_Token subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Token/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Token  The concrete Horde_Token reference.
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $sig = hash('md5', serialize(array($driver, $params)));

        if (!isset(self::$_instances[$sig])) {
            self::$_instances[$sig] = self::factory($driver, $params);
        }

        return self::$_instances[$sig];
    }

    /**
     * Constructor.
     */
    protected function __construct($params)
    {
        $this->_params = $params;
    }

    /**
     * TODO
     */
    public function encodeRemoteAddress()
    {
        return isset($_SERVER['REMOTE_ADDR'])
            ? base64_encode($_SERVER['REMOTE_ADDR'])
            : '';
    }

    /**
     * Generates a connection id and returns it.
     *
     * @param string $seed  A unique ID to be included in the token.
     *
     * @return string  The generated id string.
     */
    public function generateId($seed = '')
    {
        return Horde_Util::uriB64Encode(pack('H*', sha1(uniqid(mt_rand(), true) . $seed . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''))));
    }

    /**
     * Checks if the given token has been previously used. First
     * purges all expired tokens. Then retrieves current tokens for
     * the given ip address. If the specified token was not found,
     * adds it.
     *
     * @param string $token  The value of the token to check.
     *
     * @return boolean  True if the token has not been used, false otherwise.
     * @throws Horde_Exception
     */
    public function verify($token)
    {
        $this->purge();

        if ($this->exists($token)) {
            return false;
        }

        $this->add($token);
        return true;
    }

    /**
     * This is an abstract method that should be overridden by a
     * subclass implementation. The base implementation allows all
     * token values.
     *
     * @throws Horde_Exception
     */
    public function exists()
    {
        return false;
    }

    /**
     * This is an abstract method that should be overridden by a
     * subclass implementation. The base implementation allows all
     * token values.
     *
     * @throws Horde_Exception
     */
    public function add()
    {
    }

    /**
     * This is an abstract method that should be overridden by a
     * subclass implementation. The base implementation allows all
     * token values.
     *
     * @throws Horde_Exception
     */
    public function purge()
    {
    }

}
