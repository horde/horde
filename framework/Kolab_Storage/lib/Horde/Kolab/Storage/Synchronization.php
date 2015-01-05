<?php
/**
 * Handles synchronization with the backend.
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
 * Handles synchronization with the backend.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Synchronization
{
    /**
     * Synchronization strategy.
     *
     * @var Horde_Kolab_Storage_Synchronization
     */
    private $_strategy;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Synchronization $strategy Optional synchronization strategy.
     */
    public function __construct(Horde_Kolab_Storage_Synchronization $strategy = null)
    {
        if ($strategy === null) {
            $this->_strategy = new Horde_Kolab_Storage_Synchronization_OncePerSession();
        } else {
            $this->_strategy = $strategy;
        }
    }

    /**
     * Synchronize the provided list in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_List $list The list to synchronize.
     */
    public function synchronizeList(Horde_Kolab_Storage_List_Tools $list)
    {
        $this->_strategy->synchronizeList($list);
    }

    /**
     * Synchronize the provided data in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_Data $data The data to synchronize.
     */
    public function synchronizeData(Horde_Kolab_Storage_Data $data)
    {
        $this->_strategy->synchronizeData($data);
    }
}