<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * Provides an API for various cryptographic systems used by Horde
 * applications.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */
class Horde_Crypt
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete Horde_Crypt instance based on $driver.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use (class must extend Horde_Crypt).
     * @param array $params   A hash containing any additional configuration
     *                        or parameters a subclass might need.
     *
     * @return Horde_Crypt  The newly created concrete instance.
     * @throws Horde_Crypt_Exception
     */
    public static function factory($driver, $params = array())
    {
        /* Return a base Horde_Crypt object if no driver is specified. */
        if (empty($driver) || (strcasecmp($driver, 'none') == 0)) {
            return new Horde_Crypt();
        }

        /* Base drivers (in Crypt/ directory). */
        $class = __CLASS__ . '_' . ucfirst(basename($driver));
        if (class_exists($class)) {
            return new $class($params);
        }

        /* Explicit class name, */
        $class = $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Crypt_Exception(
            __CLASS__ . ': Class definition of ' . $driver . ' not found.'
        );
    }

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - email_charset: (string) The default email charset.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge(array(
            'email_charset' => null,
        ), $params);
    }

    /**
     * Encrypt the requested data.
     * This method should be provided by all classes that extend Horde_Crypt.
     *
     * @param string $data   The data to encrypt.
     * @param array $params  An array of arguments needed to encrypt the data.
     *
     * @return array  The encrypted data.
     */
    public function encrypt($data, $params = array())
    {
        return $data;
    }

    /**
     * Decrypt the requested data.
     * This method should be provided by all classes that extend Horde_Crypt.
     *
     * @param string $data   The data to decrypt.
     * @param array $params  An array of arguments needed to decrypt the data.
     *
     * @return array  The decrypted data.
     * @throws Horde_Crypt_Exception
     */
    public function decrypt($data, $params = array())
    {
        return $data;
    }

}
