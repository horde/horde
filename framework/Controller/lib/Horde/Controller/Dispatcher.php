<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */

/**
 * Dispatch a request to the appropriate controller and execute the response.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */
class Horde_Controller_Dispatcher
{
    /** @var Horde_Controller_Dispatcher */
    private static $_instance;

    /** @var Horde_Routes_Mapper */
    protected $_mapper;

    /** @var Horde_Log_Logger */
    protected $_logger;

    /** @var Horde_Support_Inflector */
    protected $_inflector;

    /** @var string */
    protected $_controllerDir = '';

    /** @var string */
    protected $_viewsDir = '';

    /**
     * Singleton method. This should be the only way of instantiating a
     * Horde_Controller_Dispatcher object.
     *
     * @return Horde_Controller_Dispatcher
     */
    public static function singleton($context = array())
    {
        if (self::$_instance === null) {
            self::$_instance = new self($context);
        }
        return self::$_instance;
    }

    /**
     * Class constructor. Client code should use the singleton method to
     * instantiate objects.
     */
    protected function __construct($context)
    {
        if (!isset($context['mapper']) || ! $context['mapper'] instanceof Horde_Routes_Mapper) {
            throw new Horde_Controller_Exception('Mapper object missing from Dispatcher constructor');
        }

        foreach ($context as $key => $val) {
            $this->{'_' . $key} = $val;
        }

        // Make sure controller directory, if set, ends in a /.
        if ($this->_controllerDir && substr($this->_controllerDir, -1) != '/') {
            $this->_controllerDir .= '/';
        }

        // Set the mapper's controller directory and controllerScan
        if ($this->_controllerDir && !$this->_mapper->directory) {
            $this->_mapper->directory = $this->_controllerDir;
        }
        $scanner = new Horde_Controller_Scanner($this->_mapper);
        $this->_mapper->controllerScan = $scanner->getCallback();

        // Make sure views directory, if set, ends in a /.
        if ($this->_viewsDir && substr($this->_viewsDir, -1) != '/') {
            $this->_viewsDir .= '/';
        }

        // Make sure we have an inflector
        if (!$this->_inflector) {
            $this->_inflector = new Horde_Support_Inflector;
        }
    }

    /**
     * Get the route utilities for this dispatcher and its mapper.
     *
     * @return  Horde_Routes_Utils
     */
    public function getRouteUtils()
    {
        return $this->_mapper->utils;
    }

    /**
     * Dispatch the request to the correct controller.
     *
     * @param Horde_Controller_Request_Base $request
     */
    public function dispatch(Horde_Controller_Request_Base $request, $response = null)
    {
        $t = new Horde_Support_Timer;
        $t->push();

        if (! $response instanceof Horde_Controller_Response_Base) {
            // $response = new Horde_Controller_Response_Http;
            $response = new Horde_Controller_Response_Base;
        }

        // Recognize routes and process request
        $controller = $this->recognize($request);
        $response = $controller->process($request, $response);

        // Send response and log request
        $time = $t->pop();
        $this->_logRequest($request, $time);
        $response->send();
    }

    /**
     * Check if request path matches any Routes to get the controller
     *
     * @return  Horde_Controller_Base
     * @throws  Horde_Controller_Exception
     */
    public function recognize($request)
    {
        $path = $request->getPath();
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        $matchdata = $this->_mapper->match($path);
        if ($matchdata) {
            $hash = $this->formatMatchdata($matchdata);
        }

        if (empty($hash) || !isset($hash[':controller'])) {
            $msg = 'No routes match the path: "' . $request->getPath() . '"';
            throw new Horde_Controller_Exception($msg);
        }

        $request->setPathParams($hash);

        // try to load the class
        $controllerName = $hash[':controller'];
        if (!class_exists($controllerName, false)) {
            $path = $this->_controllerDir . $controllerName . '.php';
            if (file_exists($path)) {
                require $path;
            } else {
                $msg = "The Controller \"$controllerName\" does not exist at " . $path;
                throw new Horde_Controller_Exception($msg);
            }
        }

        $options = array(
            'viewsDir' => $this->_viewsDir,
        );
        return new $controllerName($options);
    }

    /**
     * Take the $matchdata returned by a Horde_Routes_Mapper match and add
     * in :controller and :action that are used by the rest of the framework.
     *
     * Format controller names: my_stuff => MyStuffController
     * Format action names:     action_name => actionName
     *
     * @param   array   $matchdata
     * @return  mixed   false | array
     */
    public function formatMatchdata($matchdata)
    {
        $ret = array();
        foreach ($matchdata as $key => $val) {
            if ($key == 'controller') {
                $ret[':controller'] = $this->_inflector->camelize($val) . 'Controller';
            } elseif ($key == 'action') {
                $ret[':action'] = $this->_inflector->camelize($val, 'lower');
            }

            $ret[$key] = $val;
        }
        return !empty($ret) && isset($ret['controller']) ? $ret : false;
    }

    /**
     * Log the http request
     *
     * @todo - get total query times
     *
     * @param   Horde_Controller_Request_Base $request
     * @param   int $totalTime
     */
    protected function _logRequest(Horde_Controller_Request_Base $request, $totalTime)
    {
        if (!is_callable(array($this->_logger, 'info'))) {
            return;
        }

        $queryTime  = 0; // total time to execute queries
        $queryCount = 0; // total queries performed
        $phpTime = $totalTime - $queryTime;

        // embed user info in log
        $uri    = $request->getUri();
        $method = $request->getMethod();

        $paramStr = 'PARAMS=' . $this->_formatLogParams($request->getAllParams());

        $msg = "$method $uri $totalTime ms (DB=$queryTime [$queryCount] PHP=$phpTime) $paramStr";
        $msg = wordwrap($msg, 80, "\n\t  ", 1);

        $this->_logger->info($msg);
    }

    /**
     * Formats the request parameters as a "key => value, key => value, ..." string
     * for the log file.
     *
     * @param array $params
     * @return string
     */
    protected function _formatLogParams($params)
    {
        $paramStr = '{';
        $count = 0;
        foreach ($params as $key => $value) {
            if ($key != 'controller'  && $key != 'action' &&
                $key != ':controller' && $key != ':action') {
                if ($count++ > 0) { $paramStr .= ', '; }

                $paramStr .= $key.' => ';

                if (is_array($value)) {
                    $paramStr .= $this->_formatLogParams($value);
                } elseif (is_object($value)) {
                    $paramStr .= get_class($value);
                } else {
                    $paramStr .= $value;
                }
            }
        }
        return $paramStr . '}';
    }

}
