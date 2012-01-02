<?php
/**
 * The Kolab implementation of the free/busy system.
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
 * The Horde_Kolab_FreeBusy class holds the Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
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
class Horde_Kolab_FreeBusy
{
    /**
     * The dependency injection container.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * The backend used for the export.
     *
     * @var string
     */
    private $_backend;

    /**
     * The export type.
     *
     * @var string
     */
    private $_export;

    /**
     * Class name of the factory.
     *
     * @var string
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param string $type    The export type.
     * @param string $backend The chosen backend.
     * @param array  $params  The parameters required to initialize the
     *                        application.
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
     * 'request_config'- (array)  Options for the request configuration. [optional]
     *
     *     'prefix' - (string) The class prefix to use for controllers.
     *
     * 'logger'  - (array)  The keys of the array are log handler class names
     *                      (e.g. Horde_Log_Handler_Stream) while the
     *                      corresponding values are arrays. Each such array
     *                      may contain a key 'params' that holds parameters
     *                      passed to the constructor of the log handler. It
     *                      may also hold a second key 'options' with options
     *                      passed to the instantiated log handler. [optional]
     * 'writer'  - (array)  Options for the response writer object. [optional]
     *
     *     'class'    - (string) The name of the response writer class.
     *
     * 'owner'  - (array)  Options for the data owner. [optional]
     *
     *     'domain'   - (string) The domain that will be assumed for
     *                           domainless owners.
     *
     * 'provider'     - (array)  Options for the data provider. [optional]
     *
     *     'server'   - (string) The URL that will be considered to be
     *                           provided locally rather than redirecting
     *                           to a remote server.
     *     'redirect' - (boolean) Should non-local requests be redirected
     *                            to the remote server or should the data
     *                            be fetched and passed through?
     * 'injector' - (Horde_Injector) An outside injector that allows to
     *                               inject arbitrary instance replacements.
     *                               [optional]
     *
     * </pre>
     */
    public function __construct($type, $backend, $params = array())
    {
        if (!isset($params['injector'])) {
            $this->_injector = new Horde_Injector(
                new Horde_Injector_TopLevel()
            );
        } else {
            $this->_injector = $params['injector'];
        }

        $this->set(
            'Horde_Kolab_FreeBusy_Configuration',
            $params
        );

        $this->_export = $type;
        $this->_backend = $backend;
        $this->_factory = 'Horde_Kolab_FreeBusy_' . $type . '_Factory_' . $backend;

        $this->bindings();

        $this->_injector->setInstance('Horde_Kolab_FreeBusy', $this);
    }

    /**
     * Setup the basic injector bindings.
     *
     * @return NULL
     */
    public function bindings()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_FreeBusy_Factory', $this->_factory
        );
        $this->_injector->bindFactory(
            'Horde_Routes_Mapper', $this->_factory, 'createMapper'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_Controller_RequestConfiguration',
            $this->_factory,
            'createRequestConfiguration'
        );
        $this->_injector->bindFactory(
            'Horde_Controller_Request', $this->_factory, 'createRequest'
        );
        $this->_injector->bindFactory(
            'Horde_View_Base', $this->_factory, 'createView'
        );
        $this->_injector->bindFactory(
            'Horde_Controller_ResponseWriter',
            $this->_factory,
            'createResponseWriter'
        );
        $this->_injector->bindFactory(
            'Horde_Log_Logger', $this->_factory, 'createLogger'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_User', $this->_factory, 'createUser'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_Owner', $this->_factory, 'createOwner'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_Resource', $this->_factory, 'createResource'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_Provider', $this->_factory, 'createProvider'
        );
    }

    /**
     * Return the backend the application uses for the export.
     *
     * @return string The backend used for the export.
     */
    public function getBackend()
    {
        return $this->_backend;
    }

    /**
     * Return the export type.
     *
     * @return string The export type.
     */
    public function getExportType()
    {
        return $this->_export;
    }

    /**
     * Get an element.
     *
     * @param string $interface The element to retrieve.
     *
     * @return mixed The element.
     */
    public function get($interface)
    {
        return $this->_injector->getInstance($interface);
    }

    /**
     * Set an element to the given value.
     *
     * @param string $interface The element to set.
     * @param mixed  $instance  The value to set the element to.
     *
     * @return NULL
     */
    public function set($interface, $instance)
    {
        return $this->_injector->setInstance($interface, $instance);
    }

    /**
     * Handle the current request.
     *
     * @return NULL
     */
    public function dispatch()
    {
        try {
            $this->get('Horde_Controller_ResponseWriter')->writeResponse(
                $this->get('Horde_Controller_Runner')->execute(
                    $this->_injector,
                    $this->get('Horde_Controller_Request'),
                    $this->get('Horde_Kolab_FreeBusy_Controller_RequestConfiguration')
                )
            );
        } catch (Exception $e) {
            $this->_injector->bindFactory(
                'Horde_Controller_ResponseWriter',
                'Horde_Kolab_FreeBusy_Factory_Base',
                'createResponseWriter'
            );
            $response = $this->_injector->createInstance('Horde_Controller_Response');
            $response->setHeaders(array('Status' => '404 Not Found', 'HTTP/1.0' => '404 Not Found'));
            $response->setBody($e->getMessage());
            $this->get('Horde_Controller_ResponseWriter')->writeResponse(
                $response
            );
        }
    }
}
