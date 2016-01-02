<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Token
 */

/**
 * Token tracking implementation using MongoDB.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Token
 */
class Horde_Token_Mongo extends Horde_Token_Base
{
    /* Field names. */
    const ADDRESS = 'addr';
    const TID = 'tid';
    const TIMESTAMP = 'ts';

    /**
     * The MongoDB Collection object for the token data.
     *
     * @var MongoCollection
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *   - collection: (string) The collection name.
     *   - mongo_db: [REQUIRED] (Horde_Mongo_Client) A MongoDB client object.
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'horde_cache'
        ), $params));

        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    public function purge()
    {
        try {
            $this->_db->remove(array(
                self::TIMESTAMP => array(
                    '$lt' => (time() - $this->_params['timeout'])
                )
            ));
        } catch (MongoException $e) {
            throw new Horde_Token_Exception($e);
        }
    }

    /**
     */
    public function exists($tokenID)
    {
        try {
            return !is_null($this->_db->findOne(array(
                self::ADDRESS => $this->_encodeRemoteAddress(),
                self::TID => $tokenID
            )));
        } catch (MongoException $e) {
            return false;
        }
    }

    /**
     */
    public function add($tokenID)
    {
        $data = array(
            self::ADDRESS => $this->_encodeRemoteAddress(),
            self::TID => $tokenID
        );

        try {
            $this->_db->update($data, array(
                '$set' => array_merge($data, array(self::TIMESTAMP => time()))
            ), array(
                'upsert' => true
            ));
        } catch (MongoException $e) {
            throw new Horde_Token_Exception($e);
        }
    }

}
