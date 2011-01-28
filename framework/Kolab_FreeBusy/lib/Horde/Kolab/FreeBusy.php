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
    static protected $instance;

    /**
     * The provider for dependency injection
     *
     * @var Horde_Provider_Base
     */
    protected $provider;

    /**
     * Constructor.
     *
     * @param array $params The parameters required to initialize the
     *                      application.
     */
    public function __construct($params = array())
    {
        $this->provider             = new Horde_Provider_Base();
        $this->provider->params     = $params;
        $this->provider->request    = new Horde_Provider_Injection_Factory(array('Horde_Kolab_FreeBusy_Factory', 'getRequest'));
        $this->provider->mapper     = new Horde_Provider_Injection_Factory(array('Horde_Kolab_FreeBusy_Factory', 'getMapper'));
        $this->provider->dispatcher = new Horde_Provider_Injection_Factory(array('Horde_Kolab_FreeBusy_Factory', 'getDispatcher'));
        $this->provider->logger     = new Horde_Provider_Injection_Factory(array('Horde_Kolab_FreeBusy_Factory', 'getLogger'));
        $this->provider->driver     = new Horde_Provider_Injection_Factory(array('Horde_Kolab_FreeBusy_Driver_Base', 'factory'));
    }

    /**
     * Get an element.
     *
     * @param string $key The key of the element to retrieve.
     *
     * @return mixed The element.
     */
    public function __get($key)
    {
        return $this->provider->{$key};
    }

    /**
     * Set an element to the given value.
     *
     * @param string $key   The key of the element to set.
     * @param mixed  $value The value to set the element to.
     *
     * @return NULL
     */
    public function __set($key, $value)
    {
        $this->provider->{$key} = $value;
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
            $this->provider->dispatcher->dispatch($this->provider->request);
        } catch (Exception $e) {
            //@todo: Error view
            throw $e;
        }
    }
}
