<?php
/**
 * Synchronization strategy that relies on the backend determining if it needs
 * to be synchronized. Typically using CONDSTORE or some other token based
 * strategy. Using this strategy with an IMAP server or backend that does not
 * support sync tokens will result in a sync occuring on every access.
 *
 * @todo Perhaps fall back to one of the other strategies if tokens are not
 * available? Use some kind of combination decorator maybe?
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Synchronization strategy that synchronizes at certain intervals.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Synchronization_Token
extends Horde_Kolab_Storage_Synchronization
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Synchronize the provided list in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_List $list The list to synchronize.
     */
    public function synchronizeList(Horde_Kolab_Storage_List_Tools $list)
    {
        $list_id = $list->getId();
        if (empty($_SESSION['kolab_storage']['synchronization']['list'][$list_id])) {
            $list->getListSynchronization()->synchronize();
            $_SESSION['kolab_storage']['synchronization']['list'][$list_id] = true;
        }
    }

    /**
     * Synchronize the provided data in case the selected synchronization
     * strategy requires it. Always calls the synchronize method of $data,
     * assuming $data will know if it needs to be synchronized or not.
     *
     * @param Horde_Kolab_Storage_Data $data The data to synchronize.
     */
    public function synchronizeData(Horde_Kolab_Storage_Data $data)
    {
        $data->synchronize();
    }

}