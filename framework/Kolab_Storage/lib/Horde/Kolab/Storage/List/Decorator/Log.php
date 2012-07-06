<?php
/**
 * The log decorator for folder lists from Kolab storage.
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
 * The log decorator for folder lists from Kolab storage.
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
class Horde_Kolab_Storage_List_Decorator_Log
implements Horde_Kolab_Storage_List, Horde_Kolab_Storage_List_Query
{
    /**
     * Decorated list handler.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list  The original list handler.
     * @param mixed $logger                   The log handler. This instance
     *                                        must provide the debug() method.
     */
    public function __construct(Horde_Kolab_Storage_List $list, $logger)
    {
        $this->_list = $list;
        $this->_logger = $logger;
    }

    /**
     * Return the list driver.
     *
     * @return Horde_Kolab_Storage_Driver The driver.
     */
    public function getDriver()
    {
        return $this->_list->getDriver();
    }

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getId()
    {
        return $this->_list->getId();
    }

    /**
     * Return the connection parameters.
     *
     * @return array The connection parameters.
     */
    public function getIdParameters()
    {
        return $this->_list->getIdParameters();
    }

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function createFolder($folder, $type = null)
    {
        $this->_logger->debug(sprintf('Creating folder %s.', $folder));
        $result = $this->_list->createFolder($folder, $type);
        $this->_logger->debug(
            sprintf('Successfully created folder %s [type: %s].', $folder, $type)
        );
    }

    /**
     * Delete a folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
        $this->_logger->debug(sprintf('Deleting folder %s.', $folder));
        $result = $this->_list->deleteFolder($folder);
        $this->_logger->debug(
            sprintf('Successfully deleted folder %s.', $folder)
        );
    }

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function renameFolder($old, $new)
    {
        $this->_logger->debug(sprintf('Renaming folder %s.', $old));
        $result = $this->_list->renameFolder($old, $new);
        $this->_logger->debug(
            sprintf('Successfully renamed folder %s to %s.', $old, $new)
        );
    }

    /**
     * Returns a representation for the requested folder.
     *
     * @param string $folder The path of the folder to return.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function getFolder($folder)
    {
        return $this->_list->getFolder($folder);
    }

    /**
     * Mark the specified folder as the default folder of this type.
     *
     * @param string $folder The path of the folder to mark as default.
     *
     * @return NULL
     */
    public function setDefault($folder)
    {
        $this->_list->setDefault($folder);
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $this->_logger->debug(
            sprintf(
                'Listing folders for %s.',
                $this->getId()
            )
        );
        $result = $this->_list->listFolders();
        $this->_logger->debug(
            sprintf(
                'List for %s contained %s folders.',
                $this->getId(),
                count($result)
            )
        );
        return $result;
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function listFolderTypes()
    {
        $this->_logger->debug(
            sprintf(
                'Listing folder type annotations for %s.',
                $this->getId()
            )
        );
        $result = $this->_list->listFolderTypes();
        $this->_logger->debug(
            sprintf(
                'List for %s contained %s folders and annotations.',
                $this->getId(),
                count($result)
            )
        );
        return $result;
    }

    /**
     * Returns the namespace for the list.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function getNamespace()
    {
        return $this->_list->getNamespace();
    }

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    public function getStamp()
    {
        return $this->_list->getStamp();
    }

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $this->_list->synchronize();
        $this->_logger->debug(
            sprintf(
                'Synchronized folder list for %s.',
                $this->getId()
            )
        );
    }

    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_Query $query)
    {
        $this->_list->registerQuery($name, $query);
    }

    /**
     * Return a registered query.
     *
     * @param string $name The query name.
     *
     * @return Horde_Kolab_Storage_Query The requested query.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query does
     *                                       not exist.
     */
    public function getQuery($name = null)
    {
        return $this->_list->getQuery($name);
    }
}