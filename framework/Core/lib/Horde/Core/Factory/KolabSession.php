<?php
/**
 * A Horde_Injector:: based Horde_Kolab_Session:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Kolab_Session:: factory.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_KolabSession
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector to use.
     */
    public function __construct(
        Horde_Injector $injector
    ) {
        $this->_injector      = $injector;
        $this->_setup();
    }

    /**
     * Setup the machinery to create Horde_Kolab_Session objects.
     *
     * @return NULL
     */
    private function _setup()
    {
        $this->_setupConfiguration();
        $this->_setupAuth();
        $this->_setupStorage();
    }

    /**
     * Provide configuration settings for Horde_Kolab_Session.
     *
     * @return NULL
     */
    private function _setupConfiguration()
    {
        $configuration = array();
        if (!empty($GLOBALS['conf']['kolab']['session'])) {
            $configuration = $GLOBALS['conf']['kolab']['session'];
        }
        $this->_injector->setInstance(
            'Horde_Kolab_Session_Configuration', $configuration
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Session_Auth handler.
     *
     * @return NULL
     */
    private function _setupAuth()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Session_Auth_Interface',
            'Horde_Kolab_Session_Auth_Horde'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Session_Storage handlers.
     *
     * @return NULL
     */
    private function _setupStorage()
    {
        $this->_injector->bindFactory(
            'Horde_Kolab_Session_Storage_Interface',
            'Horde_Core_Factory_KolabSession',
            'getStorage'
        );
    }

    /**
     * Return the session storage driver.
     *
     * @return Horde_Kolab_Session_Storage The driver for storing sessions.
     */
    public function getStorage()
    {
        return new Horde_Kolab_Session_Storage_Session(
            $GLOBALS['session']
        );
    }

    /**
     * Return the session validation driver.
     *
     * @param Horde_Kolab_Session $session The session to validate.
     * @param mixed               $auth    The user ID or false if no user is
     *                                     logged in.
     *
     * @return Horde_Kolab_Session_Valid_Interface The driver for validating
     *                                             sessions.
     */
    public function getSessionValidator(
        Horde_Kolab_Session $session,
        $auth
    ) {
        $configuration = $this->_injector->getInstance('Horde_Kolab_Session_Configuration');

        $validator = new Horde_Kolab_Session_Valid_Base(
            $session, $auth
        );

        if (isset($configuration['debug']) || isset($configuration['log'])) {
            $validator = new Horde_Kolab_Session_Valid_Decorator_Logged(
                $validator, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        return $validator;
    }

    /**
     * Validate the given session.
     *
     * @param Horde_Kolab_Session $session The session to validate.
     *
     * @return boolean True if the given session is valid.
     */
    public function validate(
        Horde_Kolab_Session $session
    ) {
        return $this->getSessionValidator(
            $session,
            $this->_injector->getInstance('Horde_Registry')->getAuth()
        )->isValid();
    }

    /**
     * Returns a new session handler.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function createSession()
    {
        $session = new Horde_Kolab_Session_Base(
            $this->_injector->getInstance('Horde_Kolab_Server_Composite'),
            $this->_injector->getInstance('Horde_Kolab_Session_Configuration')
        );

        //@todo: Fix validation
        /** If we created a new session handler it needs to be stored once */
        $session = new Horde_Kolab_Session_Decorator_Stored(
            $session,
            $this->_injector->getInstance('Horde_Kolab_Session_Storage_Interface')
        );
        return $session;
    }

    /**
     * Return the Horde_Kolab_Session:: instance.
     *
     * @return Horde_Kolab_Session The session handler.
     */
    public function create()
    {
        $storage = $this->_injector->getInstance('Horde_Kolab_Session_Storage_Interface');
        $session = $storage->load();

        if (empty($session) || !$this->validate($session)) {
            $session = $this->createSession();
        }

        $configuration = $this->_injector->getInstance('Horde_Kolab_Session_Configuration');


        if (isset($configuration['debug']) || isset($configuration['log'])) {
            $session = new Horde_Kolab_Session_Decorator_Logged(
                $session, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        if (isset($configuration['anonymous']['user'])
            && isset($configuration['anonymous']['pass'])
        ) {
            $session = new Horde_Kolab_Session_Decorator_Anonymous(
                $session,
                $configuration['anonymous']['user'],
                $configuration['anonymous']['pass']
            );
        }

        return $session;
    }
}
