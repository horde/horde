<?php
/**
 * Factory methods for basic objects required by the free/busy export.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Factory methods for basic objects required by the free/busy export.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Factory_Type_Freebusy
{
    /**
     * The injector providing required dependencies.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector providing required dependencies.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;

        $this->_injector->bindImplementation(
            'Horde_Kolab_FreeBusy_Export_Freebusy_Backend',
            'Horde_Kolab_FreeBusy_Export_Freebusy_Backend_'
            . $this->_injector->getInstance('Horde_Kolab_FreeBusy')->getBackend()
        );
    }

    /**
     * Create the mapper.
     *
     * @return Horde_Route_Mapper The mapper.
     *
     * @throws Horde_Exception
     */
    public function getMapper()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['mapper']) ? $configuration['mapper'] : array();
        if (!empty($params['params'])) {
            $mapper_params = $params['params'];
        } else {
            $mapper_params = array();
        }
        $mapper = new Horde_Routes_Mapper($mapper_params);

        /**
         * Application routes are relative only to the application. Let the
         * mapper know where they start.
         */
        if (!empty($configuration['script'])) {
            $mapper->prefix = dirname($configuration['script']);
        } else {
            $mapper->prefix = dirname($_SERVER['PHP_SELF']);
        }

        if (empty($params['controller'])) {
            $params['controller'] = 'freebusy';
        }

        // Check for route definitions.
        if (!empty($configuration['config']['dir'])) {
            $routeFile = $configuration['config']['dir'] . '/routes.php';
        }
        if (empty($params['config']['dir'])
            || !file_exists($routeFile)) {
            $mapper->connect(':(callee).:(type)',
                             array('controller'   => $params['controller'],
                                   'action'       => 'fetch',
                                   'requirements' => array('type'   => '(i|x|v)fb',
                                                           'callee' => '[^/]+'),
                             ));

            $mapper->connect('trigger/*(folder).pfb',
                             array('controller'   => $params['controller'],
                                   'action'       => 'trigger'
                             ));

            $mapper->connect('*(folder).:(type)',
                             array('controller'   => $params['controller'],
                                   'action'       => 'trigger',
                                   'requirements' => array('type' => '(p|px)fb'),
                             ));

            $mapper->connect('delete/:(callee)',
                             array('controller'   => $params['controller'],
                                   'action'       => 'delete',
                                   'requirements' => array('callee' => '[^/]+'),
                             ));

            $mapper->connect('regenerate',
                             array('controller'   => $params['controller'],
                                   'action'       => 'regenerate',
                             ));
        } else {
            // Load application routes.
            include $routeFile;
        }
        return $mapper;
    }

    /**
     * Create the dispatcher.
     *
     * @return Horde_Controller_Dispatcher The dispatcher.
     */
    public function getDispatcher()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['dispatch']) ? $configuration['dispatch'] : array();
        if (empty($params['controllerDir'])) {
            $controllerDir = dirname(__FILE__) . '/../../Controller';
        } else {
            $controllerDir = $params['controllerDir'];
        }

        if (empty($params['viewsDir'])) {
            $viewsDir = dirname(__FILE__) . '/View';
        } else {
            $viewsDir = $params['viewsDir'];
        }

        $context = array(
            'mapper'        => $this->_injector->getInstance('Horde_Routes_Mapper'),
            'controllerDir' => $controllerDir,
            'viewsDir'      => $viewsDir,
            'logger'        => $this->_injector->getInstance('Horde_Log_Logger'),
        );

        $dispatcher = Horde_Controller_Dispatcher::singleton($context);

        return $dispatcher;
    }
}
