<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * A MongoDB database implementation for caching IMAP/POP data.
 * Requires the Horde_Mongo class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Cache_Backend_Mongo extends Horde_Imap_Client_Cache_Backend implements Horde_Mongo_Collection_Index
{
    /* Collection names. */
    const BASE = 'horde_imap_client_cache_data';
    const MD = 'horde_imap_client_cache_metadata';
    const MSG = 'horde_imap_client_cache_message';

    /**
     * The MongoDB object for the cache data.
     *
     * @var MongoDB
     */
    protected $_db;

    /**
     * The list of indices.
     *
     * @var array
     */
    protected $_indices = array(
        self::BASE => array(
            'base_index_1' => array(
                'hostspec' => 1,
                'mailbox' => 1,
                'port' => 1,
                'username' => 1,
            )
        ),
        self::MSG => array(
            'msg_index_1' => array(
                'uid' => 1,
                'msguid' => 1
            )
        )
    );

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <ul>
     *  <li>
     *   REQUIRED parameters:
     *   <ul>
     *    <li>
     *     mongo_db: (Horde_Mongo_Client) A MongoDB client object.
     *    </li>
     *   </ul>
     *  </li>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _initOb()
    {
        $this->_db = $this->_params['mongo_db']->selectDB(null);
    }

    /**
     */
    public function get($mailbox, $uids, $fields, $uidvalid)
    {
        $this->getMetaData($mailbox, $uidvalid, array('uidvalid'));

        if (!($uid = $this->_getUid($mailbox))) {
            return array();
        }

        $out = array();
        $query = array(
            'msguid' => array('$in' => array_map('strval', $uids)),
            'uid' => $uid
        );

        try {
            $cursor = $this->_db->selectCollection(self::MSG)->find(
                $query,
                array(
                    'data' => true,
                    'msguid' => true
                )
            );
            foreach ($cursor as $val) {
                $out[$val['msguid']] = $this->_value($val['data']);
            }
        } catch (MongoException $e) {}

        return $out;
    }

    /**
     */
    public function getCachedUids($mailbox, $uidvalid)
    {
        $this->getMetaData($mailbox, $uidvalid, array('uidvalid'));

        if (!($uid = $this->_getUid($mailbox))) {
            return array();
        }

        $out = array();
        $query = array(
            'uid' => $uid
        );

        try {
            $cursor = $this->_db->selectCollection(self::MSG)->find(
                $query,
                array(
                    'msguid' => true
                )
            );
            foreach ($cursor as $val) {
                $out[] = $val['msguid'];
            }
        } catch (MongoException $e) {}

        return $out;
    }

    /**
     */
    public function set($mailbox, $data, $uidvalid)
    {
        if ($uid = $this->_getUid($mailbox)) {
            $res = $this->get($mailbox, array_keys($data), array(), $uidvalid);
        } else {
            $res = array();
            $uid = $this->_createUid($mailbox);
        }

        $coll = $this->_db->selectCollection(self::MSG);

        foreach ($data as $key => $val) {
            try {
                if (isset($res[$key])) {
                    $coll->update(array(
                        'msguid' => strval($key),
                        'uid' => $uid
                    ), array(
                        'data' => $this->_value(array_merge($res[$key], $val)),
                        'msguid' => strval($key),
                        'uid' => $uid
                    ));
                } else {
                    $coll->insert(array(
                        'data' => $this->_value($val),
                        'msguid' => strval($key),
                        'uid' => $uid
                    ));
                }
            } catch (MongoException $e) {}
        }

        /* Update modified time. */
        try {
            $this->_db->selectCollection(self::BASE)->update(
                array('uid' => $uid),
                array('modified' => time())
            );
        } catch (MongoException $e) {}

        /* Update uidvalidity. */
        $this->setMetaData($mailbox, array('uidvalid' => $uidvalid));
    }

    /**
     */
    public function getMetaData($mailbox, $uidvalid, $entries)
    {
        if (!($uid = $this->_getUid($mailbox))) {
            return array();
        }

        $out = array();
        $query = array(
            'uid' => $uid
        );

        if (!empty($entries)) {
            $entries[] = 'uidvalid';
            $query['field'] = array(
                '$in' => array_unique($entries)
            );
        }

        try {
            $cursor = $this->_db->selectCollection(self::MD)->find(
                $query,
                array(
                    'data' => true,
                    'field' => true
                )
            );
            foreach ($cursor as $val) {
                $out[$val['field']] = $this->_value($val['data']);
            }

            if (is_null($uidvalid) ||
                !isset($out['uidvalid']) ||
                ($out['uidvalid'] == $uidvalid)) {
                return $out;
            }

            $this->deleteMailbox($mailbox);
        } catch (Horde_Db_Exception $e) {}

        return array();
    }

    /**
     */
    public function setMetaData($mailbox, $data)
    {
        if (!($uid = $this->_getUid($mailbox))) {
            $uid = $this->_createUid($mailbox);
        }

        $coll = $this->_db->selectCollection(self::MD);

        foreach ($data as $key => $val) {
            try {
                $coll->update(
                    array(
                        'field' => $key,
                        'uid' => $uid
                    ),
                    array(
                        'data' => $this->_value($val),
                        'field' => $key,
                        'uid' => $uid
                    ),
                    array('upsert' => true)
                );
            } catch (MongoException $e) {}
        }
    }

    /**
     */
    public function deleteMsgs($mailbox, $uids)
    {
        if ($uid = $this->_getUid($mailbox)) {
            try {
                $this->_db->selectCollection(self::MSG)->remove(array(
                    'msguid' => array('$in' => array_map('strval', $uids)),
                    'uid' => $uid
                ));
            } catch (MongoException $e) {}
        }
    }

    /**
     */
    public function deleteMailbox($mailbox)
    {
        if (!($uid = $this->_getUid($mailbox))) {
            return;
        }

        foreach (array(self::BASE, self::MD, self::MSG) as $val) {
            try {
                $this->_db->selectCollection($val)->remove(array(
                    'uid' => $uid
                ));
            } catch (MongoException $e) {}
        }
    }

    /**
     */
    public function clear($lifetime)
    {
        if (is_null($lifetime)) {
            foreach (array(self::BASE, self::MD, self::MSG) as $val) {
                $this->_db->selectCollection($val)->drop();
            }
            return;
        }

        $query = array(
            'modified' => array('$lt' => (time() - $lifetime))
        );
        $uids = array();

        try {
            $cursor = $this->_db->selectCollection(self::BASE)->find($query);
            foreach ($cursor as $val) {
                $uids[] = strval($result['_id']);
            }
        } catch (MongoException $e) {}

        if (empty($uids)) {
            return;
        }

        foreach (array(self::BASE, self::MD, self::MSG) as $val) {
            try {
                $this->_db->selectCollection($val)->remove(array(
                    'uid' => array('$in' => $uids)
                ));
            } catch (MongoException $e) {}
        }
    }

    /**
     * Return the UID for a mailbox/user/server combo.
     *
     * @param string $mailbox  Mailbox name.
     *
     * @return string  UID from base table.
     */
    protected function _getUid($mailbox)
    {
        $query = array(
            'hostspec' => $this->_params['hostspec'],
            'mailbox' => $mailbox,
            'port' => $this->_params['port'],
            'username' => $this->_params['username']
        );

        try {
            if ($result = $this->_db->selectCollection(self::BASE)->findOne($query)) {
                return strval($result['_id']);
            }
        } catch (MongoException $e) {}

        return null;
    }

    /**
     * Create and return the UID for a mailbox/user/server combo.
     *
     * @param string $mailbox  Mailbox name.
     *
     * @return string  UID from base table.
     */
    protected function _createUid($mailbox)
    {
        $this->_db->selectCollection(self::BASE)->insert(array(
            'hostspec' => $this->_params['hostspec'],
            'mailbox' => $mailbox,
            'port' => $this->_params['port'],
            'username' => $this->_params['username']
        ));

        return $this->_getUid($mailbox);
    }

    /**
     * Convert data from/to storage format.
     *
     * @param mixed|MongoBinData $data  The data object.
     *
     * @return mixed|MongoBinData  The converted data.
     */
    protected function _value($data)
    {
        static $compress;

        if (!isset($compress)) {
            $compress = new Horde_Compress_Fast();
        }

        return ($data instanceof MongoBinData)
            ? @unserialize($compress->decompress($data->bin))
            : new MongoBinData($compress->compress(serialize($data)), MongoBinData::BYTE_ARRAY);
    }

    /* Horde_Mongo_Collection_Index methods. */

    /**
     */
    public function checkMongoIndices()
    {
        foreach ($this->_indices as $key => $val) {
            if (!$this->_params['mongo_db']->checkIndices($key, $val)) {
                return false;
            }
        }

        return true;
    }

    /**
     */
    public function createMongoIndices()
    {
        foreach ($this->_indices as $key => $val) {
            $this->_params['mongo_db']->createIndices($key, $val);
        }
    }

}
