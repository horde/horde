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
class Horde_Kolab_Storage_Data_Query_History_Base
implements Horde_Kolab_Storage_Data_Query_History
{
    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    protected $factory;

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
     * The precomputed history prefix
     *
     * @var string Cached history prefix string
     */
    private $_prefix;

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
        $this->factory = $params['factory'];
        $this->history = $this->factory->createHistory($data->getAuth());
    }

    /**
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @param array $params Additional parameters:
     *   - changes: (array)  An array of arrays keyed by backend id containing
     *                       information about each change.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $prefix = $this->_constructHistoryPrefix();
        // Abort history update if we can't determine the prefix.
        // Otherwise we pollute the database with useless entries.
        if (empty($prefix))
            return;

        $stamp = $this->data->getStamp();
        if (isset($params['changes'])) {
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] as $bid => $object) {
                $this->_updateLog($prefix.$object['uid'], $bid, $stamp);
            }
            foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED] as $bid => $object_uid) {
                // Check if the object is really gone from the folder.
                // Otherwise we just deleted a duplicated object or updated the original one.
                // (An update results in an ADDED + DELETED folder action)
                if ($this->data->objectIdExists($object_uid) == true)
                    continue;

                $this->history->log(
                    $prefix.$object_uid, array('action' => 'delete', 'bid' => $bid, 'stamp' => $stamp), true
                );
            }
        } else {
            foreach ($this->data->getObjectToBackend() as $object => $bid) {
                $this->_updateLog($prefix.$object, $bid, $stamp);
            }
        }
    }

    /**
     * Construct prefix needed for Horde_History entries.
     *
     * Horde history entries are prefixed and filtered
     * by application name and base64 encoded folder name.
     *
     * @return string Constructed prefix. Can be empty.
     */
    private function _constructHistoryPrefix()
    {
        // Check if we already know the full prefix
        if (!empty($this->_prefix))
            return $this->_prefix;

        $type = $this->_type2app($this->data->getType());
        if (empty($type))
            return '';

        // Determine share name
        $share_name = '';
        $folder = $this->data->getPath();

        // TODO: Access global Kolab_Storage object if possible
        // We probably have to extend the class structure for this.
        $query = $this->factory->create()->getList()
                ->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE);

        $data = $query->getParameters($folder);
        if (isset($data['share_name']))
            $share_name = $data['share_name'];
        else
            return '';

        $this->_prefix = $type.':'.$share_name.':';

        return $this->_prefix;
    }

    /**
     * Map Kolab object type to horde application name.
     *
     * @param string $type Kolab object type
     *
     * @return string The horde application name. Empty string if unknown
     */
    private function _type2app($type)
    {
        $mapping = array(
            'contact' => 'turba',
            'event' => 'kronolith',
            'note' => 'mnemo',
            'task' => 'nag',
        );

        if (isset($mapping[$type]))
            return $mapping[$type];

        return '';
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
                $object, array('action' => 'add', 'bid' => $bid, 'stamp' => $stamp), true
            );
        } else {
            $last = array('ts' => 0);
            foreach ($log as $entry) {
                $action = $entry['action'];
                if ($entry['ts'] > $last['ts'] && ($action == 'add' || $action == 'modify' || $action == 'delete')) {
                    $last = $entry;
                } else if ($entry['ts'] == $last['ts'] && $action == 'delete') {
                    // prefer 'delete' actions over other actions if the timestamp is the same.
                    // see the logic below.
                    $last = $entry;
                }
            }

            // If the last action for this object was 'delete', we issue an 'add'.
            // Objects can vanish and re-appear using the same object uid.
            // (a foreign client is sending an update over a slow link)
            if ($last['action'] == 'delete') {
                $this->history->log(
                    $object, array('action' => 'add', 'bid' => $bid, 'stamp' => $stamp), true
                );
            }

            if (!isset($last['bid']) || $last['bid'] != $bid
                || (isset($last['stamp']) && $last['stamp']->isReset($stamp))) {
                $this->history->log(
                    $object, array('action' => 'modify', 'bid' => $bid, 'stamp' => $stamp), true
                );
            }
        }
    }
}
