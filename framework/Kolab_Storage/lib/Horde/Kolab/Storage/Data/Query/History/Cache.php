<?php
/**
 * The cache based hook that updates the Horde history information once data
 * gets synchronized with the Kolab backend.
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
 * The cache based hook that updates the Horde history information once data
 * gets synchronized with the Kolab backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Query_History_Cache
extends Horde_Kolab_Storage_Data_Query_History_Base
{
    /**
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        if (isset($params['last_sync']) &&
            ($params['last_sync'] === false ||
             $params['last_sync'] !== $this->history->getActionTimestamp(__CLASS__ , 'sync'))) {
            /**
             * Ignore current changeset and do a full synchronization as we are
             * out of sync
             */
            unset($params['changes']);
        }
        parent::synchronize($params);
        if (isset($params['current_sync'])) {
            $this->history->log(
                __CLASS__ ,
                array('action' => 'sync', 'ts' => $params['current_sync'])
            );
        }
    }
}