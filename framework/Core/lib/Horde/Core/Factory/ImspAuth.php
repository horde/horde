<?php
/**
 * A Horde_Injector:: based Horde_Imsp_Auth:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Imsp_Auth:: factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_ImspAuth
{
    /**
     * Instance cache
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     *
     * @var Horde_Injector
     */
    protected $_injector;

    /**
     * Constructor
     *
     * @param Horde_Injector $injector
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Attempts to return a concrete Horde_Imsp_Auth instance based on $driver.
     * Will only create a new object if one with the same parameters already
     * does not exist.
     *
     * @param string $driver  Type of IMSP_Auth subclass to return.
     * @param array $params   The driver parameters.
     *
     * @return Horde_Imsp_Auth
     */
    static public function create($driver, array $params = array())
    {
        //@TODO: Fix this.
        /* Check for any imtest driver instances and kill them.
           Otherwise, the socket will hang between requests from
           seperate drivers (an Auth request and an Options request).*/
        if (is_array(self::$_instances)) {
            foreach (self::$_instances as $obj) {
                if ($obj->getDriverType() == 'imtest') {
                    $obj->logout();
                }
            }
        }
        $signature = serialize(array($driver, $params));
        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::_factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

    /**
     * Attempts to return a concrete Horde_Imsp_Auth instance based on $driver
     * Must be called as &Horde_Imsp_Auth::factory()
     *
     * @param  string $driver Type of Horde_Imsp_Auth subclass to return.
     *
     * @return mixed  The created Horde_Imsp_Auth subclass.
     * @throws Horde_Exception
     */
    static protected function _factory($driver, array $params = array())
    {
        $class = $this->_getDriverName($driver, 'Horde_Imsp_Auth');

        // Verify user/pass
        if (empty($params['username'])) {
            $params['username'] = $GLOBALS['registry']->getAuth('bare');
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        }

        return new $class($params);
    }

}
