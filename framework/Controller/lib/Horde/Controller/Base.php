<?php
/**
 * Base class for controllers that implements the Logged, Injected, and Viewed
 * interfaces.
 *
 * This class is for convenience, if you decide you wish to use only logging or
 * the injector or views, or neither, you do not have to use it.  As long as
 * your controllers implement Horde_Controller, they are runnable.
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
abstract class Horde_Controller_Base implements Horde_Controller
{
    /**
     * This is marked private on purpose, so that you have to use the
     * getInjector() method to access it in derived classes.  This is done so
     * that you don't assume its always set, since its set via setter-injection
     * to save on having to define a constructor param for it
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Private on purpose so you have to use getLogger().
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Private on purpose so you have to use getView().
     *
     * @var Horde_View
     */
    private $_view;

    /**
     * Private on purpose so you have to use getUrlWriter().
     *
     * @var Horde_Controller_UrlWriter
     */
    private $_urlWriter;

    /**
     * Set the injector for this controller
     *
     * @inject
     *
     * @param Horde_Injector  The injector that this controller should use to create objects
     */
    public function setInjector(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Get the injector for this controller
     *
     * @return Horde_Injector  The injector previously set for this controller,
     * or a new Horde_Injector_TopLevel
     */
    public function getInjector()
    {
        if (!$this->_injector) {
            $this->_injector = new Horde_Injector_TopLevel();
        }
        return $this->_injector;
    }

    /**
     * Set the Logger for this controller
     *
     * @inject
     *
     * @param Horde_Log_Logger The logger to use for this controller
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Get the logger assigned to this controller
     *
     * @return Horde_Log_Logger  The logger for this controller
     */
    public function getLogger()
    {
        if (!$this->_logger) {
            $this->_logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        }
        return $this->_logger;
    }

    /**
     * Set the Horde_View object to be used for this controller
     *
     * @inject
     *
     * @param Horde_View_Base  The view object
     */
    public function setView(Horde_View_Base $view)
    {
        $this->_view = $view;
        $this->_view->controller = $this;
    }

    /**
     * Gets the current view for this controller
     *
     * @note This method will create an empty Horde_View if none has been set.
     *
     * @return Horde_View_Base  The view for this controller, or a new empty
     * Horde_View if none is set
     */
    public function getView()
    {
        if (!$this->_view) {
            $this->setView($this->getInjector()->getInstance('Horde_View_Base'));
        }
        return $this->_view;
    }

    /**
     * Get the current request
     */
    public function getRequest()
    {
        return $this->getInjector()->getInstance('Horde_Controller_Request');
    }

    /**
     * Get the current response
     */
    public function getResponse()
    {
        return $this->getInjector()->getInstance('Horde_Controller_Response');
    }

    /**
     * Get an instance of UrlWriter for this controller.
     *
     * @return Horde_Controller_UrlWriter
     */
    public function getUrlWriter()
    {
        // instantiate UrlWriter that will generate URLs for this controller
        if (!$this->_urlWriter) {
            // Need a reasonable way to get the :controller match from the URL - reverse route?
            // $defaults = array('controller' => $this->getControllerName());
            $this->_urlWriter = $this->getInjector()->getInstance('Horde_Controller_UrlWriter');
        }
        return $this->_urlWriter;
    }
}
