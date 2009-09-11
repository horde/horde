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
 * The Horde_Kolab_FreeBusy class serves as Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
 * @since    Horde 3.2
 */
class Horde_Kolab_FreeBusy
{
    /**
     * Singleton value.
     *
     * @var Horde_Kolab_FreeBusy
     */
    static protected $instance;

    /**
     * The object representing the request.
     *
     * @var Horde_Controller_Request_Base
     */
    private $_request;

    /**
     * The object representing the request<->controller mapping.
     *
     * @var Horde_Routes_Mapper
     */
    private $_mapper;

    /**
     * The request dispatcher.
     *
     * @var Horde_Controller_Dispatcher
     */
    private $_dispatcher;

    /**
     * Constructor.
     *
     * @param array $params The parameters required to initialize the
     *                      application.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Returns a reference to the global Horde_Kolab_FreeBusy object,
     * only creating it if it doesn't already exist.
     *
     * This method must be invoked as:
     *   $registry = Horde_Kolab_FreeBusy::singleton()
     *
     * We expect this method to be invoked *once* on application startup. At
     * that point the parameters need to be set to correctly initialize the
     * system.
     *
     * The singleton should then later be only used by the Controllers to access
     * different system components (by using the MVC system we loose connection
     * to this class within the controllers so we need global state here).
     *
     * @param array $params The parameters required to initialize the
     *                      application.
     * <pre>
     * 'script'  - (string) Script name in relation to the document root.
     *                      [optional]
     *
     * 'config'  - (array)  Indicates where to find configuration options.
     *                      [optional]
     *
     *     'dir'      - (string) Configuration files can be found in this
     *                           directory.
     *
     * 'request' - (array)  Options for the request object. [optional]
     *
     *     'class'    - (string) The class of request object to use (should
     *                           obviously match the request type).
     *     'params'   - (array)  Additional parameters to use on request
     *                           object construction.
     *
     * 'mapper'  - (array)  Options for the mapper object. [optional]
     *
     *     'params'   - (array)  Additional parameters to use on mapper
     *                           object construction.
     *
     * 'dispatch'- (array)  Options for the dispatcher object. [optional]
     *
     *     'controllerDir' - (string) The directory holding controllers.
     *     'viewsDir'      - (string) The directory holding views.
     *
     * </pre>
     *
     * @return Horde_Kolab_FreeBusy  The Horde_Registry instance.
     */
    static public function singleton($params = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new Horde_Kolab_FreeBusy($params);
        }

