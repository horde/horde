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
 * The Horde_Kolab_FreeBusy class holds the Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
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
class Horde_Kolab_FreeBusy
{
    /**
     * Singleton value.
     *
     * @var Horde_Kolab_FreeBusy
     */
    static private $_instance;

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
     * Constructor.
     *
     * @param array $params The parameters required to initialize the
     *                      application.
     */
    public function __construct($backend, $params = array())
    {
        $this->setBackend($backend);

        $this->_injector = new Horde_Injector(
            new Horde_Injector_TopLevel()
        );
        $this->set(
            'Horde_Kolab_FreeBusy_Configuration_Interface',
            $params
        );
        $this->_injector->bindFactory(
            'Horde_Controller_Request_Base',
            'Horde_Kolab_FreeBusy_Factory_Base',
            'getRequest'
        );
        $this->_injector->bindFactory(
            'Horde_Route_Mapper',
            'Horde_Kolab_FreeBusy_Factory_Base',
            'getMapper'
        );
        $this->_injector->bindFactory(
            'Horde_Controller_Dispatcher',
            'Horde_Kolab_FreeBusy_Factory_Base',
            'getDispatcher'
        );
        $this->_injector->bindFactory(
            'Horde_Log_Logger',
            'Horde_Kolab_FreeBusy_Factory_Base',
            'getLogger'
        );
    }

    /**
     * Sets the export type and prepares the injector for retrieval of the
     * correct elements required for the export.
     *
     * @param string $type The type of the requested export.
     *
     * @return NULL
     */
    public function setExport($type)
    {
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_User_Interface',
            'Horde_Kolab_FreeBusy_Factory_' . $type . '_' . $this->getBackend(),
            'getUser'
        );
        $this->_injector->bindFactory(
            'Horde_Kolab_FreeBusy_Resource_Interface',
            'Horde_Kolab_FreeBusy_Factory_' . $type . '_' . $this->getBackend(),
            'getResource'
        );
        $this->_injector->bindImpementation(
            'Horde_Kolab_FreeBusy_Export_Interface',
            'Horde_Kolab_FreeBusy_Export_' . $type . '_' . $this->getBackend()
        );
    }

    /**
     * Set the backend the application should use for the export.
     *
     * @param string $backend The backend used for the export.
     *
     * @return NULL
     */
    private function setBackend($backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Return the backend the application should use for the export.
     *
     * @return string The backend used for the export.
     */
    private function getBackend()
    {
        return $this->_backend;
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
     * 'logger'  - (array)  The keys of the array are log handler class names
     *                      (e.g. Horde_Log_Handler_Stream) while the
     *                      corresponding values are arrays. Each such array
     *                      may contain a key 'params' that holds parameters
     *                      passed to the constructor of the log handler. It
     *                      may also hold a second key 'options' with options
     *                      passed to the instantiated log handler. [optional]
     *
     * </pre>
     *
     * @return Horde_Kolab_FreeBusy  The Horde_Registry instance.
     */
    static public function singleton($params = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($params);
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
     * Handle the current request.
     *
     * @return NULL
     */
    public function dispatch()
    {
        try {
            $this->get('Horde_Controller_Dispatcher')->dispatch(
                $this->get('Horde_Controller_Request_Base')
            );
        } catch (Exception $e) {
            //@todo: Error view
            throw $e;
        }
    }
}
