<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */
abstract class Horde_Controller_Base
{
    /**
     * Params is the list of variables set through routes.
     * @var Horde_Support_Array
     */
    protected $params;

    /**
     * Have we performed a render on this controller
     * @var boolean
     */
    protected $_performedRender = false;

    /**
     * Have we performed a redirect on this controller
     * @var boolean
     */
    protected $_performedRedirect = false;

    /**
     * The request object we are processing
     * @var Horde_Controller_Request_Base
     * @todo Assign default value.
     */
    protected $_request;

    /**
     * The response object we are returning
     * @var Horde_Controller_Response
     * @todo Assign default value.
     */
    protected $_response;

    /**
     * The current action being performed
     * @var string
     * @todo Assign default value.
     */
    protected $_action;

    /**
     * Normal methods available as action requests.
     * @var array
     */
    protected $_actionMethods = array();

    /**
     * @var string
     */
    protected $_viewsDir = '';

    /**
     * @var
     */
    protected $_urlWriter;

    /**
     * New controller instance
     */
    public function __construct($options)
    {
        foreach ($options as $key => $val) {
            $this->{'_' . $key} = $val;
        }
    }

    /**
     * Lazy loading of resources: view, ...
     *
     * @param string $name Property to load
     */
    public function __get($name)
    {
        switch ($name) {
        case '_view':
            $this->_view = new Horde_View;
            return $this->_view;
        }
    }

