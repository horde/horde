<?php
/**
 * A Horde_Injector:: based Horde_Kolab_Storage:: factory.
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
 * A Horde_Injector:: based Horde_Kolab_Storage:: factory.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Core_Factory_KolabStorage extends Horde_Core_Factory_Base
{
    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);
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
    }

    /**
     * Provide configuration settings for Horde_Kolab_Session.
     *
     * @return NULL
     */
    private function _setupConfiguration()
    {
        $configuration = array();

        //@todo: Update configuration parameters
        if (!empty($GLOBALS['conf']['kolab']['imap'])) {
            $configuration = $GLOBALS['conf']['kolab']['imap'];
        }
        if (!empty($GLOBALS['conf']['kolab']['storage'])) {
            $configuration = $GLOBALS['conf']['kolab']['storage'];
        }

        $this->_injector->setInstance(
            'Horde_Kolab_Storage_Configuration', $configuration
        );
    }

    /**
     * Return the Horde_Kolab_Storage:: instance.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function create()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_Storage_Configuration');

        $session = $this->_injector->getInstance('Horde_Kolab_Session');

        $mail = $session->getMail();
        if (empty($mail)) {
            return false;
        }

        $params = array(
            'logger' => $this->_injector->getInstance('Horde_Log_Logger'),
            'timelog' => $this->_injector->getInstance('Horde_Log_Logger'),
            'cache' => $this->_injector->getInstance('Horde_Cache'),
            'driver' => 'horde',
            'params' => array(
                'host' => $session->getImapServer(),
                'username' => $GLOBALS['registry']->getAuth(),
                'password' => $GLOBALS['registry']->getAuthCredential('password'),
                'port'     => $configuration['port'],
                'secure'   => true
            )
        );

        $factory = new Horde_Kolab_Storage_Factory();
        $storage = $factory->createFromParams(
            $params
        );
        $storage->addListQuery(
            $storage->getList(), Horde_Kolab_Storage_List::QUERY_SHARE
        );
        return $storage;
    }
}
