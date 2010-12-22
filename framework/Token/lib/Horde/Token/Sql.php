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
class Horde_Token_Sql extends Horde_Token_Base
{
    /**
     * Handle for the database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @see Horde_Token_Base::__construct() for more parameters.
     *
     * @param array $params  Required parameters:
     * - db (Horde_Db_Adapter): The DB instance.
     * Optional parameters:
     * - table (string): The name of the tokens table.
     *                   DEFAULT: 'horde_tokens'
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
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_tokens',
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
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Token_Exception($e);
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

        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }
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

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Token_Exception($e);
        }
    }

}
