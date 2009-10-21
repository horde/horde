<?php
/**
 * Revives an old Horde_Kolab_Session handler or generates a new one if
 * required.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/** We need the Auth library */
require_once 'Horde/Auth.php';

/**
 * Revives an old Horde_Kolab_Session handler or generates a new one if
 * required.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Factory
{
    /**
     * Horde_Kolab_Session instance.
     *
     * @var Horde_Kolab_Session
     */
    static private $_instance;

    /**
     * Setup the machinery to create Horde_Kolab_Session objects.
     *
     * @param array          $configuration The parameters required to create
     *                                      the desired Horde_Kolab_Server object.
     * @param Horde_Injector $injector      The object providing our dependencies.
     *
     * @return NULL
     */
    static public function setup(array $configuration, Horde_Injector $injector)
    {
        self::setupAuth($injector);
        self::setupStore($injector);

        self::setupLogger(
            $injector,
            isset($configuration['logger'])
            ? $configuration['logger'] : null
        );

        Horde_Kolab_Server_Factory::setupServer(
            $injector,
            isset($configuration['server'])
            ? $configuration['server'] : array()
        );

        self::setupConfiguration(
            $injector,
            isset($configuration['session'])
            ? $configuration['session'] : array()
        );

        self::setupSession($injector);
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Sesssion_Auth handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupAuth(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_Session_Auth',
            'Horde_Kolab_Session_Auth_Horde'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Sesssion_Store handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupStore(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_Session_Store',
            'Horde_Kolab_Session_Store_Sessionobjects'
        );
    }

    /**
     * Provide a log handler for Horde_Kolab_Session.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     * @param mixed          $instance The log handler or empty if it
     *                                 should be created.
     *
     * @return NULL
     */
    static protected function setupLogger(
        Horde_Injector $injector,
        $instance = null
    ) {
        if (empty($instance)) {
            $instance = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        }
        $injector->setInstance('Horde_Kolab_Session_Logger', $instance);
    }

    /**
     * Provide configuration settings for Horde_Kolab_Session.
     *
     * @param Horde_Injector $injector      The object providing our
     *                                      dependencies.
     * @param stdClass       $configuration The configuration parameters.
     *
     * @return NULL
     */
    static protected function setupConfiguration(
        Horde_Injector $injector,
        stdClass $configuration
    ) {
        $injector->setInstance(
            'Horde_Kolab_Session_Configuration', $configuration
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Sesssion handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupSession(Horde_Injector $injector)
    {
        $injector->bindFactory(
            'Horde_Kolab_Session',
            'Horde_Kolab_Session_Factory',
            'getSession'
        );
    }

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Session instance.
     *
     * It will only create a new instance if no Horde_Kolab_Session instance
     * currently exists or if a user ID has been specified that does not match the
     * user ID/user mail of the current session.
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return Horde_Kolab_Session The concrete Session reference.
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    static public function getSession(Horde_Injector $injector)
    {
        $config  = $injector->getInstance('Horde_Kolab_Session_Config');
        $logger  = $injector->getInstance('Horde_Kolab_Session_Logger');
        $store   = $injector->getInstance('Horde_Kolab_Session_Store');
        $auth    = $injector->getInstance('Horde_Kolab_Session_Auth');

        $session = $store->load();

        if (!empty($session)) {
            $session->setAuth($auth);
            $logged_session = new Horde_Kolab_Session_Logged($session, $logger);
            if ($logged_session->isValid($config->user)) {
                /**
                 * Return only the core session handler as this is only about
                 * data access and that needs no decorators.
                 */
                return $session;
            }
        }

        $server = $injector->getInstance('Horde_Kolab_Server');

        $session = new Horde_Kolab_Session_Base($config->user, $server, $config->params);
        $session->setAuth($auth);
        /** If we created a new session handler it needs to be stored once */
        $session = new Horde_Kolab_Session_Stored($session, $store);
        $session = new Horde_Kolab_Session_Logged($session, $logger);
        $session->connect($config->credentials);
        return $session;
    }

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Session instance.
     *
     * It will only create a new instance if no Horde_Kolab_Session instance
     * currently exists
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID. For Kolab this must either contain
     *                            the user id or the primary user mail address.
     *
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return Horde_Kolab_Session The concrete Session reference.
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    static public function singleton($user = null, $credentials = null)
    {
        global $conf;

        if (!isset(self::$_instance)) {
            $config['logger']  = Horde::getLogger();
            $config['server']  = $conf['kolab']['server'];
            $config['session']['user'] = $user;
            $config['session']['credentials'] = $credentials;
            //@todo
            $config['session']['params'] = array();
            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            self::setup($config, $injector);
            self::$_instance = $injector->getInstance('Horde_Kolab_Session');
            /**
             * Once we are not building our own provider here we need to take
             * care that the resulting session is checked for validity. Invalid
             * sessions need to be discarded an recreated with createInstance().
             *
             * if (!self::$_instance->isValid()) {
             *   self::$_instance = $injector->createInstance('Horde_Kolab_Session');
             *   $injector->setInstance('Horde_Kolab_Session', self::$_instance);
             * }
             */
        }
        return self::$_instance;
    }
}
