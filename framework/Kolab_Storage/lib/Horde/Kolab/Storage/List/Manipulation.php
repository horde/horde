<?php
/**
 * Describes Kolab folder list manipulators.
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
 * Describes Kolab folder list manipulators.
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
abstract class Horde_Kolab_Storage_List_Manipulation
{
    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    abstract public function createFolder($folder, $type = null);

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
    abstract public function deleteFolder($folder);

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    abstract public function renameFolder($old, $new);

    /**
     * Register a new manipulation listener.
     *
     * @param Horde_Kolab_Storage_List_Manipulation_Listener $listener The new listener.
     */
    abstract public function registerListener(Horde_Kolab_Storage_List_Manipulation_Listener $listener);
}