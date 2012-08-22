<?php
/**
 * Manages a list of queries that has been attached to a Horde_Kolab_Storage_List.
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
 * Manages a list of queries that has been attached to a Horde_Kolab_Storage_List.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Queries
{
    /**
     * The list of registered queries.
     *
     * @var array
     */
    private $_queries = array();

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function updateAfterCreateFolder($folder, $type = null)
    {
        foreach ($this->_queries as $name => $query) {
            $query->createFolder($folder, $type);
        }
    }

    /**
     * Delete a folder.
     *
     * WARNING: Do not use this call in case there is still data present in the
     * folder. You are required to empty any data set *before* removing the
     * folder. Otherwise there is no guarantee you can adhere to that Kolab
     * specification that might require the triggering of remote systems to
     * inform them about the removal of the folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function updateAfterDeleteFolder($folder)
    {
        foreach ($this->_queries as $name => $query) {
            $query->deleteFolder($folder);
        }
    }

    /**
     * Update the queries after renaming a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function updateAfterRenameFolder($old, $new)
    {
        foreach ($this->_queries as $name => $query) {
            $query->renameFolder($old, $new);
        }
    }

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize()
    {
        foreach ($this->_queries as $name => $query) {
            $query->synchronize();
        }
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
        $this->_queries[$name] = $query;
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
        if ($name === null) {
            $name = Horde_Kolab_Storage_List::QUERY_BASE;
        }
        if (isset($this->_queries[$name])) {
            return $this->_queries[$name];
        } else {
            throw new Horde_Kolab_Storage_List_Exception('No such query!');
        }
    }
}
