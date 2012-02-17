<?php
/**
 * The interface describing a list of Kolab folders.
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
 * The interface describing a list of Kolab folders.
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
interface Horde_Kolab_Storage_List
extends Horde_Kolab_Storage_Queriable
{
    /** Identifies the basic list query */
    const QUERY_BASE  = 'List';

    /** Identifies the ACL query */
    const QUERY_ACL   = 'Acl';

    /** Identifies the share query */
    const QUERY_SHARE = 'Share';

    /**
     * Return the list driver.
     *
     * @return Horde_Kolab_Storage_Driver The driver.
     */
    public function getDriver();

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getId();

    /**
     * Return the connection parameters.
     *
     * @return array The connection parameters.
     */
    public function getIdParameters();

    /**
     * Returns a representation for the requested folder.
     *
     * @param string $folder The path of the folder to return.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function getFolder($folder);

    /**
     * Mark the specified folder as the default folder of this type.
     *
     * @param string $folder The path of the folder to mark as default.
     *
     * @return NULL
     */
    public function setDefault($folder);

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
}
