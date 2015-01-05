<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Lock
 */

/**
 * Lock storage in a MongoDB database.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Lock
 */
class Horde_Lock_Mongo extends Horde_Lock
{
    /* Field names. */
    const EXPIRY_TS = 'expiry_ts';
    const LID = 'lid';
    const ORIGIN_TS = 'origin_ts';
    const OWNER = 'owner';
    const PRINCIPAL = 'principal';
    const SCOPE = 'scope';
    const TYPE = 'type';
    const UPDATE_TS = 'update_ts';

    /**
     * The MongoDB Collection object for the cache data.
     *
     * @var MongoCollection
     */
    protected $_db;

    /**
     * Ugly hack: lock driver written with assumption that it returns data
     * as defined by SQL columns. So need to do mapping in this driver.
     *
     * @var array
     */
    protected $_map = array(
        self::EXPIRY_TS => 'lock_expiry_timestamp',
        self::LID => 'lock_id',
        self::ORIGIN_TS => 'lock_origin_timestamp',
        self::OWNER => 'lock_owner',
        self::PRINCIPAL => 'lock_principal',
        self::SCOPE => 'lock_scope',
        self::TYPE => 'lock_type',
        self::UPDATE_TS => 'lock_update_timestamp'
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     *   - collection: (string) The collection name.
     *   - mongo_db: [REQUIRED] (Horde_Mongo_Client) A MongoDB client object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'horde_locks'
        ), $params));

        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (!rand(0, 999)) {
            $this->gc();
        }
    }

    /**
     */
    public function getLockInfo($lockid)
    {
        $query = array(
            self::LID => $lockid,
            '$or' => array(
                array(
                    self::EXPIRY_TS => array(
                        '$gte' => time()
                    ),
                ),
                array(
                    self::EXPIRY_TS => Horde_Lock::PERMANENT
                )
            )
        );

        try {
            return $this->_mapFields($this->_db->findOne($query));
        } catch (MongoException $e) {
            throw new Horde_Lock_Exception($e);
        }
    }

    /**
     */
    public function getLocks($scope = null, $principal = null, $type = null)
    {
        $query = array(
            '$or' => array(
                array(
                    self::EXPIRY_TS => array(
                        '$gte' => time()
                    ),
                ),
                array(
                    self::EXPIRY_TS => Horde_Lock::PERMANENT
                )
            )
        );

        // Check to see if we need to filter the results
        if (!empty($principal)) {
            $query[self::PRINCIPAL] = $principal;
        }
        if (!empty($scope)) {
            $query[self::SCOPE] = $scope;
        }
        if (!empty($type)) {
            $query[self::TYPE] = $type;
        }

        try {
            $result = $this->_db->find($query);
        } catch (MongoException $e) {
            throw new Horde_Lock_Exception($e);
        }

        $locks = array();
        foreach ($result as $val) {
            $locks[$val[self::LID]] = $this->_mapFields($val);
        }

        return $locks;
    }

    /**
     */
    public function resetLock($lockid, $lifetime)
    {
        if (!$this->getLockInfo($lockid)) {
            return false;
        }

        $now = time();
        $expiration = ($lifetime == Horde_Lock::PERMANENT)
            ? Horde_Lock::PERMANENT
            : ($now + $lifetime);

        try {
            $this->_db->update(array(
                self::EXPIRY_TS => array(
                    '$ne' => Horde_Lock::PERMANENT
                ),
                self::LID => $lockid
            ), array(
                '$set' => array(
                    self::EXPIRY_TS => $expiration,
                    self::UPDATE_TS => $now
                )
            ));
        } catch (MongoException $e) {
            throw new Horde_Lock_Exception($e);
        }

        return true;
    }

    /**
     */
    public function setLock($requestor, $scope, $principal,
                            $lifetime = 1, $type = Horde_Lock::TYPE_SHARED)
    {
        $oldlocks = $this->getLocks(
            $scope,
            $principal,
            ($type == Horde_Lock::TYPE_SHARED) ? Horde_Lock::TYPE_EXCLUSIVE : null
        );

        if (count($oldlocks) != 0) {
            // A lock exists.  Deny the new request.
            if ($this->_logger) {
                $this->_logger->log(sprintf('Lock requested for %s denied due to existing lock.', $principal), 'NOTICE');
            }
            return false;
        }

        $lockid = strval(new Horde_Support_Uuid());

        $now = time();
        $expiration = ($lifetime == Horde_Lock::PERMANENT)
            ? Horde_Lock::PERMANENT
            : ($now + $lifetime);

        try {
            $this->_db->insert(array(
                self::EXPIRY_TS => $expiration,
                self::LID => $lockid,
                self::ORIGIN_TS => $now,
                self::OWNER => $requestor,
                self::PRINCIPAL => $principal,
                self::SCOPE => $scope,
                self::TYPE => $type,
                self::UPDATE_TS => $now
            ));
        } catch (MongoException $e) {
            throw new Horde_Lock_Exception($e);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s set successfully by %s in scope %s on "%s"', $lockid, $requestor, $scope, $principal), 'DEBUG');
        }

        return $lockid;
    }

    /**
     */
    public function clearLock($lockid)
    {
        if (empty($lockid)) {
            throw new Horde_Lock_Exception('Must supply a valid lock ID.');
        }

        try {
            /* Since we're trying to clear the lock we don't care whether it
                is still valid or not. Unconditionally remove it. */
            $this->_db->remove(array(
                self::LID => $lockid
            ));
        } catch (MongoException $e) {
            throw new Horde_Lock_Exception($e);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s cleared successfully.', $lockid), 'DEBUG');
        }

        return true;
    }

    /**
     * Do garbage collection needed for the driver.
     */
    public function gc()
    {
        try {
            $result = $this->_db->remove(array(
                self::EXPIRY_TS => array(
                    '$lt' => time(),
                    '$ne' => Horde_Lock::PERMANENT
                )
            ));

            if ($this->_logger) {
                $this->_logger->log(sprintf('Lock garbage collection cleared %d locks.', $result['n']), 'DEBUG');
            }
        } catch (MongoException $e) {}
    }

    /**
     * Map return to SQL fields.
     *
     * @return array
     */
    protected function _mapFields($res)
    {
        $out = array();

        if ($res) {
            foreach ($res as $key => $val) {
                if (isset($this->_map[$key])) {
                    $out[$this->_map[$key]] = $val;
                }
            }
        }

        return $out;
    }

}
