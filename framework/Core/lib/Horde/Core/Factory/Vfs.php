<?php
/**
 * A Horde_Injector:: based Horde_Vfs:: factory.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * A Horde_Injector:: based Horde_Vfs:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Core_Factory_Vfs
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
    public function getVfs($scope)
    {
        $params = Horde::getVFSConfig($scope);
        return VFS::singleton($params['type'], $params['params']);
    }

}