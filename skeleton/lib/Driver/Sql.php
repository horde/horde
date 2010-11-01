<?php
/**
 * Skeleton storage implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/sql/skeleton_foo.sql
 * script.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @package  Skeleton
 */
class Skeleton_Driver_Sql extends Skeleton_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

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
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the SQL table.
     *           DEFAULT: 'skeleton_foo'
     * </pre>
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

        $this->_params = array_merge($this->_params, array(
            'table' => 'skeleton_foo'
        ), $params);
    }

    /**
     * Retrieves the foos from the database.
     *
     * @throws Skeleton_Exception
     */
    public function retrieve()
    {
        /* Build the SQL query. */
        $query = 'SELECT * FROM ' . $this->_params['table'] . ' WHERE foo = ?';
        $values = array($this->_params['bar']);

        /* Execute the query. */
        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Skeleton_Exception($e);
        }

        /* Store the retrieved values in the foo variable. */
        $this->_foo = array_merge($this->_foo, $rows);
    }

}
