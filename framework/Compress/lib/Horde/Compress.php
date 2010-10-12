<?php
/**
 * The Horde_Compress:: class provides an API for various compression
 * techniques that can be used by Horde applications.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Compress
 */
class Horde_Compress
{
    /**
     * Translation provider.
     *
     * @var Horde_Translation
     */
    protected $_dict;

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Compress/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       parameters a subclass might need.
     *
     * @return Horde_Compress  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = null)
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = basename($driv_name);
        } else {
            $driver = basename($driver);
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Compress_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Constructor.
     *
     * @param Horde_Translation $dict  A translation handler implementing
     *                                 Horde_Translation.
     */
    public function __construct($dict = null)
    {
        if ($dict) {
            $this->_dict = $dict;
        } else {
            $this->_dict = new Horde_Translation_Gettext('Horde_Compress', dirname(__FILE__) . '/../../locale');
        }
    }

    /**
     * Compress the data.
     *
     * @param string $data   The data to compress.
     * @param array $params  An array of arguments needed to compress the data.
     *
     * @return mixed  The compressed data.
     * @throws Horde_Exception
     */
    public function compress($data, $params = array())
    {
        return $data;
    }

    /**
     * Decompress the data.
     *
     * @param string $data   The data to decompress.
     * @param array $params  An array of arguments needed to decompress the
     *                       data.
     *
     * @return array  The decompressed data.
     * @throws Horde_Exception
     */
    public function decompress($data, $params = array())
    {
        return $data;
    }

}