        return self::$instance;
    }

    /**
     * Destroy the application context.
     *
     * @return NULL
     */
    static public function destroy()
    {
        self::$instance = null;
    }

    /**
     * Inject the request object into the application context.
     *
     * @param Horde_Controller_Request_Base $request The object that should
     *                                               represent the current
     *                                               request.
     *
     * @return NULL
     */
    public function setRequest(Horde_Controller_Request_Base $request)
    {
        $this->_request = $request;
    }

    /**
     * Return the object representing the current request.
     *
     * @return Horde_Controller_Request_Base The current request.
     *
     * @throws Horde_Exception
     */
    public function getRequest()
    {
        if (!isset($this->_request)) {
            if (!empty($this->_params['request']['class'])) {
                $request_class = $this->_params['request']['class'];
            } else {
                $request_class = 'Horde_Controller_Request_Http';
            }
            if (!empty($this->_params['request']['params'])) {
                $params = $this->_params['request']['params'];
            } else {
                $params = array();
            }
            // Set up our request and routing objects
            $this->_request = new $request_class($params);
            /**
             * The HTTP request object would hide errors. Display them.
             */
            if (isset($this->request->_exception)) {
                throw $this->request->_exception;
            }
        }

        return $this->_request;
    }

    /**
     * Inject the mapper object into the application context.
     *
     * @param Horde_Route_Mapper $mapper The object that handles mapping.
     *
     * @return NULL
     */
    public function setMapper(Horde_Route_Mapper $mapper)
    {
        $this->_mapper = $mapper;
    }

    /**
     * Return the mapper.
     *
     * @return Horde_Route_Mapper The mapper.
     *
     * @throws Horde_Exception
     */
    public function getMapper()
    {
        if (!isset($this->_mapper)) {
            if (!empty($this->_params['mapper']['params'])) {
                $params = $this->_params['mapper']['params'];
            } else {
                $params = array();
            }
            $this->_mapper = new Horde_Routes_Mapper($params);

            /**
             * Application routes are relative only to the application. Let the
             * mapper know where they start.
             */
            if (!empty($this->_params['script'])) {
                $this->_mapper->prefix = dirname($this->_params['script']);
            } else {
                $this->_mapper->prefix = dirname($_SERVER['PHP_SELF']);
            }

            // Check for route definitions.
            if (!empty($this->_params['config']['dir'])) {
                $routeFile = $this->_params['config']['dir'] . '/routes.php';
            }
            if (empty($this->_params['config']['dir'])
                || !file_exists($routeFile)) {
                $this->_mapper->connect(':(mail).:(type)',
                                        array('controller'   => 'freebusy',
                                              'action'       => 'fetch',
                                              'requirements' => array('type' => '(i|x|v)fb',
                                                                      'mail'   => '[^/]+'),
                                        ));

                $this->_mapper->connect('trigger/*(folder).pfb',
                                        array('controller'   => 'freebusy',
                                              'action'       => 'trigger'
                                        ));

                $this->_mapper->connect('*(folder).:(type)',
                                        array('controller'   => 'freebusy',
                                              'action'       => 'trigger',
                                              'requirements' => array('type' => '(p|px)fb'),
                                        ));

                $this->_mapper->connect('delete/:(mail)',
                                        array('controller'   => 'freebusy',
                                              'action'       => 'delete',
                                              'requirements' => array('mail' => '[^/]+'),
                                        ));

                $this->_mapper->connect('regenerate',
                                        array('controller'   => 'freebusy',
                                              'action'       => 'regenerate',
                                        ));
            } else {
                // Load application routes.
                include $routeFile;
            }
        }

        return $this->_mapper;
    }

    /**
     * Inject the dispatcher object into the application context.
     *
     * @param Horde_Controller_Dispatcher $dispatcher The object that handles
     *                                                dispatching.
     *
     * @return NULL
     */
    public function setDispatcher(Horde_Controller_Dispatcher $dispatcher)
    {
        $this->_dispatcher = $dispatcher;
    }

    /**
     * Return the dispatcher.
     *
     * @return Horde_Controller_Dispatcher The dispatcher.
     *
     * @throws Horde_Exception
     */
    public function getDispatcher()
    {
        if (!isset($this->_dispatcher)) {
            if (empty($this->_params['dispatch']['controllerDir'])) {
                $controllerDir = dirname(__FILE__) . '/FreeBusy/Controller';
            } else {
                $controllerDir = $this->_params['dispatch']['controllerDir'];
            }

            if (empty($this->_params['dispatch']['viewsDir'])) {
                $viewsDir = dirname(__FILE__) . '/FreeBusy/View';
            } else {
                $viewsDir = $this->_params['dispatch']['viewsDir'];
            }

            $context = array(
                'mapper' => $this->getMapper(),
                'controllerDir' => $controllerDir,
                'viewsDir' => $viewsDir,
                // 'logger' => '',
            );

            $this->_dispatcher = Horde_Controller_Dispatcher::singleton($context);
        }

        return $this->_dispatcher;
    }

    /**
     * Handle the current request.
     *
     * @return NULL
     */
    public function dispatch()
    {
        try {
            $this->getDispatcher()->dispatch($this->getRequest());
        } catch (Exception $e) {
            //@todo: Error view
            throw $e;
        }
    }
}
