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
 * @package   Mongo
 */

/**
 * Extend the base PECL MongoClient class by allowing it to be serialized.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mongo
 */
class Horde_Mongo_Client extends MongoClient implements Serializable
{
    /**
     * Database name (Horde_Mongo_Client forces connections to this single
     * database to ease configuration).
     *
     * @var string
     */
    public $dbname = 'horde';

    /**
     * Constructor args.
     *
     * @var array
     */
    private $_cArgs;

    /**
     * @see MongoClient#__construct
     */
    public function __construct($server = null, array $options = array())
    {
        $this->_cArgs = array($server, $options);
        parent::__construct($server, $options);
    }

    /* Database name is hardcoded into Horde_Mongo_Client. */

    /**
     * @deprecated
     * @see MongoClient#dropDB
     */
    public function dropDB($db)
    {
        return parent::dropDB($this->dbname);
    }

    /**
     * @see MongoClient#__get
     */
    public function __get($dbname)
    {
        return parent::__get($this->dbname);
    }

    /**
     * @see MongoClient#selectCollection
     */
    public function selectCollection($db, $collection = null)
    {
        return parent::selectCollection($this->dbname, $collection);
    }

    /**
     * @see MongoClient#selectDB
     */
    public function selectDB($name)
    {
        return parent::selectDB($this->dbname);
    }

    /* Horde_Mongo_Client specific methods. */

    /**
     * TODO
     *
     * @param string $collection
     * @param array $indices
     *
     * @return boolean
     */
    public function checkIndices($collection, array $indices)
    {
        $info = $this->selectCollection(null, $collection)->getIndexInfo();

        foreach ($indices as $key => $val) {
            foreach ($info as $val2) {
                if (($val2['name'] == $key) && ($val2['key'] == $val)) {
                    continue 2;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * TODO
     *
     * @param string $collection
     * @param array $indices
     */
    public function createIndices($collection, array $indices)
    {
        $coll = $this->selectCollection(null, $collection);
        $coll->deleteIndexes();

        foreach ($indices as $key => $val) {
            $coll->ensureIndex($val, array(
                'background' => true,
                'name' => $key,
                'w' => 0
            ));
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        $this->close();
        return serialize(array(
            $this->dbname,
            $this->_cArgs
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->dbname, $this->_cArgs) = unserialize($data);
        parent::__construct($this->_cArgs[0], $this->_cArgs[1]);
    }

}
