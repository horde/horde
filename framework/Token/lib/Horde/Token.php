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
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Token
 */
class Horde_Token
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return.
     *                       If $driver is an array, then we will look
     *                       in $driver[0]/lib/Token/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Token_Driver  The newly created concrete instance.
     * @throws Horde_Token_Exception
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        $driver = basename($driver);
        $class = __CLASS__;
        if ($driver != 'none') {
            $class .= '_' . ucfirst($driver);
        }

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Token_Exception('Driver ' . $driver . ' not found.');
    }

    /**
     * Generates a connection id and returns it.
     *
     * @param string $seed  A unique ID to be included in the token.
     *
     * @return string  The generated id string.
     */
    static public function generateId($seed = '')
    {
        return Horde_Url::uriB64Encode(pack('H*', hash('sha1', uniqid(mt_rand()) . $seed . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''))));
    }

}
