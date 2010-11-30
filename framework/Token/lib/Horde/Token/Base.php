<?php
/**
 * The Horde_Token_Base:: class provides a common abstracted interface for
 * a token implementation.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Token
 */
abstract class Horde_Token_Base
{
    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Hash of parameters necessary to use the chosen backend.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     */
    public function __construct($params)
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $this->_params = $params;
    }

    /**
     * Checks if the given token has been previously used. First
     * purges all expired tokens. Then retrieves current tokens for
     * the given ip address. If the specified token was not found,
     * adds it.
     *
     * @param string $token  The value of the token to check.
     *
     * @return boolean  True if the token has not been used, false otherwise.
     * @throws Horde_Token_Exception
     */
    public function verify($token)
    {
        $this->purge();

        if ($this->exists($token)) {
            return false;
        }

        $this->add($token);
        return true;
    }

    /**
     * Does the token exist?
     *
     * @param string $tokenID  Token ID.
     *
     * @return boolean  True if the token exists.
     * @throws Horde_Token_Exception
     */
    abstract public function exists($tokenID);

    /**
     * Add a token ID.
     *
     * @param string $tokenID  Token ID to add.
     *
     * @throws Horde_Token_Exception
     */
    abstract public function add($tokenID);

    /**
     * Delete all expired connection IDs.
     *
     * @throws Horde_Token_Exception
     */
    abstract public function purge();

    /**
     * Return a "number used once" (a concatenation of a timestamp and a random
     * numer).
     *
     * @return string A string of 6 bytes.
     */
    public function getNonce()
    {
        return pack('N', time()) . pack('n', mt_rand());
    }

    /**
     * Encodes the remote address.
     *
     * @return string  Encoded address.
     */
    protected function _encodeRemoteAddress()
    {
        return isset($_SERVER['REMOTE_ADDR'])
            ? base64_encode($_SERVER['REMOTE_ADDR'])
            : '';
    }


}
