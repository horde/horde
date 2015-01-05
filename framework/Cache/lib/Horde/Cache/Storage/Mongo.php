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
 * @package   Cache
 */

/**
 * Cache storage in a MongoDB database.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Storage_Mongo extends Horde_Cache_Storage_Base
{
    /* Field names. */
    const CID = 'cid';
    const DATA = 'data';
    const EXPIRE = 'expire';
    const TIMESTAMP = 'ts';

    /**
     * The MongoDB Collection object for the cache data.
     *
     * @var MongoCollection
     */
    protected $_db;

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
            'collection' => 'horde_cache'
        ), $params));
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* Only do garbage collection 0.1% of the time we create an object. */
        if (substr(time(), -3) === '000') {
            try {
                $this->_db->remove(array(
                    self::EXPIRE => array(
                        '$exists' => true,
                        '$lt' => time()
                    )
                ));
            } catch (MongoException $e) {
                $this->_logger->log($e->getMessage(), 'DEBUG');
            }
        }
    }

    /**
     */
    protected function _initOb()
    {
        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        $okey = $key;
        $key = $this->_getCid($key);

        /* Build SQL query. */
        $query = array(
            self::CID => $key
        );

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query[self::TIMESTAMP] = array('$gte' => time() - $lifetime);
        }

        try {
            $result = $this->_db->findOne($query, array(self::DATA));
        } catch (MongoException $e) {
            $this->_logger->log($e->getMessage(), 'DEBUG');
            return false;
        }

        if (empty($result)) {
            /* No rows were found - cache miss */
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache miss: %s (cache ID %s)', $okey, $key), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache hit: %s (cache ID %s)', $okey, $key), 'DEBUG');
        }

        return $result[self::DATA]->bin;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $okey = $key;
        $key = $this->_getCid($key);
        $curr = time();

        $data = array(
            self::CID => $key,
            self::DATA => new MongoBinData($data, MongoBinData::BYTE_ARRAY),
            self::TIMESTAMP => $curr
        );

        // 0 lifetime indicates the object should not be GC'd.
        if (!empty($lifetime)) {
            $data[self::EXPIRE] = intval($lifetime) + $curr;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf(
                'Cache set: %s (id %s set at %s%s)',
                $okey,
                $key,
                date('r', $curr),
                (isset($data[self::EXPIRE]) ? ' expires at ' . date('r', $data[self::EXPIRE]) : '')
            ), 'DEBUG');
        }

        // Remove any old cache data and prevent duplicate keys
        try {
            $this->_db->update(array(
                self::CID => $key
            ), array(
                '$set' => $data
            ), array(
                'upsert' => true,
                'w' => 0
            ));
        } catch (MongoException $e) {
            $this->_logger->log($e->getMessage(), 'DEBUG');
            return false;
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        $okey = $key;
        $key = $this->_getCid($key);

        /* Build SQL query. */
        $query = array(
            self::CID => $key
        );

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query[self::TIMESTAMP] = array('$gte' => time() - $lifetime);
        }

        try {
            $result = $this->_db->findOne($query);
        } catch (MongoException $e) {
            $this->_logger->log($e->getMessage(), 'DEBUG');
            return false;
        }

        if (is_null($result)) {
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache exists() miss: %s (cache ID %s)', $okey, $key), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache exists() hit: %s (cache ID %s)', $okey, $key), 'DEBUG');
        }

        return true;
    }

    /**
     */
    public function expire($key)
    {
        $okey = $key;
        $key = $this->_getCid($key);

        try {
            $this->_db->remove(array(
                self::CID => $key
            ));
            $this->_logger->log(sprintf('Cache expire: %s (cache ID %s)', $okey, $key), 'DEBUG');
            return true;
        } catch (MongoException $e) {
            return false;
        }
    }

    /**
     */
    public function clear()
    {
        $this->_db->drop();
    }

    /**
     * Gets the cache ID for a key.
     *
     * @param string $key  The key.
     *
     * @return string  The cache ID.
     */
    protected function _getCid($key)
    {
        return hash('md5', $key);
    }

}
