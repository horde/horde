<?php
/**
 * The Horde_Token_Base:: class provides a common abstracted interface for
 * a token implementation.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
     * - secret (string): The secret string used for signing tokens.
     * Optional parameters:
     * - token_lifetime (integer): The number of seconds after which tokens
     *                             time out. Negative numbers represent "no
     *                             timeout". The default is "-1".
     * - timeout (integer): The period (in seconds) after which an id is purged.
     *                      DEFAULT: 86400 (24 hours)
     * - logger (Horde_Log_Logger): A logger object.
     */
    public function __construct($params)
    {
        if (!isset($params['secret'])) {
            throw new Horde_Token_Exception('Missing secret parameter.');
        }

        $params = array_merge(array(
            'token_lifetime' => -1,
            'timeout' => 86400
        ), $params);

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
     * it is being stored as used in the backend.
     *
     * @param string $token  The value of the token to check.
     *
     * @return boolean  True if the token has not been used, false otherwise.
     * @throws Horde_Token_Exception
     */
    public function verify($token)
    {
        $this->purge();

        $nonce = $this->_decodeNonce($token);
        if ($this->exists($nonce)) {
            return false;
        }

        $this->add($nonce);
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
     * @return boolean  True if the token was valid.
     */
    public function isValid($token, $seed = '', $timeout = null,
                            $unique = false)
    {
        list($nonce, $hash) = $this->_decode($token);
        if ($hash != $this->_hash($nonce . $seed)) {
            return false;
        }
        if ($timeout === null) {
            $timeout = $this->_params['token_lifetime'];
        }
        if ($this->_isExpired($nonce, $timeout)) {
            return false;
        }
        if ($unique) {
            return $this->verify($token);
        }
        return true;
    }

    /**
     * Is the given token still valid? Throws an exception in case it is not.
     *
     * @param string  $token  The signed token.
     * @param string  $seed   The unique ID of the token.
     *
     * @return array An array of two elements: The nonce and the hash.
     *
     * @throws Horde_Token_Exception If the token was invalid.
     */
    public function validate($token, $seed = '')
    {
        list($nonce, $hash) = $this->_decode($token);
        if ($hash != $this->_hash($nonce . $seed)) {
            throw new Horde_Token_Exception_Invalid(Horde_Token_Translation::t('We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now.'));
        }
        if ($this->_isExpired($nonce, $this->_params['token_lifetime'])) {
            throw new Horde_Token_Exception_Expired(Horde_Token_Translation::t(sprintf("This request cannot be completed because the link you followed or the form you submitted was only valid for %s minutes. Please try again now.", floor($this->_params['token_lifetime'] / 60))));
        }
        return array($nonce, $hash);
    }

    /**
     * Is the given token valid and has never been used before? Throws an
     * exception otherwise.
     *
     * @param string  $token  The signed token.
     * @param string  $seed   The unique ID of the token.
     *
     * @return NULL
     *
     * @throws Horde_Token_Exception  If the token was invalid or has been
     *                                used before.
     */
    public function validateUnique($token, $seed = '')
    {
        if (!$this->isValid($token, $seed)) {
            throw new Horde_Token_Exception_Used(Horde_Token_Translation::t('This token is invalid!'));
        }

        if (!$this->verify($token)) {
            throw new Horde_Token_Exception_Used(Horde_Token_Translation::t('This token has been used before!'));
        }
    }

    /**
     * Decode a token into the prefixed nonce and the hash.
     *
     * @param string $token The token to be decomposed.
     *
     * @return array An array of two elements: The nonce and the hash.
     */
    private function _decode($token)
    {
        $b = Horde_Url::uriB64Decode($token);
        return array(substr($b, 0, 6), substr($b, 6));
    }

    /**
     * Extract the nonce from the token.
     *
     * @param string $token The token to be decomposed.
     *
     * @return string The nonce.
     */
    private function _decodeNonce($token)
    {
        $b = Horde_Url::uriB64Decode($token);
        return substr($b, 0, 6);
    }

    /**
     * Has the nonce expired?
     *
     * @param string $nonce   The to be checked for expiration.
     * @param int    $timeout The timeout that should be applied.
     *
     * @return boolean True if the nonce expired.
     */
    private function _isExpired($nonce, $timeout)
    {
        $timestamp = unpack('N', substr($nonce, 0, 4));
        $timestamp = array_pop($timestamp);
        return $timeout >= 0 && (time() - $timestamp - $timeout) >= 0;
    }

    /**
     * Sign the given text with the secret.
     *
     * @param string $text The text to be signed.
     *
     * @return string The hashed text.
     */
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
