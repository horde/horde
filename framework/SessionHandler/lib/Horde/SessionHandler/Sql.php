<?php
/**
 * Horde_SessionHandler implementation for SQL databases.
 *
 * The table structure can be found in:
 *   horde/scripts/sql/horde_sessionhandler.sql.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @package  SessionHandler
 */
class Horde_SessionHandler_Sql extends Horde_SessionHandler
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
     * Close the backend.
     *
     * @throws Horde_SessionHandler_Exception
     */
    protected function _close()
    {
        /* Close any open transactions. */
        try {
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            throw new Horde_SessionHandler_Exception($e);
        }
    }

    /**
     * Read the data for a particular session identifier from the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _read($id)
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
     * Write session data to the backend.
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    protected function _write($id, $session_data)
    {
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
            $this->_db->rollbackDbTransaction();
            return false;
        }

        return true;
    }

    /**
     * Destroy the data for a particular session identifier in the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return boolean  True on success, false otherwise.
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
     * Garbage collect stale sessions from the backend.
     *
     * @param integer $maxlifetime  The maximum age of a session.
     *
     * @return boolean  True on success, false otherwise.
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
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
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
