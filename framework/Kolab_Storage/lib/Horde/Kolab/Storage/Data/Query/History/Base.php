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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The hook that updates the Horde history information once data gets
 * synchronized with the Kolab backend.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
    private $_data;

    /**
     * The history handler.
     *
     * @var Horde_History
     */
    private $_history;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Data $data,
        $params
    ) {
        $this->_data = $data;
        $this->_history = $params['factory']->createHistory($data->getAuth());
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
        $stamp = $this->_data->getStamp();
        if (isset($params['changes'])) {
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] as $bid => $object) {
                $this->updateLog($object['uid'], $bid, $stamp);
            }
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED] as $bid => $object) {
                $this->_history->log(
                    $object, array('action' => 'delete', 'bid' => $bid, 'stamp' => $stamp)
                );
            }
        } else {
            foreach ($this->_data->getObjectToBackend() as $object => $bid) {
                $this->updateLog($object, $bid, $stamp);
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
    protected function updateLog($object, $bid, $stamp)
    {
        $log = $this->_history->getHistory($object);
        if (count($log) == 0) {
            $this->_history->log(
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
                $this->_history->log(
                    $object, array('action' => 'modify', 'bid' => $bid, 'stamp' => $stamp)
                );
            }
        }
    }
}