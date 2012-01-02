<?php
/**
 * Generates test database connectors.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Generates test database connectors.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Factory_Share
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Create a SQL next generate share setup.
     *
     * @params array $params Additional options.
     * <pre>
     * 'app' - (string) The application name.
     * 'user' - (string) The current user.
     * </pre>
     *
     * @return Horde_Share_Sqlng The share setup.
     */
    public function create($params)
    {
        $shares = $this->_createShares('Horde_Share_Sqlng', $params);
        try {
            $db = $this->_injector->getInstance('Horde_Db_Adapter');
        } catch (Exception $e) {
            throw new Horde_Test_Exception(
                sprintf(
                    'Failed creating the "Horde_Db_Adapter" service: %s',
                    $e->getMessage()
                )
            );
        }
        $shares->setStorage($db);
        return $shares;
    }

    /**
     * Create a Kolab share setup.
     *
     * @params array $params Additional options.
     * <pre>
     * 'app' - (string) The application name.
     * 'user' - (string) The current user.
     * </pre>
     *
     * @return Horde_Share_Sqlng The share setup.
     */
    public function createKolab($params)
    {
        $shares = $this->_createShares('Horde_Share_Kolab', $params);
        try {
            $storage = $this->_injector->getInstance('Horde_Kolab_Storage');
        } catch (Exception $e) {
            throw new Horde_Test_Exception(
                sprintf(
                    'Failed creating the "Horde_Kolab_Storage" service: %s',
                    $e->getMessage()
                )
            );
        }
        $shares->setStorage($storage);
        return $shares;
    }

    /**
     * Create the share handler.
     *
     * @param string $class  Class name of the share handler.
     * @param array  $params Additional options.
     *
     * @return mixed The share handler.
     */
    private function _createShares($class, $params)
    {
        if (!class_exists($class)) {
            throw new Horde_Test_Exception("The \"$class\" class is unavailable!");
        }
        try {
            $perms = $this->_injector->getInstance('Horde_Perms');
        } catch (Exception $e) {
            throw new Horde_Test_Exception(
                sprintf(
                    'Failed creating the "Horde_Perms" service: %s',
                    $e->getMessage()
                )
            );
        }
        try {
            $group = $this->_injector->getInstance('Horde_Group');
        } catch (Exception $e) {
            throw new Horde_Test_Exception(
                sprintf(
                    'Failed creating the "Horde_Group" service: %s',
                    $e->getMessage()
                )
            );
        }
        return new $class($params['app'], $params['user'], $perms, $group);
    }
}