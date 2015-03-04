<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Ingo_Storage API implementation to save Ingo data via a MongoDB database.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Mongo
extends Ingo_Storage
implements Horde_Mongo_Collection_Index
{
    /* Field names. */
    const MONGO_ID = '_id';
    const DATA = 'data';
    const ORDER = 'order';
    const WHO = 'who';

    /**
     * Indices list.
     *
     * @var array
     */
    protected $_indices = array(
        'index_who' => array(
            self::WHO => 1
        )
    );

    /**
     * @param array $params  Parameters:
     *   - collection: (string) The name of the storage collection.
     *   - mongo_db: (Horde_Mongo_Client) [REQUIRED] The DB instance.
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'ingo_storage'
        ), $params));

        $this->_params['db'] = $this->_params['mongo_db']
            ->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    protected function _loadFromBackend()
    {
        try {
            $res = $this->_params['db']->aggregate(array(
                /* Match the query. */
                array(
                    '$match' => array(
                        self::WHO => Ingo::getUser()
                    )
                ),

                /* Sort the rows. */
                array(
                    '$sort' => array(self::ORDER => 1)
                )
            ));

            if (isset($res['result'])) {
                foreach ($res['result'] as $val) {
                    if ($ob = @unserialize($val[self::DATA])) {
                        $ob->uid = strval($val[self::MONGO_ID]);
                        $this->_rules[] = $ob;
                    }
                }
            }
        } catch (MongoException $e) {}
    }

    /**
     */
    protected function _removeUserData($user)
    {
        try {
            $this->_params['db']->remove(array(
                self::WHO => Ingo::getUser()
            ));
        } catch (MongoException $e) {}
    }

    /**
     */
    protected function _storeBackend($action, $rule)
    {
        $user = Ingo::getUser();

        switch ($action) {
        case self::STORE_ADD:
            try {
                $res = $this->_params['db']->aggregate(array(
                    array(
                        '$match' => array(
                            self::WHO => $user
                        )
                    ),
                    array(
                        '$group' => array(
                            self::MONGO_ID => '',
                            'max' => array('$max' => '$' . self::ORDER)
                        )
                    )
                ));

                if (isset($res['result'])) {
                    $res = current($res['result']);
                    $max = ++$res['max'];
                } else {
                    $max = 0;
                }

                $this->_params['db']->insert(array(
                    self::DATA => serialize($rule),
                    self::ORDER => $max,
                    self::WHO => $user
                ));
            } catch (MongoException $e) {
                throw new Ingo_Exception($e);
            }
            break;

        case self::STORE_DELETE:
            try {
                $this->_params['db']->remove(array(
                    self::MONGO_ID => new MongoId($rule->uid),
                    self::WHO => $user
                ));
            } catch (MongoException $e) {}
            break;

        case self::STORE_SORT:
            try {
                foreach ($this->_rules as $key => $val) {
                    $this->_params['db']->update(array(
                        self::MONGO_ID => new MongoId($val->uid),
                        self::WHO => $user
                    ), array(
                        '$set' => array(
                            self::ORDER => $key
                        )
                    ));
                }
            } catch (MongoException $e) {}
            break;

        case self::STORE_UPDATE:
            try {
                $this->_params['db']->update(array(
                    self::MONGO_ID => new MongoId($rule->uid),
                    self::WHO => $user
                ), array(
                    '$set' => array(
                        self::DATA => serialize($rule)
                    )
                ));
            } catch (MongoException $e) {}
            break;
        }
    }

    /* Horde_Mongo_Collection_Index methods. */

    /**
     */
    public function checkMongoIndices()
    {
        return $this->_params['mongo_db']->checkIndices(
            $this->_params['db'],
            $this->_indices
        );
    }

    /**
     */
    public function createMongoIndices()
    {
        $this->_params['mongo_db']->createIndices(
            $this->_params['db'],
            $this->_indices
        );
    }

}
