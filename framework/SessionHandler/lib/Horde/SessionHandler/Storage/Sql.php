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
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
        if (!$this->_db->transactionStarted()) {
            $this->_db->beginDbTransaction();
        }

        /* Build query. */
        $query = sprintf('SELECT session_data FROM %s WHERE session_id = ?',
                         $this->_params['table']);
        $values = array($id);

        /* Execute the query. */
        try {
            $columns = $this->_db->columns($this->_params['table']);
            return $columns['session_data']->binaryToString(
                $this->_db->selectValue($query, $values));
        } catch (Horde_Db_Exception $e) {
            return '';
        }
    }

    /**
     */
    public function write($id, $session_data)
    {
        if (!$this->_db->isActive()) {
            $this->_db->reconnect();
            $this->_db->beginDbTransaction();
        }

        /* Check if session exists. */
        try {
            $exists = $this->_db->selectValue(
                sprintf('SELECT 1 FROM %s WHERE session_id = ?',
                        $this->_params['table']),
                array($id));
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        /* Update or insert session data. */
        $session_data = new Horde_Db_Value_Binary($session_data);
        try {
            if ($exists) {
                $query = sprintf(
                    'UPDATE %s '
                    . 'SET session_data = ?, session_lastmodified = ? '
                    . 'WHERE session_id = ?',
                    $this->_params['table']);
                $values = array(
                    $session_data,
                    time(),
                    $id
                );
                $this->_db->update($query, $values);
            } else {
                $query = sprintf(
                    'INSERT INTO %s '
                    . '(session_id, session_data, session_lastmodified) '
                    . 'VALUES (?, ?, ?)',
                    $this->_params['table']);
                $values = array(
                    $id,
                    $session_data,
                    time()
                );
                $this->_db->insert($query, $values);
            }
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
