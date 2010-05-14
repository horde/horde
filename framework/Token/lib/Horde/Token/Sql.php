<?php
/**
 * Token tracking implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure for the tokens is as follows:
 * <pre>
 * CREATE TABLE horde_tokens (
 *     token_address    VARCHAR(100) NOT NULL,
 *     token_id         VARCHAR(32) NOT NULL,
 *     token_timestamp  BIGINT NOT NULL,
 *
 *     PRIMARY KEY (token_address, token_id)
 * );
 * </pre>
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Max Kalika <max@horde.org>
 * @category Horde
 * @package  Token
 */
class Horde_Token_Sql extends Horde_Token_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db = '';

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (DB) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the tokens table in 'database'.
     *           DEFAULT: 'horde_tokens'
     * 'timeout' - (integer) The period (in seconds) after which an id is
     *             purged.
     *             DEFAULT: 86400 (24 hours)
     * 'write_db' - (DB) The write DB instance.
     * </pre>
     *
     * @throws Horde_Token_Exception
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new Horde_Token_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];

        if (isset($params['write_db'])) {
            $this->_write_db = $params['write_db'];
        } else {
            $this->_write_db = $this->_db;
        }

        unset($params['db'], $params['write_db']);

        $params = array_merge(array(
            'table' => 'horde_tokens',
            'timeout' => 86400
        ), $params);

        parent::__construct($params);
    }

    /**
     * Delete all expired connection IDs.
     *
     * @throws Horde_Token_Exception
     */
    public function purge()
    {
        /* Build SQL query. */
        $query = 'DELETE FROM ' . $this->_params['table']
            . ' WHERE token_timestamp < ?';

        $values = array(time() - $this->_params['timeout']);

        /* Return an error if the update fails. */
        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            throw new Horde_Token_Exception($result);
        }
    }

    /**
     * Does the token exist?
     *
     * @param string $tokenID  Token ID.
     *
     * @return boolean  True if the token exists.
     * @throws Horde_Token_Exception
     */
    public function exists($tokenID)
    {
        /* Build SQL query. */
        $query = 'SELECT token_id FROM ' . $this->_params['table']
            . ' WHERE token_address = ? AND token_id = ?';

        $values = array($this->_encodeRemoteAddress(), $tokenID);

        $result = $this->_db->getOne($query, $values);
        if ($result instanceof PEAR_Error) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            return false;
        }

        return !empty($result);
    }

    /**
     * Add a token ID.
     *
     * @param string $tokenID  Token ID to add.
     *
     * @throws Horde_Token_Exception
     */
    public function add($tokenID)
    {
        /* Build SQL query. */
        $query = 'INSERT INTO ' . $this->_params['table']
            . ' (token_address, token_id, token_timestamp)'
            . ' VALUES (?, ?, ?)';

        $values = array($this->_encodeRemoteAddress(), $tokenID, time());

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            throw new Horde_Token_Exception($result);
        }
    }

}
