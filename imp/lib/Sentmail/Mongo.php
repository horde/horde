<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Sentmail driver implementation for MongoDB databases.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Sentmail_Mongo extends IMP_Sentmail implements Horde_Mongo_Collection_Index
{
    /* Field names. */
    const ACTION = 'action';
    const MESSAGEID = 'msgid';
    const RECIPIENT = 'recip';
    const SUCCESS = 'success';
    const TS = 'ts';
    const WHO = 'who';

    /**
     * Handle for the current database connection.
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
        'index_ts' => array(
            self::TS => 1
        ),
        'index_who' => array(
            self::WHO => 1
        ),
        'index_success' => array(
            self::SUCCESS => 1
        )
    );

    /**
     * @param array $params  Parameters:
     *   - collection: (string) The name of the sentmail collection.
     *   - mongo_db: (Horde_Mongo_Client) [REQUIRED] The DB instance.
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'imp_sentmail'
        ), $params));

        $this->_db = $this->_params['mongo_db']->selectCollection(null, $this->_params['collection']);
    }

    /**
     */
    protected function _log($action, $message_id, $recipient, $success)
    {
        try {
            $this->_db->insert(array(
                self::ACTION => $action,
                self::MESSAGEID => $message_id,
                self::RECIPIENT => $recipient,
                self::SUCCESS => intval($success),
                self::TS => time(),
                self::WHO => $GLOBALS['registry']->getAuth()
            ));
        } catch (MongoException $e) {}
    }

    /**
     */
    public function favouriteRecipients($limit, $filter = null)
    {
        $query = array(
            self::SUCCESS => 1,
            self::WHO => $GLOBALS['registry']->getAuth()
        );

        if (!empty($filter)) {
            $query[self::ACTION] = array('$in' => $filter);
        }

        $out = array();

        try {

            $res = $this->_db->aggregate(array(
                /* Match the query. */
                array('$match' => $query),

                /* Group by recipient. */
                array(
                    '$group' => array(
                        '_id' => '$' . self::RECIPIENT,
                        'count' => array(
                            '$sum' => 1
                        )
                    )
                ),

                /* Sort by recipient. */
                array(
                    '$sort' => array('count' => -1)
                ),

                /* Limit the return. */
                array(
                    '$limit' => $limit
                )
            ));

            if (isset($res['result'])) {
                foreach ($res['result'] as $val) {
                    $out[] = $val['_id'];
                }
            }
        } catch (MongoException $e) {}

        return $out;
    }

    /**
     */
    public function numberOfRecipients($hours, $user = false)
    {
        $query = array(
            self::SUCCESS => 1,
            self::TS => array(
                '$gt' => (time() - ($hours * 3600))
            )
        );

        if ($user) {
            $query[self::WHO] = $GLOBALS['registry']->getAuth();
        }

        try {
            return $this->_db->count($query);
        } catch (MongoException $e) {
            return 0;
        }
    }

    /**
     */
    protected function _deleteOldEntries($before)
    {
        try {
            $this->_db->remove(array(
                self::TS => array(
                    '$lt' => $before
                )
            ));
        } catch (MongoException $e) {}
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
