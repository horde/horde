<?php
/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Factory_Vfs extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the VFS instance.
     *
     * @param string $scope  The vfs scope to return.
     * @param array $params  Configuration parameters. If specified this
     *                       configuration is used instead of the configuration
     *                       from conf.php.
     *
     * @return Horde_Vfs  The VFS object.
     * @throws Horde_Exception
     */
    public function create($scope = 'horde', $params = null)
    {
        if (empty($this->_instances[$scope])) {
            if (!$params) {
                $params = $this->getConfig($scope);
            }

            $class = $this->_getDriverName($params['type'], 'Horde_Vfs');
            $this->_instances[$scope] = new $class($params['params']);
        }

        return $this->_instances[$scope];
    }

    /**
     * Returns the VFS driver parameters for the specified backend.
     *
     * @param string $name  The VFS system name being used.
     *
     * @return array  A hash with the VFS parameters; the VFS driver in 'type'
     *                and the connection parameters in 'params'.
     * @throws Horde_Exception
     */
    public function getConfig($name = 'horde')
    {
        global $conf;

        if ($name !== 'horde' && !isset($conf[$name]['type'])) {
            throw new Horde_Exception(Horde_Core_Translation::t("You must configure a VFS backend."));
        }

        $vfs = ($name == 'horde' || $conf[$name]['type'] == 'horde')
            ? $conf['vfs']
            : $conf[$name];

        switch (Horde_String::lower($vfs['type'])) {
        case 'sql':
        case 'sqlfile':
        case 'musql':
            if ($name == 'horde' || $conf[$name]['type'] == 'horde') {
                $vfs['params']['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
            } else {
                $config = Horde::getDriverConfig('vfs', 'sql');
                unset($config['umask'], $config['vfsroot']);
                $vfs['params']['db'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('horde', $config);
            }
            break;
        }

        return $vfs;
    }
}
