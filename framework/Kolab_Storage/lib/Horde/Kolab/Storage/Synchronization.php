<?php
/**
 * Handles synchronization with the backend.
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
 * Handles synchronization with the backend.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Synchronization
{
    /**
     * Synchronize the provided list in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_List $list The list to synchronize.
     *
     * @return NULL
     */
    public function synchronizeList(Horde_Kolab_Storage_List $list)
    {
        $list_id = $list->getId();
        if (empty($_SESSION['kolab_storage']['synchronization']['list'][$list_id])) {
            $list->synchronize();
            $_SESSION['kolab_storage']['synchronization']['list'][$list_id] = true;
        }
    }

    /**
     * Synchronize the provided data in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_Data $data The data to synchronize.
     *
     * @return NULL
     */
    public function synchronizeData(Horde_Kolab_Storage_Data $data)
    {
        $data_id = $data->getId();
        if (empty($_SESSION['kolab_storage']['synchronization']['data'][$data_id])) {
            $data->synchronize();
            $_SESSION['kolab_storage']['synchronization']['data'][$data_id] = true;
        }
    }
}