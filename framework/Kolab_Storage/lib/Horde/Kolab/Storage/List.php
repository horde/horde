<?php
/**
 * The interface describing a list of Kolab folders.
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
 * The interface describing a list of Kolab folders.
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
interface Horde_Kolab_Storage_List
extends Horde_Kolab_Storage_Queriable
{
    /** Identifies the basic list query */
    const QUERY_BASE = 'Base';

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getConnectionId();

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function createFolder($folder, $type = null);

    /**
     * Returns a representation for the requested folder.
     *
     * @param string $folder The path of the folder to return.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function getFolder($folder);

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as strings.
     */
    public function listFolders();

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function listFolderTypes();

    /**
     * Returns the namespace for the list.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function getNamespace();

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize();
}
