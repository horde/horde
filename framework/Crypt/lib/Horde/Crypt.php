<?php
/**
 * The Horde_Crypt:: class provides an API for various cryptographic
 * systems used by Horde applications.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Crypt
 */
class Horde_Crypt
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * The temporary directory to use.
     *
     * @var string
     */
    protected $_tempdir;

    /**
     * Attempts to return a concrete Horde_Crypt instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Crypt subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Crypt/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       parameters a subclass might need.
     *
     * @return Horde_Crypt  The newly created concrete Horde_Crypt instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = basename($driv_name);
        } else {
            $driver = basename($driver);
        }

        /* Return a base Horde_Crypt object if no driver is specified. */
        if (empty($driver) || (strcasecmp($driver, 'none') == 0)) {
            return new Horde_Crypt();
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Crypt_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Attempts to return a reference to a concrete Horde_Crypt instance
     * based on $driver. It will only create a new instance if no
     * Horde_Crypt instance with the same parameters currently exists.
     *
     * This should be used if multiple crypto backends (and, thus,
     * multiple Horde_Crypt instances) are required.
     *
     * This method must be invoked as: $var = Horde_Crypt::singleton()
     *
     * @param mixed $driver  The type of concrete Horde_Crypt subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Crypt/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Crypt  The concrete Horde_Crypt reference.
     * @throws Horde_Exception
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = hash('md5', serialize(array($driver, $params)));

        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = Horde_Crypt::factory($driver, $params);
        }

        return self::$_instances[$signature];
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
     * @throws Horde_Exception
     */
    public function decrypt($data, $params = array())
    {
        return $data;
    }

    /**
     * Create a temporary file that will be deleted at the end of this
     * process.
     *
     * @param string  $descrip  Description string to use in filename.
     * @param boolean $delete   Delete the file automatically?
     *
     * @return string  Filename of a temporary file.
     */
    protected function _createTempFile($descrip = 'horde-crypt',
                                       $delete = true)
    {
        return Horde_Util::getTempFile($descrip, $delete, $this->_tempdir, true);
    }

}
