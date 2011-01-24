<?php
/**
 * SessionHandler storage implementation for SQL databases.
 *
 * Uses the following SQL table structure:
 * <pre>
 * CREATE TABLE horde_sessionhandler (
 *     VARCHAR(32) NOT NULL,
 *     session_lastmodified   INT NOT NULL,
 *     session_data           LONGBLOB,
 *     -- Or, on some DBMS systems:
 *     --  session_data           IMAGE,
 *
 *     PRIMARY KEY (session_id)
 * );
 *
 * CREATE INDEX session_lastmodified_idx ON horde_sessionhandler (session_lastmodified);
 * </pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
class Horde_SessionHandler_Storage_Sql extends Horde_SessionHandler_Storage
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the sessions table.
     *           DEFAULT: 'horde_sessionhandler'
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

        parent::__construct(array_merge(array(
            'table' => 'horde_sessionhandler'
        ), $params));
    }

    /**
     */
    public function open($save_path = null, $session_name = null)
    {
    }

    /**
     */
    public function close()
    {
        /* Close any open transactions. */
        if ($this->_db->transactionStarted()) {
            try {
                $this->_db->commitDbTransaction();
            } catch (Horde_Db_Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     */
    public function read($id)
    {
        /* Begin a transaction. */
        // TODO: Rowlocking in Mysql
        $this->_db->beginDbTransaction();

        /* Build query. */
        $query = sprintf('SELECT session_data FROM %s WHERE session_id = ?',
                         $this->_params['table']);
        $values = array($id);

        /* Execute the query. */
        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }
    }

    /**
     */
    public function write($id, $session_data)
    {
        if (!$this->_db->isActive()) { $this->_db->reconnect(); }

        /* Build the SQL query. */
        $query = sprintf('SELECT session_id FROM %s WHERE session_id = ?',
                         $this->_params['table']);
        $values = array($id);

        /* Execute the query. */
        try {
            $result = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        /* Build the replace SQL query. */
        $query = sprintf('REPLACE INTO %s ' .
                         '(session_id, session_data, session_lastmodified) ' .
                         'VALUES (?, ?, ?)',
                         $this->_params['table']);
        $values = array(
            $id,
            $session_data,
            time()
        );

        /* Execute the replace query. */
        try {
            $this->_db->update($query, $values);
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            try {
                $this->_db->rollbackDbTransaction();
            } catch (Horde_Db_Exception $e) {
            }
            return false;
        }

        return true;
    }

    /**
     */
    public function destroy($id)
    {
        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE session_id = ?',
                         $this->_params['table']);
        $values = array($id);

        /* Execute the query. */
        try {
            $this->_db->delete($query, $values);
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     */
    public function gc($maxlifetime = 300)
    {
        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE session_lastmodified < ?',
                         $this->_params['table']);
        $values = array(time() - $maxlifetime);

        /* Execute the query. */
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     */
    public function getSessionIDs()
    {
        $this->open();

        /* Build the SQL query. */
        $query = sprintf('SELECT session_id FROM %s' .
                         ' WHERE session_lastmodified >= ?',
                         $this->_params['table']);
        $values = array(time() - ini_get('session.gc_maxlifetime'));

        /* Execute the query. */
        try {
            return $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            return array();
        }
    }
}
