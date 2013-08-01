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
 * @package   Prefs
 */

/**
 * Preferences storage implementation for a MongoDB database.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */
class Horde_Prefs_Storage_Mongo extends Horde_Prefs_Storage_Base implements Horde_Mongo_Collection_Index
{
    /* Field names. */
    const UID = 'uid';
    const SCOPE = 'scope';
    const NAME = 'name';
    const VALUE = 'value';

    /**
     * The MongoDB Collection object for the cache data.
     *
     * @var MongoCollection
     */
    protected $_db;

    /**
     * Indices list.
     *
     * @var array
     */
    protected $_indices = array(
        'index_scope' => array(
            self::SCOPE => 1
        ),
        'index_uid' => array(
            self::UID => 1
        )
    );

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration parameters.
     * <pre>
     *   - collection: (string) The collection name.
     *   - mongo_db: (Horde_Mongo_Client) [REQUIRED] A MongoDB client object.
     * </pre>
     */
    public function __construct($user, array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct($user, array_merge(array(
            'collection' => 'horde_prefs'
        ), $params));

        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    public function get($scope_ob)
    {
        try {
            $res = $this->_db->find(array(
                self::SCOPE => $scope_ob->scope,
                self::UID => $this->_params['user']
            ), array(
                self::NAME => 1,
                self::VALUE => 1
            ));
        } catch (MongoException $e) {
            throw new Horde_Prefs_Exception($e);
        }

        foreach ($res as $val) {
            $scope_ob->set($val[self::NAME], $val[self::VALUE]->bin);
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);

            if (is_null($value)) {
                $this->remove($scope_ob->scope, $name);
            } else {
                $query = array(
                    self::NAME => $name,
                    self::SCOPE => $scope_ob->scope,
                    self::UID => $this->_params['user']
                );

                try {
                    $this->_db->update(
                        $query,
                        array_merge($query, array(
                            self::VALUE => new MongoBinData($value, MongoBinData::BYTE_ARRAY)
                        )),
                        array(
                            'upsert' => true
                        )
                    );
                } catch (MongoException $e) {
                    throw new Horde_Prefs_Exception($e);
                }
            }
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        $query = array(
            self::UID => $this->_params['user']
        );

        if (!is_null($scope)) {
            $query[self::SCOPE] = $scope;
            if (!is_null($pref)) {
                $query[self::NAME] = $pref;
            }
        }

        try {
            $this->_db->remove($query);
        } catch (MongoException $e) {
            throw new Horde_Prefs_Exception($e);
        }
    }

    /**
     */
    public function listScopes()
    {
        try {
            return $this->_db->distinct(self::SCOPE);
        } catch (MongoException $e) {
            throw new Horde_Prefs_Exception($e);
        }
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
