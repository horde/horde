<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SessionHandler
 */

/**
 * MongoDB storage driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SessionHandler
 */
class Horde_SessionHandler_Storage_Mongo extends Horde_SessionHandler_Storage implements Horde_Mongo_Collection_Index
{
    /* Field names. */
    const DATA = 'data';
    const LOCK = 'lock';
    const MODIFIED = 'ts';
    const SID = 'sid';

    /**
     * MongoCollection object for the storage table.
     *
     * @var MongoCollection
     */
    protected $_db;

    /**
     * Is the session locked.
     *
     * @var boolean
     */
    protected $_locked = false;

    /**
     * Indices list.
     *
     * @var array
     */
    protected $_indices = array(
        'index_ts' => array(
            self::MODIFIED => 1
        )
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * collection: (string) The collection to store data in.
     * mongo_db: (Horde_Mongo_Client) [REQUIRED] The Mongo client object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'horde_sessionhandler'
        ), $params));

        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    public function open($save_path = null, $session_name = null)
    {
        return true;
    }

    /**
     */
    public function close()
    {
        if ($this->_locked) {
            try {
                $this->_db->update(array(
                    self::SID => $this->_locked
                ), array(
                    '$unset' => array(self::LOCK => '')
                ));
            } catch (MongoException $e) {}
            $this->_locked = false;
        }

        return true;
    }

    /**
     */
    public function read($id)
    {
        /* Check for session existence. Unfortunately needed because
         * we need findAndModify() for its atomicity for locking, but this
         * atomicity means we can't tell the difference between a
         * non-existent session and a locked session. */
        $exists = $this->_db->count(array(
            self::SID => $id
        ));

        $exist_check = false;
        $i = 0;

        /* Set a maximum unlocking time, to prevent runaway PHP processes. */
        $max = ini_get('max_execution_time') * 10;

        while (true) {
            $data = array(
                self::LOCK => time(),
                self::SID => $id
            );

            /* This call will either create the session if it doesn't exist,
             * or will update the current session and lock it if not already
             * locked. If a session exists, and is locked, $res will contain
             * an empty set and we need to sleep and wait for lock to be
             * removed. */
            $res = $this->_db->findAndModify(array(
                self::SID => $id,
                self::LOCK => array(
                    '$exists' => $exist_check
                )
            ), array($data), array(
                self::DATA => true
            ), array(
                'update' => array(
                    '$set' => $data
                ),
                'upsert' => !$exists
            ));

            if (!$exists || isset($res[self::DATA])) {
                break;
            }

            /* After a second, check the timestamp to determine if this is
             * a stale session. This can prevent long waits on a busted PHP
             * process. */
            if ($i == 10) {
                $res = $this->_db->findOne(array(
                    self::SID => $id
                ), array(
                    self::LOCK => true
                ));

                $max = isset($res[self::LOCK])
                    ? ((time() - $res[self::LOCK]) * 10)
                    : $i;
            }

            if (++$i >= $max) {
                $exist_check = true;
            } else {
                /* Sleep for 0.1 second before trying again. */
                usleep(100000);
            }
        }

        $this->_locked = $id;

        return isset($res[self::DATA])
            ? $res[self::DATA]->bin
            : '';
    }

    /**
     */
    public function write($id, $session_data)
    {
        /* Update/insert session data. */
        try {
            $this->_db->update(array(
                self::SID => $id
            ), array(
                self::DATA => new MongoBinData($session_data, MongoBinData::BYTE_ARRAY),
                self::MODIFIED => time(),
                self::SID => $id
            ), array(
                'upsert' => true
            ));

            $this->_locked = false;
        } catch (MongoException $e) {
            return false;
        }

        return true;
    }

    /**
     */
    public function destroy($id)
    {
        try {
            $this->_db->remove(array(
                self::SID => $id
            ));
            return true;
        } catch (MongoException $e) {}

        return false;
    }

    /**
     */
    public function gc($maxlifetime = 300)
    {
        try {
            $this->_db->remove(array(
                self::MODIFIED => array(
                    '$lt' => (time() - $maxlifetime)
                )
            ));
            return true;
        } catch (MongoException $e) {}

        return false;
    }

    /**
     */
    public function getSessionIDs()
    {
        $ids = array();

        try {
            $cursor = $this->_db->find(array(
                self::MODIFIED => array(
                    '$gte' => (time() - ini_get('session.gc_maxlifetime'))
                )
            ), array(self::SID));

            foreach ($cursor as $val) {
                $ids[] = $val[self::SID];
            }
        } catch (MongoException $e) {}

        return $ids;
    }

    /* Horde_Mongo_Collection_Index methods. */

    /**
     */
    public function checkMongoIndices()
    {
        return $this->_params['mongo_db']->checkIndices($this->_db, $this->_indices);
    }

    /**
     */
    public function createMongoIndices()
    {
        $this->_params['mongo_db']->createIndices($this->_db, $this->_indices);
    }

}
