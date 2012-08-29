<?php
/**
 * A log decorator definition for the Kolab storage drivers.
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
 * A log decorator definition for the Kolab storage drivers.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Decorator_Log
extends Horde_Kolab_Storage_Driver_Decorator_Base
{
    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The decorated driver.
     * @param mixed                      $logger The log handler. This instance
     *                                           must provide the debug() method.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver, $logger)
    {
        $this->_logger = $logger;
        parent::__construct($driver);
    }

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        $this->_logger->debug(
            sprintf('Driver "%s": Creating backend.', $this->getDriverName())
        );
        $result = $this->_driver->createBackend();
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Backend successfully created', $this->getDriverName()
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
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Creating folder %s.',
                $this->getDriverName(),
                $folder
            )
        );
        $result = parent::create($folder);
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Successfully created folder %s.',
                $this->getDriverName(),
                $folder
            )
        );
    }

    /**
     * Delete the specified folder.
     *
     * @param string $folder  The folder to delete.
     *
     * @return NULL
     */
    public function delete($folder)
    {
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Deleting folder %s.',
                $this->getDriverName(),
                $folder
            )
        );
        $result = parent::delete($folder);
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Successfully deleted folder %s.',
                $this->getDriverName(),
                $folder
            )
        );
    }

    /**
     * Rename the specified folder.
     *
     * @param string $old  The folder to rename.
     * @param string $new  The new name of the folder.
     *
     * @return NULL
     */
    public function rename($old, $new)
    {
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Renaming folder %s.',
                $this->getDriverName(),
                $old
            )
        );
        $result = parent::rename($old, $new);
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Successfully renamed folder %s to %s.',
                $this->getDriverName(),
                $old,
                $new
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
        $this->_logger->debug(
            sprintf('Driver "%s": Listing folders.', $this->getDriverName())
        );
        $result = parent::listFolders();
        $this->_logger->debug(
            sprintf(
                'Driver "%s": List contained %s folders.',
                $this->getDriverName(),
                count($result))
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
        $this->_logger->debug(
            sprintf('Driver "%s": Listing annotation "%s".', $this->getDriverName(), $annotation)
        );
        $result = parent::listAnnotation($annotation);
        $this->_logger->debug(
            sprintf(
                'Driver "%s": List contained %s folder annotations.',
                $this->getDriverName(),
                count($result))
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
        $this->_logger->debug(
            sprintf('Driver "%s": Retrieving namespaces.', $this->getDriverName())
        );
        $result = parent::getNamespace();
        $this->_logger->debug(
            sprintf(
                'Driver "%s": Retrieved namespaces [%s].',
                $this->getDriverName(),
                (string)$result
            )
        );
        return $result;
    }
}