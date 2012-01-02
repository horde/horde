<?php
/**
 * Factory methods for basic objects required by the free/busy export.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Factory methods for basic objects required by the free/busy export.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_Factory_Base
extends Horde_Kolab_FreeBusy_Factory_Base
{
    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector providing required dependencies.
     */
    public function __construct(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_FreeBusy_Params_Owner',
            'Horde_Kolab_FreeBusy_Freebusy_Params_Folder'
        );
        parent::__construct($injector);
    }

    /**
     * Create the mapper.
     *
     * @return Horde_Route_Mapper The mapper.
     */
    public function createMapper()
    {
        $mapper = parent::createMapper();

        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['mapper']) ? $configuration['mapper'] : array();

        if (empty($params['controller'])) {
            $params['controller'] = 'freebusy';
        }

        // Check for route definitions.
        if (!empty($configuration['config']['dir'])) {
            $routeFile = $configuration['config']['dir'] . '/routes.php';
        }
        if (empty($params['config']['dir'])
            || !file_exists($routeFile)) {
            $mapper->connect(
                ':(owner).:(type)',
                array(
                    'controller'   => $params['controller'],
                    'action'       => 'fetch',
                    'requirements' => array(
                        'type'   => '(i|x|v)fb',
                        'owner' => '[^/]+'),
                )
            );
            $mapper->connect(
                'trigger/*(folder).pfb',
                array(
                    'controller'   => $params['controller'],
                    'action'       => 'trigger'
                )
            );

            $mapper->connect(
                '*(folder).:(type)',
                array(
                    'controller'   => $params['controller'],
                    'action'       => 'trigger',
                    'requirements' => array('type' => '(p|px)fb'),
                )
            );
            $mapper->connect(
                'delete/:(owner)',
                array(
                    'controller'   => $params['controller'],
                    'action'       => 'delete',
                    'requirements' => array('owner' => '[^/]+'),
                )
            );
            $mapper->connect(
                'regenerate',
                array(
                    'controller'   => $params['controller'],
                    'action'       => 'regenerate',
                )
            );
        } else {
            // Load application routes.
            include $routeFile;
        }
        return $mapper;
    }

    /**
     * Return the class name prefix for controllers.
     *
     * @return string The prefix.
     */
    protected function getControllerPrefix()
    {
        return 'Horde_Kolab_FreeBusy_Freebusy_Controller_';
    }
}
