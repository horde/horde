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
class Horde_Kolab_FreeBusy_Factory_Base
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
    }

    /**
     * Create the object representing the current request.
     *
     * @return Horde_Controller_Request The current request.
     *
     * @throws Horde_Exception
     */
    public function createRequest()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
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

        return new $request_class($request_params);
    }

    /**
     * Create the view object.
     *
     * @return Horde_View The view helper.
     */
    public function createView()
    {
        $view = new Horde_View();
        $view->addHelper('Tag');
        $view->addHelper('Text');
        return $view;
    }

    /**
     * Return the logger.
     *
     * @return Horde_Log_Logger The logger.
     */
    public function getLogger()
    {
        $logger = new Horde_Log_Logger();

        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
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