    /**
     * Process the {@link Horde_Controller_Request_Base} and return
     * the {@link Horde_Controller_Response}. This is the method that is called
     * for every request to be processed. It then determines which action to call
     * based on the parameters set within the {@link Horde_Controller_Request_Base}
     * object.
     *
     * <code>
     *  <?php
     *  ...
     *  $request  = new Horde_Controller_Request_Base();
     *  $response = new Horde_Controller_Response_Base();
     *
     *  $response = $controller->process($request, $response);
     *  ...
     *  ?>
     * </code>
     *
     * @param   Horde_Controller_Request_Base   $request
     * @param   Horde_Controller_Response_Base  $response
     * @return  Horde_Controller_Response_Base
     */
    public function process(Horde_Controller_Request_Base $request, Horde_Controller_Response_Base $response)
    {
        $this->_request = $request;
        $this->_response = $response;

        $this->_initParams();

        $this->_shortName = str_replace('Controller', '', $this->params[':controller']);

        try {
            // templates
            $this->_initActionMethods();
            $this->_initViewPaths();
            $this->_initViewHelpers();

            // Initialize application logic used through all actions
            $this->_initializeApplication();
            if ($this->_performed()) {
                return $this->_response;
            }

            // Initialize sub-controller logic used through all actions
            if (is_callable(array($this, '_initialize'))) {
                $this->_initialize();
            }

            // pre filters

            // execute action & save any changes to sessionData
            $this->{$this->_action}();

            // post filters

            // render default if we haven't performed an action yet
            if (!$this->_performed()) {
                $this->render();
            }
        } catch (Exception $e) {
            // error handling
        }

        return $this->_response;
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
            $defaults = array('controller' => $this->getControllerName());
            $this->_urlWriter = new Horde_Controller_UrlWriter($defaults);
        }
        return $this->_urlWriter;
    }

    /**
     * Get the current controller's name.
     *
     * @return string
     */
    protected function getControllerName()
    {
        if (empty($this->params)) {
            $this->_initParams();
        }

        return $this->params[':controller'];
    }

    /**
     * Render the response to the user. Actions are automatically rendered if no other
     * action is specified.
     *
     * <code>
     *  <?php
     *  ...
     *  $this->render(array('text'    => 'some text to render'));
     *  $this->render(array('action'  => 'actionName'));
     *  $this->render(array('nothing' => 1));
     *  ...
     *  ?>
     * </code>
     *
     * @see renderText()
     * @see renderAction()
     * @see renderNothing()
     *
     * @param array $options
     *
     * @throws  Horde_Controller_Exception
     */
    protected function render($options = array())
    {
        // should not render/redirect more than once.
        if ($this->_performed()) {
            throw new Horde_Controller_Exception("Double render error: \"$this->_action\"");
        }

        // validate options

        // set response status
        if (!empty($options['status'])) {
            $header = $this->interpretStatus($options['status']);
            $this->_response->setStatus($header);
        }

        // set response location
        if (!empty($options['location'])) {
            $url = $this->urlFor($options['location']);
            $this->_response->setHeader("Location: $url", $replace = true);
        }

        // render text
        if (!empty($options['text'])) {
            $this->renderText($options['text']);

        // render xml
        } elseif (!empty($options['xml'])) {
            $this->_response->setContentType('application/xml');

            $xml = $options['xml'];
            if (is_object($xml) && method_exists($xml, 'toXml')) {
                $xml = $xml->toXml();
            }

            $this->renderText($xml);

        // render template file
        } elseif (!empty($options['action'])) {
            $this->renderAction($options['action']);

        // render empty body
        } elseif (!empty($options['nothing'])) {
            $this->renderText('');

        // render default template
        } else {
            $this->renderAction($this->_action);
        }
    }

    /**
     * Render text directly to the screen without using a template
     *
     * <code>
     *  <?php
     *  ...
     *  $this->renderText('some text to render to the screen');
     *  ...
     *  ?>
     * </code>
     *
     * @param   string  $text
     */
    protected function renderText($text)
    {
        $this->_response->setBody($text);
        $this->_performedRender = true;
    }

    /**
     * The name of the action method will render by default.
     *
     * render 'listDocuments' template file
     * <code>
     *  <?php
     *  ...
     *  $this->renderAction('listDocuments');
     *  ...
     *  ?>
     * </code>
     *
     * @param   string  $name
     */
    protected function renderAction($name)
    {
        // current url
        $this->_view->currentUrl = $this->_request->getUri();

        // copy instance variables
        foreach (get_object_vars($this) as $key => $value) {
            $this->_view->$key = $value;
        }

        // add suffix
        if ($this->_actionConflict) {
            $name = str_replace('Action', '', $name);
        }
        if (strpos($name, '.') === false) {
            $name .= '.html.php';
        }

        // prepend this controller's "short name" only if the action was
        // specified without a controller "short name".
        // e.g. index           -> Shortname/index
        //      Shortname/index -> Shortname/index
        if (strpos($name, '/') === false) {
            // $name = $this->_shortName . '/' . $name;
        }

        if ($this->_useLayout) {
            $this->_view->contentForLayout = $this->_view->render($name);
            $text = $this->_view->render($this->_layoutName);
        } else {
            $text = $this->_view->render($name);
        }
        $this->renderText($text);
    }

    /**
     * Render blank content. This can be used anytime you want to send a 200 OK
     * response back to the user, but don't need to actually render any content.
     * This is mostly useful for ajax requests.
     *
     * <code>
     *  <?php
     *  ...
     *  $this->renderNothing();
     *  ...
     *  ?>
     * </code>
     */
    protected function renderNothing()
    {
        $this->renderText('');
    }

    /**
     * Check if a render or redirect has been performed
     * @return  boolean
     */
    protected function _performed()
    {
        return $this->_performedRender || $this->_performedRedirect;
    }

    /**
     * Each variable set through routing {@link Horde_Routes_Mapper} is
     * availabie in controllers using the $params array.
     *
     * The controller also has access to GET/POST arrays using $params
     *
     * The action method to be performed is stored in $this->params[':action'] key
     */
    protected function _initParams()
    {
        $this->params = new Horde_Support_Array($this->_request->getParameters());
        $this->_action = $this->params->get(':action');
    }

    /**
     * Set the list of public actions that are available for this Controller.
     * Subclasses can remove methods from being publicly called by calling
     * {@link hideAction()}.
     *
     * @throws  Horde_Controller_Exception
     */
    protected function _initActionMethods()
    {
        // Perform reflection to get the list of public methods
        $reflect = new ReflectionClass($this);
        $methods = $reflect->getMethods();
        foreach ($methods as $m) {
            if ($m->isPublic() && !$m->isConstructor() && !$m->isDestructor()  &&
                $m->getName() != 'process' && substr($m->getName(), 0, 1) != '_') {
                $this->_actionMethods[$m->getName()] = 1;
            }
        }

        // try action suffix.
        if (!isset($this->_actionMethods[$this->_action]) &&
            isset($this->_actionMethods[$this->_action.'Action'])) {
            $this->_actionConflict = true;
            $this->_action = $this->_action.'Action';
        }
        // action isn't set, but there is a methodMissing() catchall method
        if (!isset($this->_actionMethods[$this->_action]) &&
            isset($this->_actionMethods['methodMissing'])) {
            $this->_action = 'methodMissing';

        // make sure we have an action set, and that there is no methodMissing() method
        } elseif (!isset($this->_actionMethods[$this->_action]) &&
                  !isset($this->_actionMethods['methodMissing'])) {
            $msg = 'Missing action: '.get_class($this)."::".$this->_action;
            throw new Horde_Controller_Exception($msg);
        }
    }

    /**
     * Initialize the view paths where the templates reside for this controller.
     * These are added in FIFO order, so if we do $this->renderAction('foo'),
     * in the BarController, the order it will search these directories will be:
     *  1. /views/Bar/foo.html
     *  2. /views/shared/foo.html
     *  3. /views/layouts/foo.html
     *  4. /views/foo.html (the default)
     *
     * We can specify a directory to look in instead of relying on the default order
     * by doing $this->renderAction('shared/foo').
     */
    protected function _initViewPaths()
    {
        $this->_view->addTemplatePath($this->_viewsDir . 'layouts');
        $this->_view->addTemplatePath($this->_viewsDir . 'shared');
        $this->_view->addTemplatePath($this->_viewsDir . $this->_shortName);
    }

    /**
     * Initialize the default helpers for use in the views
     */
    protected function _initViewHelpers()
    {
        $controllerHelper = $this->_shortName . 'Helper';
        if (class_exists($controllerHelper)) {
            new $controllerHelper($this->_view);
        }
    }

    /**
     * This gets called before action is performed in a controller.
     * Override method in subclass to setup filters/helpers
     */
    protected function _initializeApplication(){
    }

}
