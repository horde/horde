<?php
/**
 * A Horde_Injector:: based Horde_Imsp:: factory.
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
 * A Horde_Injector:: based Horde_Imsp:: factory.
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
class Horde_Core_Factory_Imsp extends Horde_Core_Factory_Base
{
    /**
     * The instance cache
     *
     * @var array
     */
    protected $_instances = array();

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
     * Create an instance of a Horde_Imsp class.
     *
     * @param type $driver   The driver type.
     * @param array $params  Driver params.
     *
     * @return Horde_Imsp  The Horde_Imsp object
     */
    public function create($driver = null, array $params = array())
    {
        $signature = serialize(array($driver, $params));
        if (!isset($this->_instances[$signature])) {
            $this->_instances[$signature] = self::_factory($driver, $params);
        }

        return $this->_instances[$signature];
    }

    /**
     * Factory method
     *
     * @param string $driver  The driver type, leave empty for client connection
     * @param array $params   The driver parameters, leave empty to use default.
     *
     * @return mixed The Horde_Imsp object or Horde_Imsp_Client object.
     * @throws Horde_Exception
     */
    protected function _factory($driver = null, array $params = array())
    {
        $driver = basename($driver);

        // Use global config if none passed in.
        if (empty($params)) {
            $params = $GLOBALS['conf']['imsp'];
        } elseif (empty($params['auth_method'])) {
            $params['auth_method'] = $GLOBALS['conf']['imsp']['auth_method'];
        }

        $params['authObj'] = $this->_injector->getInstance('Horde_Core_Factory_ImspAuth')->create($params['auth_method'], $params);
        // @TODO: Separate class for the imtest client?
        unset($params['auth_method']);
        try {
            $socket = new Horde_Imsp_Client_Socket($params);
        } catch (Horde_Imsp_Exception $e) {
            throw new Horde_Exception($e);
        }
        // Return the client itself if no requested driver.
        if (empty($driver)) {
            return $socket;
        }

        if (!$socket->authenticate($params)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $class = $this->_getDriverName($driver, 'Horde_Imsp');
        return new $class($socket, $params);
    }

}
