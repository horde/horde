<?php
/**
 * Skeleton storage implementation for the Horde_Db database abstraction layer.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @package  Skeleton
 */
class Skeleton_Driver_Sql extends Skeleton_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Storage variable.
     *
     * @var array
     */
    protected $_foo = array();

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  Class parameters:
     *                       - db:    (Horde_Db_Adapater) A database handle.
     *                       - table: (string, optional) The name of the
     *                                database table.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        parent::__construct($params);
    }

    /**
     * Retrieves the foos from the database.
     *
     * @throws Skeleton_Exception
     */
    public function retrieve()
    {
        /* Build the SQL query. */

        // Unrestricted query

        $query = 'SELECT * FROM skeleton_items';

        // Restricted query alternative

        //$query = 'SELECT * FROM skeleton_items WHERE foo = ?';
        //$values = array($this->_params['bar']);

        /* Execute the query. */
        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Skeleton_Exception($e);
        }

        /* Store the retrieved values in the foo variable. */
        $this->_foo = array_merge($this->_foo, $rows);
    }

    /**
     * Stores a foo in the database.
     *
     * @throws Sms_Exception
     */
    public function store($data)
    {
        $query = 'INSERT INTO skeleton_items' .
                 ' (item_owner, item_data)' .
                     ' VALUES (?, ?)';
        $values = array($GLOBALS['registry']->getAuth(),
                        $data);

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sms_Exception($e->getMessage());
        }
    }
}
