<?php
/**
 * Factory methods for basic objects required by the export.
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
 * Factory methods for basic objects required by the export.
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
class Horde_Kolab_FreeBusy_Factory_Base
implements Horde_Kolab_FreeBusy_Factory
{
    /**
     * The injector providing required dependencies.
     *
     * @var Horde_Injector
     */
    protected $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector providing required
     *                                 dependencies.
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
     * Create the instance that will output the response.
     *
     * @return Horde_Controller_ResponseWriter The response writer.
     *
     * @throws Horde_Exception
     */
    public function createResponseWriter()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['writer']) ? $configuration['writer'] : array();
        if (!empty($params['class'])) {
            $writer_class = $params['class'];
        } else {
            $writer_class = 'Horde_Controller_ResponseWriter_Web';
        }
        return new $writer_class();
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
    public function createLogger()
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

    /**
     * Create the mapper.
     *
     * @return Horde_Route_Mapper The mapper.
     */
    public function createMapper()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
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

        return $mapper;
    }

    /**
     * Create the request configuration.
     *
     * @return Horde_Controller_RequestConfiguration The request configuration.
     */
    public function createRequestConfiguration()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        if (isset($configuration['request_config']['prefix'])) {
            $prefix = $configuration['request_config']['prefix'];
        } else {
            $prefix = $this->getControllerPrefix();
        }

        $match = $this->_injector->getInstance(
            'Horde_Kolab_FreeBusy_Controller_MatchDict'
        )->getMatchDict();
        if (empty($match['controller']) ||
            !class_exists($prefix . ucfirst($match['controller']))) {
            $controller = 'Horde_Kolab_FreeBusy_Controller_NotFound';
        } else {
            $controller = $prefix . ucfirst($match['controller']);
        }

        $conf = new Horde_Kolab_FreeBusy_Controller_RequestConfiguration();
        $conf->setControllerName($controller);
        return $conf;
    }

    /**
     * Return the class name prefix for controllers.
     *
     * @return string The prefix.
     */
    protected function getControllerPrefix()
    {
        return 'Horde_Kolab_FreeBusy_Controller_';
    }

    /**
     * Create the user representation.
     *
     * @return Horde_Kolab_FreeBusy_User The user.
     */
    public function createUser()
    {
        list($user, $pass) = $this->_injector->getInstance(
                'Horde_Kolab_FreeBusy_Params_User'
        )->getCredentials();
        return $this->_injector->getInstance('Horde_Kolab_FreeBusy_UserDb')
            ->getUser(
                $user, $pass
            );
    }

    /**
     * Create the owner representation.
     *
     * @return Horde_Kolab_FreeBusy_Owner The owner.
     */
    public function createOwner()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['owner']) ? $configuration['owner'] : array();

        $params['user'] = $this->_injector->getInstance(
            'Horde_Kolab_FreeBusy_User'
        );
        return $this->_injector->getInstance('Horde_Kolab_FreeBusy_UserDb')
            ->getOwner(
                $this->_injector->getInstance(
                    'Horde_Kolab_FreeBusy_Params_Owner'
                )->getOwner(),
                $params
            );
    }

    /**
     * Create the data provider.
     *
     * @return Horde_Kolab_FreeBusy_Provider The provider.
     */
    public function createProvider()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Configuration');
        $params = isset($configuration['provider']) ? $configuration['provider'] : array();

        if (!isset($params['server'])) {
            $params['server'] = 'https://localhost/export';
        }

        $owner_fb = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Owner')
            ->getRemoteServer();
        if (!empty($owner_fb) && $owner_fb != $params['server']) {
            $this->_injector->getInstance('Horde_Log_Logger')->debug(
                sprintf(
                    "URL \"%s\" indicates remote free/busy server since we only offer \"%s\". Redirecting.", 
                    $owner_fb,
                    $params['server']
                )
            );
            if (empty($params['redirect'])) {
                return $this->_injector->getInstance(
                    'Horde_Kolab_FreeBusy_Provider_Remote_PassThrough'
                );
            } else {
                return $this->_injector->getInstance(
                    'Horde_Kolab_FreeBusy_Provider_Remote_Redirect'
                );
            }
        } else {
            return $this->_injector->getInstance(
                'Horde_Kolab_FreeBusy_Provider_Local'
            );
        }
    }
}
