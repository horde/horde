<?php
/**
 * IMP_Quota:: provides an API for retrieving quota details from a mail
 * server.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package IMP_Quota
 */
class IMP_Quota
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a reference to a concrete IMP_Quota instance based on
     * $driver.
     *
     * It will only create a new instance if no instance with the same
     * parameters currently exists.
     *
     * This method must be invoked as: $var = &IMP_Quota::singleton()
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return mixed  The created concrete instance, or false on error.
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = md5(serialize(array($driver, $params)));

        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = IMP_Quota::factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete instance, or false on error.
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        require_once dirname(__FILE__) . '/Quota/' . $driver . '.php';
        $class = 'IMP_Quota_' . $driver;

        return class_exists($class)
            ? new $class($params)
            : false;
    }

    /**
     * Constructor.
     *
     * @param array $params  Hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;

        /* If 'password' exists in params, it has been encrypted in the
         * session so we need to decrypt. */
        if (isset($this->_params['password'])) {
            $this->_params['password'] = Horde_Secret::read(IMP::getAuthKey(), $this->_params['password']);
        }
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return mixed  Returns PEAR_Error on failure. Otherwise, returns an
     *                array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     */
    public function getQuota()
    {
        return array('usage' => 0, 'limit' => 0);
    }

    /**
     * Returns the quota messages variants, including sprintf placeholders.
     *
     * @return array  An array with quota message templates.
     */
    public function getMessages()
    {
        return array(
            'long' => isset($this->_params['format']['long'])
                ? $this->_params['format']['long']
                : _("Quota status: %.2f %s / %.2f %s  (%.2f%%)"),
            'short' => isset($this->_params['format']['short'])
                ? $this->_params['format']['short']
                : _("%.0f%% of %.0f %s"),
            'nolimit_long' => isset($this->_params['format']['nolimit_long'])
                ? $this->_params['format']['nolimit_long']
                : _("Quota status: %.2f %s / NO LIMIT"),
            'nolimit_short' => isset($this->_params['format']['nolimit_short'])
                ? $this->_params['format']['nolimit_short']
                : _("%.0f %s")
       );
    }

    /**
     * Determine the units of storage to display in the quota message.
     *
     * @return array  An array of size and unit type.
     */
    public function getUnit()
    {
        $unit = isset($this->_params['unit']) ? $this->_params['unit'] : 'MB';

        switch ($unit) {
        case 'GB':
            $calc = 1024 * 1024 * 1024.0;
            break;

        case 'KB':
            $calc = 1024.0;
            break;

        case 'MB':
        default:
            $calc = 1024 * 1024.0;
            $unit = 'MB';
            break;
        }

        return array($calc, $unit);
    }
}
