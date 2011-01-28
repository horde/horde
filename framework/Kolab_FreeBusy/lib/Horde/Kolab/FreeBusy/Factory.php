<?php
/**
 * The Kolab implementation of the free/busy system.
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
 * Factory methods for objects required by the free/busy system.
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
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Controller_Request_Base The current request.
     *
     * @throws Horde_Exception
     */
    static public function getRequest($provider)
    {
        $params = isset($provider->params['request']) ? $provider->params['request'] : array();
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
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Route_Mapper The mapper.
     *
     * @throws Horde_Exception
     */
    static public function getMapper($provider)
    {
        $params = isset($provider->params['mapper']) ? $provider->params['mapper'] : array();
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
        if (!empty($provider->params['script'])) {
            $mapper->prefix = dirname($provider->params['script']);
        } else {
            $mapper->prefix = dirname($_SERVER['PHP_SELF']);
        }

        // Check for route definitions.
        if (!empty($provider->params['config']['dir'])) {
            $routeFile = $provider->params['config']['dir'] . '/routes.php';
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
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Controller_Dispatcher The dispatcher.
     */
    static public function getDispatcher($provider)
    {
        $params = isset($provider->params['dispatch']) ? $provider->params['dispatch'] : array();
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
            'mapper'        => $provider->mapper,
            'controllerDir' => $controllerDir,
            'viewsDir'      => $viewsDir,
            'logger'        => $provider->logger,
        );

        $dispatcher = Horde_Controller_Dispatcher::singleton($context);

        return $dispatcher;
    }

    /**
     * Return the logger.
     *
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Log_Logger The logger.
     */
    static public function getLogger($provider)
    {
        $logger = new Horde_Log_Logger();

        $logger_params = isset($provider->params['logger']) ? $provider->params['logger'] : array();

        if (empty($params)) {
            $handlers = array('Horde_Log_Handler_Syslog' => array());
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
