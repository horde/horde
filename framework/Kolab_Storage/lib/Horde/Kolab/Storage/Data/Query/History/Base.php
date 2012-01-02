<?php
/**
 * The hook that updates the Horde history information once data gets
 * synchronized with the Kolab backend.
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
 * The hook that updates the Horde history information once data gets
 * synchronized with the Kolab backend.
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
class Horde_Kolab_Storage_Data_Query_History_Base
implements Horde_Kolab_Storage_Data_Query_History
{
    /**
     * The queriable data.
     *
     * @var Horde_Kolab_Storage_Data
     */
    protected $data;

    /**
     * The history handler.
     *
     * @var Horde_History
     */
    protected $history;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_Data $data,
                                $params)
    {
        $this->data = $data;
        $this->history = $params['factory']->createHistory($data->getAuth());
    }

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
        $stamp = $this->data->getStamp();
        if (isset($params['changes'])) {
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] as $bid => $object) {
                $this->_updateLog($object['uid'], $bid, $stamp);
            }
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED] as $bid => $object) {
                $this->history->log(
                    $object, array('action' => 'delete', 'bid' => $bid, 'stamp' => $stamp)
                );
            }
        } else {
            foreach ($this->data->getObjectToBackend() as $object => $bid) {
                $this->_updateLog($object, $bid, $stamp);
            }
        }
    }

    /**
     * Update the history log for an object.
     *
     * @param string                           $object The object ID.
     * @param string                           $bid    The backend ID of
     *                                                 the object.
     * @param Horde_Kolab_Storage_Folder_Stamp $stamp  The folder stamp.
     *
     * @return NULL
     */
    private function _updateLog($object, $bid, $stamp)
    {
        $log = $this->history->getHistory($object);
        if (count($log) == 0) {
            $this->history->log(
                $object, array('action' => 'add', 'bid' => $bid, 'stamp' => $stamp)
            );
        } else {
            $last = array('ts' => 0);
            foreach ($log as $entry) {
                if ($entry['ts'] > $last['ts']) {
                    $last = $entry;
                }
            }
            if (!isset($last['bid']) || $last['bid'] != $bid
                || (isset($last['stamp']) && $last['stamp']->isReset($stamp))) {
                $this->history->log(
                    $object, array('action' => 'modify', 'bid' => $bid, 'stamp' => $stamp)
                );
            }
        }
    }
}