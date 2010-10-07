<?php
/**
 * A Horde_Injector:: based Horde_Vfs:: factory.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */

/**
 * A Horde_Injector:: based Horde_Vfs:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Factory_Vfs
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Obtain the Vfs instance.
     *
     * @param string $scope  The vfs scope to return.
     *
     * @return VFS  The VFS object
     */
    public function create($scope = 'horde')
    {
        if (empty($this->_instances[$scope])) {
            $params = $this->getConfig($scope);
            $this->_instances[$scope] = VFS::factory($params['type'], $params['params']);
        }

        return $this->_instances[$scope];
    }

    /**
     * Returns the VFS driver parameters for the specified backend.
     *
     * @param string $name  The VFS system name  being used.
     *
     * @return array  A hash with the VFS parameters; the VFS driver in 'type'
     *                and the connection parameters in 'params'.
     * @throws Horde_Exception
     */
    public function getConfig($name = 'horde')
    {
        global $conf;

        if (($name !== 'horde') && !isset($conf[$name]['type'])) {
            throw new Horde_Exception(_("You must configure a VFS backend."));
        }

        $vfs = ($name == 'horde' || $conf[$name]['type'] == 'horde')
            ? $conf['vfs']
            : $conf[$name];

        switch ($vfs['type']) {
        case 'sql':
            $db_pear = $this->_injector->getInstance('Horde_Core_Factory_DbPear');
            $vfs['params'] = $db_pear->getConfig('vfs');
            $vfs['params']['db'] = $db_pear->create('read', 'horde', 'vfs');
            $vfs['params']['writedb'] = $db_pear->create('rw', 'horde', 'vfs');
            break;

        case 'sql_file':
            $db_pear = $this->_injector->getInstance('Horde_Core_Factory_DbPear');
            $vfs['params'] = $db_pear->getConfig('vfs');
            $vfs['params']['db'] = $db_pear->create('rw', 'horde', 'vfs');
            break;
        }

        return $vfs;
    }

}
