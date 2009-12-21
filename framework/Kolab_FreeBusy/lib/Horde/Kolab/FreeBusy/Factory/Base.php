<?php
/**
 * Factory methods for basic objects required by the export.
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
 * Factory methods for basic objects required by the export.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Factory
{

    /**
     * Create the object representing the current request.
     *
     * @param Horde_Injector $injector The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Controller_Request_Base The current request.
     *
     * @throws Horde_Exception
     */
    static public function getRequest($injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['request']) ? $configuration['request'] : array();
        if (!empty($params['class'])) {
            $request_class = $params['class'];
        } else {
            $request_class = 'Horde_Controller_Request_Http';
        }

        if (!empty($params['params'])) {
            $request_params = $params['params'];
        } else {
            $request_params = array();
        }

        /** Set up our request and routing objects */
        $request = new $request_class($request_params);

        /** The HTTP request object would hide errors. Display them. */
        if (isset($request->_exception)) {
            throw $request->_exception;
        }

        return $request;
    }

    /**
     * Create the mapper.
     *
     * @param Horde_Injector $injector The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Route_Mapper The mapper.
     *
     * @throws Horde_Exception
     */
    static public function getMapper($injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
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

        // Check for route definitions.
        if (!empty($configuration['config']['dir'])) {
            $routeFile = $configuration['config']['dir'] . '/routes.php';
        }
        if (empty($params['config']['dir'])
            || !file_exists($routeFile)) {
            $mapper->connect(':(callee).:(type)',
                             array('controller'   => 'freebusy',
                                   'action'       => 'fetch',
                                   'requirements' => array('type'   => '(i|x|v)fb',
                                                           'callee' => '[^/]+'),
                             ));

            $mapper->connect('trigger/*(folder).pfb',
                             array('controller'   => 'freebusy',
                                   'action'       => 'trigger'
                             ));

            $mapper->connect('*(folder).:(type)',
                             array('controller'   => 'freebusy',
                                   'action'       => 'trigger',
                                   'requirements' => array('type' => '(p|px)fb'),
                             ));

            $mapper->connect('delete/:(callee)',
                             array('controller'   => 'freebusy',
                                   'action'       => 'delete',
                                   'requirements' => array('callee' => '[^/]+'),
                             ));

            $mapper->connect('regenerate',
                             array('controller'   => 'freebusy',
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
     * @param Horde_Injector $injector The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Controller_Dispatcher The dispatcher.
     */
    static public function getDispatcher($injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['dispatch']) ? $configuration['dispatch'] : array();
        if (empty($params['controllerDir'])) {
            $controllerDir = dirname(__FILE__) . '/Controller';
        } else {
            $controllerDir = $params['controllerDir'];
        }

        if (empty($params['viewsDir'])) {
            $viewsDir = dirname(__FILE__) . '/View';
        } else {
            $viewsDir = $params['viewsDir'];
        }

        $context = array(
            'mapper'        => $injector->getInstance('Horde_Routes_Mapper'),
            'controllerDir' => $controllerDir,
            'viewsDir'      => $viewsDir,
            'logger'        => $injector->getInstance('Horde_Log_Logger');
        );

        $dispatcher = Horde_Controller_Dispatcher::singleton($context);

        return $dispatcher;
    }

    /**
     * Return the logger.
     *
     * @param Horde_Injector $injector The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Log_Logger The logger.
     */
    static public function getLogger($injector)
    {
        $logger = new Horde_Log_Logger();

        $configuration = $injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $logger_params = isset($configuration['logger']) ? $configuration['logger'] : array();

        if (empty($params)) {
            $handlers = array('Horde_Log_Handler_Null' => array());
        } else {
            $handlers = $logger_params['logger'];
        }

        foreach ($handlers as $name => $params) {
            if (!empty($params['params'])) {
                /**
                 *  We need to pass parameters to the constructor so use
                 *  reflection.
                 */
                $reflectionObj = new ReflectionClass($name);
                $handler       = $reflectionObj->newInstanceArgs($params['params']);
            } else {
                $handler = new $name();
            }

            if (!empty($params['options'])) {
                foreach ($params['options'] as $key => $value) {
                    $handler->setOption($key, $value);
                }
            }

            $logger->addHandler($handler);
        }
        return $logger;
    }
}
