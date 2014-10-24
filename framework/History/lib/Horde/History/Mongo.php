<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   History
 */

/**
 * Provides a MongoDB implementation of the history driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   History
 */
class Horde_History_Mongo
extends Horde_History
implements Horde_Mongo_Collection_Index
{
    /** Mongo collection name. */
    const MONGO_DATA = 'horde_history_data';
    const MONGO_MODSEQ = 'horde_history_modseq';

    /** Mongo field names. */
    const ACTION = 'action';
    const DESC = 'desc';
    const EXTRA = 'extra';
    const MODSEQ = 'modseq';
    const TS = 'ts';
    const UID = 'uid';
    const WHO = 'who';

    /**
     * MongoDB object used to manage the history.
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
        self::MONGO_DATA => array(
            'index_action' => array(
                self::ACTION => 1
            ),
            'index_modseq' => array(
                self::MODSEQ => 1
            ),
            'index_ts' => array(
                self::TS => 1
            ),
            'index_uid' => array(
                self::UID => 1
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
    public function __construct($auth, array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct($params);

        $this->_db = $params['mongo_db']->selectDB(null);
    }

    /**
     */
    public function getActionTimestamp($guid, $action)
    {
        if (!is_string($guid) || !is_string($action)) {
            throw new InvalidArgumentException(
                '$guid and $action need to be strings!'
            );
        }

        try {
            $cursor = $this->_db->selectCollection(self::MONGO_DATA)->find(
                array(
                    self::ACTION => $action,
                    self::UID => $guid
                ),
                array(
                    self::TS => true
                )
            )
            ->limit(1)
            ->sort(array(self::TS => -1));
            $next = $cursor->getNext();
            return intval($next[self::TS]);
        } catch (MongoException $e) {
            return 0;
        }
    }

    /**
     */
    protected function _log(Horde_History_Log $history, array $attributes,
                            $replaceAction = false)
    {
        $extra = array_diff_key(
            $attributes,
            array_flip(array('action', 'desc', 'ts', 'who'))
        );

        $data = array(
            self::DESC => ((isset($attributes['desc']) && strlen($attributes['desc'])) ? $attributes['desc'] : null),
            self::EXTRA => (empty($extra) ? null : serialize($extra)),
            self::MODSEQ => $this->_nextModSeq(),
            self::TS => $attributes['ts'],
            self::WHO => $attributes['who']
        );

        if ($replaceAction && !empty($attributes['action'])) {
            foreach ($history as $entry) {
                if (!empty($entry['action']) &&
                    ($entry['action'] == $attributes['action'])) {
                    try {
                        $this->_db->selectCollection(self::MONGO_DATA)->update(
                            array('_id' => $entry['id']),
                            array('$set' => $data)
                        );
                    } catch (MongoException $e) {
                        throw new Horde_History_Exception($e);
                    }

                    return;
                }
            }
        }

        /* If we're not replacing by action, or if we didn't find an entry to
         * replace, insert a new row. */
        $data[self::ACTION] = isset($attributes['action'])
            ? $attributes['action']
            : null;
        $data[self::UID] = $history->uid;

        try {
            $this->_db->selectCollection(self::MONGO_DATA)->insert($data);
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     */
    public function _getHistory($guid)
    {
        try {
            $cursor = $this->_db->selectCollection(self::MONGO_DATA)->find(
                array(self::UID => $guid)
            );
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }

        return new Horde_History_Log($guid, $this->_cursorToRow($cursor));
    }

    /**
     */
    public function _getByTimestamp($cmp, $ts, array $filters = array(),
                                    $parent = null)
    {
        array_unshift($filters, array(
            'field' => self::TS,
            'op' => $cmp,
            'value' => $ts
        ));

        return $this->_assocQuery(array(), $filters, $parent);
    }

    /**
     */
    protected function _getByModSeq(
        $start, $end, $filters = array(), $parent = null
    )
    {
        return $this->_assocQuery(
            array(
                self::MODSEQ => array(
                    '$gt' => $start,
                    '$lte' => $end
                )
            ),
            $filters,
            $parent
        );
    }

    /**
     */
    public function removeByNames(array $names)
    {
        if (!count($names)) {
            return;
        }

        if ($this->_cache) {
            foreach ($names as $name) {
                $this->_cache->expire('horde:history:' . $name);
            }
        }

        try {
            $this->_db->selectCollection(self::MONGO_DATA)->remove(array(
                self::UID => array('$in' => $names)
            ));
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     */
    public function getHighestModSeq($parent = null)
    {
        $ops = array();
        if (!empty($parent)) {
            $ops[self::UID] = array(
                '$regex' => preg_quote($parent) . ':*'
            );
        }

        try {
            /* Can't use aggregate() here, since no guarantee we are running
             * MongoDB 2.1+. */
            $cursor = $this->_db->selectCollection(self::MONGO_DATA)->find(
                $ops,
                array(self::MODSEQ => true)
            )
            ->sort(array(
                self::MODSEQ => -1
            ))
            ->limit(1);

            if ($next = $cursor->getNext()) {
                return $next[self::MODSEQ];
            }

            $cursor = $this->_db->selectCollection(self::MONGO_MODSEQ)->find(
                array('_id' => 'modseq')
            );
            return ($next = $cursor->getNext())
                ? $next[self::MODSEQ]
                : false;
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     */
    protected function _nextModSeq()
    {
        try {
            $res = $this->_db->selectCollection(self::MONGO_MODSEQ)->findAndModify(
                array('_id' => 'modseq'),
                array('$inc' => array(self::MODSEQ => 1)),
                array(),
                array('new' => true, 'upsert' => true)
            );
            return $res[self::MODSEQ];
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /**
     */
    public function getLatestEntry($guid, $use_ts = false)
    {
        try {
            $cursor = $this->_db->selectCollection(self::MONGO_DATA)->find(
                array(self::UID => $guid)
            )
            ->sort(array(
                ($use_ts ? self::TS : self::MODSEQ) => -1
            ))
            ->limit(1);

            $log = new Horde_History_Log($guid, $this->_cursorToRow($cursor));
            return isset($log[0])
                ? $log[0]
                : false;
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }
    }

    /* Internal methods. */

    /**
     */
    protected function _assocQuery($query, $filters, $parent)
    {
        foreach ($filters as $val) {
            switch ($val['op']) {
            case '>':
                $query[$val['field']] = array('$gt' => $val['value']);
                break;

            case '>=':
                $query[$val['field']] = array('$gte' => $val['value']);
                break;

            case '<':
                $query[$val['field']] = array('$lt' => $val['value']);
                break;

            case '<=':
                $query[$val['field']] = array('$lte' => $val['value']);
                break;

            case '=':
                $query[$val['field']] = $val['value'];
                break;
            }
        }

        if ($parent) {
            $query[self::UID] = array(
                '$regex' => preg_quote($parent) . ':*'
            );
        }

        try {
            $cursor = $this->_db->selectCollection(self::MONGO_DATA)->find(
                $query,
                array(self::UID => true)
            );

            $out = array();
            foreach ($cursor as $val) {
                $out[$val[self::UID]] = strval($val['_id']);
            }
        } catch (MongoException $e) {
            throw new Horde_History_Exception($e);
        }

        return $out;
    }

    /**
     * @param MongoCursor $cursor
     *
     * @return array
     */
    protected function _cursorToRow(MongoCursor $cursor)
    {
        $mapping = array(
            '_id' => 'history_id',
            self::ACTION => 'history_action',
            self::DESC => 'history_desc',
            self::EXTRA => 'history_extra',
            self::MODSEQ => 'history_modseq',
            self::TS => 'history_ts',
            self::WHO => 'history_who'
        );
        $out = array();

        foreach ($cursor as $val) {
            $row = array();

            foreach ($mapping as $key2 => $val2) {
                $row[$val2] = isset($val[$key2])
                    ? $val[$key2]
                    : null;
            }

            $out[] = $row;
        }

        return $out;
    }

    /* Horde_Mongo_Collection_Index methods. */

    /**
     */
    public function checkMongoIndices()
    {
        foreach ($this->_indices as $key => $val) {
            if (!$this->_db->checkIndices($key, $val)) {
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
            $this->_db->createIndices($key, $val);
        }
    }
}
