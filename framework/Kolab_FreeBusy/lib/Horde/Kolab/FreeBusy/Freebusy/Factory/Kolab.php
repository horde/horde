<?php
/**
 * Specific factory methods for the free/busy export from a Kolab backend.
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
 * Specific factory methods for the free/busy export from a Kolab backend.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
 * @since    Horde 3.2
 */
class Horde_Kolab_FreeBusy_Freebusy_Factory_Kolab
extends Horde_Kolab_FreeBusy_Freebusy_Factory_Base
{
    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector providing required dependencies.
     */
    /* public function __construct(Horde_Injector $injector) */
    /* { */
    /*     $this->_injector = $injector; */
    /*     $this->_injector->bindFactory( */
    /*         'Horde_Kolab_Storage_Folder', */
    /*         __CLASS__, */
    /*         'getFolder' */
    /*     ); */
    /*     $this->_injector->bindFactory( */
    /*         'Horde_Kolab_Storage_Configuration', */
    /*         __CLASS__, */
    /*         'getStorageConfiguration' */
    /*     ); */
    /* } */

    /**
     * Create the object representing the current user requesting the export.
     *
     * @return Horde_Kolab_FreeBusy_User_Interface The current user.
     *
     * @throws Horde_Exception
     */
    public function getUser()
    {
    }

    /**
     * Provide configuration settings for Horde_Kolab_Storage.
     *
     * @return NULL
     */
    public function getStorageConfiguration()
    {
        $configuration = array();

        //@todo: Update configuration parameters
        if (!empty($GLOBALS['conf']['kolab']['imap'])) {
            $configuration = $GLOBALS['conf']['kolab']['imap'];
        }
        if (!empty($GLOBALS['conf']['kolab']['storage'])) {
            $configuration = $GLOBALS['conf']['kolab']['storage'];
        }
        return $configuration;
    }

    /**
     * Return the Horde_Kolab_Storage:: instance.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function getStorage()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_Storage_Configuration');

        $owner = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Owner');
        $user  = $this->_injector->getInstance('Horde_Kolab_FreeBusy_Param_User');

        list($user, $pass) = $user->getCredentials();

        $params = array(
            'hostspec' => $owner->getResourceServer(),
            'username' => $user,
            'password' => $pass,
            'secure'   => true
        );

        $imap = Horde_Imap_Client::factory('socket', $params);

        //@todo: The Group package needs to be converted to H4
        require_once 'Horde/Group.php';

        $master = new Horde_Kolab_Storage_Driver_Imap(
            $imap,
            Group::singleton()
        );

        return new Horde_Kolab_Storage(
            $master,
            $params
        );
    }

    /**
     * Create the folder that provides our data.
     *
     * @return Horde_Kolab_Storage_Folder The folder.
     */
    public function getFolder()
    {
        $name = $this->_injector
            ->getInstance('Horde_Kolab_FreeBusy_Params_Freebusy_Resource_Kolab')
            ->getResourceId();
        
    }

    /**
     * Create the backend resource handler
     *
     * @return Horde_Kolab_FreeBusy_Resource The resource.
     */
    public function getResource()
    {
        return new Horde_Kolab_FreeBusy_Resource_Event_Kolab(
            $this->_injector->getInstance('Horde_Kolab_Storage_Folder')
        );
    }

}