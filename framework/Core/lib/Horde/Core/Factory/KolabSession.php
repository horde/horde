<?php
/**
 * A Horde_Injector:: based Horde_Kolab_Session:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Kolab_Session:: factory.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_KolabSession extends Horde_Core_Factory_Base
{
    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);
        $this->_injector->bindImplementation(
            'Horde_Kolab_Session_Storage',
            'Horde_Kolab_Session_Storage_Session'
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
    public function createSessionValidator(
        Horde_Kolab_Session $session,
        $auth
    ) {
        $validator = new Horde_Kolab_Session_Valid_Base(
            $session, $auth
        );

        if (isset($GLOBALS['conf']['kolab']['session']['debug'])) {
            $validator = new Horde_Kolab_Session_Valid_Decorator_Logged(
                $validator, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        return $validator;
    }

    /**
     * Returns a new session handler.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function createSession()
    {
        if (!isset($GLOBALS['conf']['kolab']['users'])) {
            $session = new Horde_Kolab_Session_Base(
                $this->_injector->getInstance('Horde_Kolab_Server_Composite'),
                $GLOBALS['conf']['kolab']
            );
        } else {
            $session = new Horde_Kolab_Session_Imap(
                new Horde_Kolab_Session_Factory_Imap(),
                $GLOBALS['conf']['kolab']
            );
        }

        if (isset($GLOBALS['conf']['kolab']['session']['debug'])) {
            $session = new Horde_Kolab_Session_Decorator_Logged(
                $session, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        $session = new Horde_Kolab_Session_Decorator_Stored(
            $session,
            $this->_injector->getInstance('Horde_Kolab_Session_Storage')
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
        $session = $this->createSession();

        $this->createSessionValidator(
            $session,
            $this->_injector->getInstance('Horde_Registry')->getAuth()
        )->validate();

        if (isset($GLOBALS['conf']['kolab']['session']['anonymous']['user'])
            && isset($GLOBALS['conf']['kolab']['session']['anonymous']['pass'])
        ) {
            $session = new Horde_Kolab_Session_Decorator_Anonymous(
                $session,
                $GLOBALS['conf']['kolab']['session']['anonymous']['user'],
                $GLOBALS['conf']['kolab']['session']['anonymous']['pass']
            );
        }

        return $session;
    }
}
