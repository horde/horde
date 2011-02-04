<?php
/**
 * The log decorator for folder lists from Kolab storage.
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
 * The log decorator for folder lists from Kolab storage.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
     * @param Horde_Kolab_Storage_List $list   The original list handler.
     * @param mixed                    $logger The log handler. This instance
     *                                         must provide the info() method.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        $logger
    ) {
        $this->_list = $list;
        $this->_logger = $logger;
    }

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getConnectionId()
    {
        return $this->_list->getConnectionId();
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
        $this->_logger->info(sprintf('Creating folder %s.', $folder));
        $result = $this->_list->createFolder($folder, $type);
        $this->_logger->info(
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
        $this->_logger->info(sprintf('Deleting folder %s.', $folder));
        $result = $this->_list->deleteFolder($folder);
        $this->_logger->info(
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
        $this->_logger->info(sprintf('Renaming folder %s.', $old));
        $result = $this->_list->renameFolder($old, $new);
        $this->_logger->info(
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
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $this->_logger->info(
            sprintf(
                'Listing folders for %s.',
                $this->getConnectionId()
            )
        );
        $result = $this->_list->listFolders();
        $this->_logger->info(
            sprintf(
                'List for %s contained %s folders.',
                $this->getConnectionId(),
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
        $this->_logger->info(
            sprintf(
                'Listing folder type annotations for %s.',
                $this->getConnectionId()
            )
        );
        $result = $this->_list->listFolderTypes();
        $this->_logger->info(
            sprintf(
                'List for %s contained %s folders and annotations.',
                $this->getConnectionId(),
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
     * Synchronize the list information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->_list->synchronize();
        $this->_logger->info(
            sprintf(
                'Synchronized folder list for %s.',
                $this->getConnectionId()
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
    public function registerQuery($name, Horde_Kolab_Storage_List_Query $query)
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