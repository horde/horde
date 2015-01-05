<?php
/**
 * A stop watch decorator for outgoing requests from the Kolab storage drivers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A stop watch decorator for outgoing requests from the Kolab storage drivers.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Driver_Decorator_Timer
extends Horde_Kolab_Storage_Driver_Decorator_Base
{
    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * A stop watch.
     *
     * @var Horde_Support_Timer
     */
    private $_timer;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The decorated driver.
     * @param Horde_Support_Timer        $timer  A stop watch.
     * @param mixed                      $logger The log handler. This instance
     *                                           must provide the debug() method.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                Horde_Support_Timer $timer,
                                $logger)
    {
        $this->_logger = $logger;
        $this->_timer = $timer;
        parent::__construct($driver);
    }

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        $this->_timer->push();
        $result = parent::createBackend();
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [construct]',
                floor($this->_timer->pop() * 1000)
            )
        );
        return $result;
    }

    /**
     * Create the specified folder.
     *
     * @param string $folder The folder to create.
     *
     * @return NULL
     */
    public function create($folder)
    {
        $this->_timer->push();
        $result = parent::create($folder);
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [createFolder]',
                floor($this->_timer->pop() * 1000)
            )
        );
    }

    /**
     * Set the access rights for a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to set the ACL for.
     * @param string $acl     The ACL.
     *
     * @return NULL
     */
    public function setAcl($folder, $user, $acl)
    {
        $this->_timer->push();
        parent::setAcl($folder, $user, $acl);
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [setAcl]',
                floor($this->_timer->pop() * 1000)
            )
        );
    }

    /**
     * Delete the access rights for user on a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to delete the ACL for
     *
     * @return NULL
     */
    public function deleteAcl($folder, $user)
    {
        $this->_timer->push();
        parent::deleteAcl($folder, $user);
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [deleteAcl]',
                floor($this->_timer->pop() * 1000)
            )
        );
    }

    /**
     * Retrieves a list of mailboxes from the server.
     *
     * @return array The list of mailboxes.
     */
    public function listFolders()
    {
        $this->_timer->push();
        $result = parent::listFolders();
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [listFolders]',
                floor($this->_timer->pop() * 1000)
            )
        );
        return $result;
    }

    /**
     * Retrieves the specified annotation for the complete list of mailboxes.
     *
     * @param string $annotation The name of the annotation to retrieve.
     *
     * @return array An associative array combining the folder names as key with
     *               the corresponding annotation value.
     */
    public function listAnnotation($annotation)
    {
        $this->_timer->push();
        $result = parent::listAnnotation($annotation);
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [listAnnotation]',
                floor($this->_timer->pop() * 1000)
            )
        );
        return $result;
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        $this->_timer->push();
        $result = parent::getNamespace();
        $this->_logger->debug(
            sprintf(
                'REQUEST OUT IMAP: %s ms [getNamespace]',
                floor($this->_timer->pop() * 1000)
            )
        );
        return $result;
    }
}