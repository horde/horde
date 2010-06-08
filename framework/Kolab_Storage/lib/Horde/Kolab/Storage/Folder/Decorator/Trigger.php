<?php
/**
 * This decorator triggers a URL following certain actions on the folder.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * This decorator triggers a URL following certain actions on the folder.
 *
 * Copyright 2008-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Decorator_Trigger
extends Horde_Kolab_Storage_Folder_Decorator_Base
{
    /**
     * An output for log messages.
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Folder $folder The folder to be decorated.
     * @param Horde_Log_Logger           $logger The logger.
     */
    public function __construct(
        Horde_Kolab_Storage_Folder $folder,
        Horde_Log_Logger $logger
    ) {
        $this->_logger = $logger;
        parent::__construct($folder);
    }

    /**
     * Saves the folder.
     *
     * @param array $attributes An array of folder attributes. You can
     *                          set any attribute but there are a few
     *                          special ones like 'type', 'default',
     *                          'owner' and 'desc'.
     *
     * @return NULL
     */
    public function save($attributes = null)
    {
        /**
         * Trigger the old folder on an empty IMAP folder after renaming a folder!
         */
        try {
            $this->_connection->create($this->name);
            $this->_connection->setAnnotation(self::ANNOT_FOLDER_TYPE,
                                              $this->_type,
                                              $this->name);
            $this->trigger($this->name);
            $this->_connection->delete($this->name);
        } catch (Exception $e) {
            Horde::logMessage(sprintf('Failed handling the dummy folder: %s!',
                                      $e->getMessage()), 'ERR');
        }


        /** Finally trigger the folder after saving.*/
        try {
            $this->trigger();
        } catch (Horde_Kolab_Storage_Exception $e) {
            Horde::logMessage(sprintf('Failed triggering folder %s! Error was: %s',
                                      $this->name, $e->getMessage()), 'ERR');
        }


    }

    /**
     * Delete the specified message from this folder.
     *
     * @param  string  $id      IMAP id of the message to be deleted.
     * @param  boolean $trigger Should the folder be triggered?
     *
     * @return NULL
     */
    public function deleteMessage($id, $trigger = true)
    {
        $this->_folder->deleteMessage($id, $trigger);

        if ($trigger) {
            try {
                $result = $this->trigger();
            } catch (Horde_Kolab_Storage_Exception $e) {
                Horde::logMessage(sprintf('Failed triggering folder %s! Error was: %s',
                                          $this->name, $result->getMessage()), 'ERR');
            }
        }
    }

    /**
     * Move the specified message to the specified folder.
     *
     * @param string $id     IMAP id of the message to be moved.
     * @param string $folder Name of the receiving folder.
     *
     * @return NULL
     */
    public function moveMessage($id, $folder)
    {
        $this->_folder->moveMessage($id, $folder);

        //@todo: shouldn't we trigger both folders here?

        $result = $this->trigger();
    }

    /**
     * Move the specified message to the specified share.
     *
     * @param string $id    IMAP id of the message to be moved.
     * @param string $share Name of the receiving share.
     *
     * @return NULL
     */
    public function moveMessageToShare($id, $share)
    {
        $this->_folder->moveMessageToShare($id, $share);

        //@todo: shouldn't we trigger both folders here?
        $result = $this->trigger();
    }

    /**
     * Save an object in this folder.
     *
     * @param array  $object       The array that holds the data of the object.
     * @param int    $data_version The format handler version.
     * @param string $object_type  The type of the kolab object.
     * @param string $id           The IMAP id of the old object if it
     *                             existed before
     * @param array  $old_object   The array that holds the current data of the
     *                             object.
     *
     * @return NULL
     */
    public function saveObject(&$object, $data_version, $object_type, $id = null,
                               &$old_object = null)
    {
        $this->_folder->saveObject($object, $data_version, $object_type, $id, $old_object);
        $this->trigger();
    }

    /**
     * Set the ACL of this folder.
     *
     * @param $user The user for whom the ACL should be set.
     * @param $acl  The new ACL value.
     *
     * @return NULL
     */
    public function setAcl($user, $acl)
    {
        $this->_folder->setAcl($user, $acl);
        $this->trigger();
    }

    /**
     * Delete the ACL for a user on this folder.
     *
     * @param $user The user for whom the ACL should be deleted.
     *
     * @return NULL
     */
    public function deleteAcl($user)
    {
        $this->_folder->deleteAcl($user);
        $this->trigger();
    }

    /**
     * Triggers any required updates after changes within the
     * folder. This is currently only required for handling free/busy
     * information with Kolab.
     *
     * @param string $name Name of the folder that should be triggered.
     *
     * @return boolean|PEAR_Error True if successfull.
     */
    private function trigger($name = null)
    {
        $type =  $this->getType();
        if (is_a($type, 'PEAR_Error')) {
            return $type;
        }

        $owner = $this->getOwner();
        if (is_a($owner, 'PEAR_Error')) {
            return $owner;
        }

        $subpath = $this->getSubpath($name);
        if (is_a($subpath, 'PEAR_Error')) {
            return $subpath;
        }

        switch($type) {
        case 'event':
            $session = &Horde_Kolab_Session_Singleton::singleton();
            $url = sprintf('%s/trigger/%s/%s.pfb',
                           $session->freebusy_server, $owner, $subpath);
            break;
        default:
            return true;
        }

        $result = $this->triggerUrl($url);
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Failed triggering folder %s. Error was: %s"),
                                            $this->name, $result->getMessage()));
        }
        return $result;
    }

    /**
     * Triggers a URL.
     *
     * @param string $url The URL to be triggered.
     *
     * @return boolean|PEAR_Error True if successfull.
     */
    private function triggerUrl($url)
    {
        global $conf;

        if (!empty($conf['kolab']['no_triggering'])) {
            return true;
        }

        $options['method'] = 'GET';
        $options['timeout'] = 5;
        $options['allowRedirects'] = true;

        if (isset($conf['http']['proxy']) && !empty($conf['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $conf['http']['proxy']);
        }

        $http = new HTTP_Request($url, $options);
        $http->setBasicAuth($GLOBALS['registry']->getAuth(), $GLOBALS['registry']->getAuthCredential('password'));
        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            return PEAR::raiseError(sprintf(_("Unable to trigger URL %s. Response: %s"),
                                            $url, $http->getResponseCode()));
        }
        return true;
    }

}
