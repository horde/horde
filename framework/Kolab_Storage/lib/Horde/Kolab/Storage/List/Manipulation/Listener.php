<?php
/**
 * Describes a manipulation listener.
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
 * Describes a manipulation listener.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
interface Horde_Kolab_Storage_List_Manipulation_Listener
{
    /**
     * Update the listener after creating a new folder.
     *
     * @param string $folder The path of the folder that has been created.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function updateAfterCreateFolder($folder, $type = null);

    /**
     * Update the listener after deleting folder.
     *
     * @param string $folder The path of the folder that has been deleted.
     *
     * @return NULL
     */
    public function updateAfterDeleteFolder($folder);

    /**
     * Update the listener after renaming a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function updateAfterRenameFolder($old, $new);
}