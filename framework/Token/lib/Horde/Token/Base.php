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
     * @param array $params  Required parameters:
     * <pre>
     * 'secret' - (string) The secret string used for signing tokens.
     * </pre>
     * Optional parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     */
    public function __construct($params)
    {
        if (!isset($params['secret'])) {
            throw new Horde_Token_Exception('Missing secret parameter.');
        }
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
     * Return a new signed token.
     *
     * @param string $seed  A unique ID to be included in the token.
     *
     * @return string The new token.
     */
    public function get($seed = '')
    {
        $nonce = $this->getNonce();
        return Horde_Url::uriB64Encode(
            $nonce . $this->_hash($nonce . $seed)
        );
    }

    /**
     * Validate a signed token.
     *
     * @param string  $token    The signed token.
     * @param string  $seed     The unique ID of the token.
     * @param int     $timeout  Timout of the token in seconds.
     *                          Values below zero represent no timeout.
     * @param boolean $unique   Should validation of the token succeed only once?
     *
     * @return boolean True if the token was valid.
     */
    public function validate($token, $seed = '', $timeout = -1, $unique = false)
    {
        $b = Horde_Url::uriB64Decode($token);
        $nonce = substr($b, 0, 6);
        $hash = substr($b, 6);
        if ($hash != $this->_hash($nonce . $seed)) {
            return false;
        }
        $timestamp = unpack('N', substr($nonce, 0, 4));
        $timestamp = array_pop($timestamp);
        if ($timeout >= 0 && $timestamp + $timeout >= time()) {
            return false;
        }
        if ($unique) {
            return $this->verify($nonce);
        }
        return true;
    }

    private function _hash($text)
    {
        return hash('sha256', $text . $this->_params['secret'], true);
    }

    /**
     * Return a "number used once" (a concatenation of a timestamp and a random
     * numer).
     *
     * @return string A string of 6 bytes.
     */
    public function getNonce()
    {
        return pack('Nn', time(), mt_rand());
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
