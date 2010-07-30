<?php
/**
 * The Horde_Secret:: class provides an API for encrypting and decrypting
 * small pieces of data with the use of a shared key.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Secret
 */
class Horde_Secret
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array(
        'cookie_domain' => '',
        'cookie_expire' => 0,
        'cookie_path' => '',
        'cookie_ssl' => false,
        'session_name' => 'horde_secret'
    );

    /**
     * Cipher cache.
     *
     * @var array
     */
    protected $_cipherCache = array();

    /**
     * Key cache.
     *
     * @var array
     */
    protected $_keyCache = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'cookie_domain' - (string) The cookie domain.
     * 'cookie_expire' - (integer) The cookie expiration time (in seconds).
     * 'cookie_path' - (string) The cookie path.
     * 'cookie_ssl' - (boolean) Only transmit cookie securely?
     * 'session_name' - (string) The cookie session name.
     * </pre>
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Take a small piece of data and encrypt it with a key.
     *
     * @param string $key      The key to use for encryption.
     * @param string $message  The plaintext message.
     *
     * @return string  The ciphertext message.
     * @throws Horde_Secret_Exception
     */
    public function write($key, $message)
    {
        $val = strlen($key) && strlen($message)
            ? $this->_getCipherOb($key)->encrypt($message)
            : '';

        if ($val instanceof PEAR_Error) {
            throw new Horde_Secret_Exception($val);
        }

        return $val;
    }

    /**
     * Decrypt a message encrypted with write().
     *
     * @param string $key      The key to use for decryption.
     * @param string $message  The ciphertext message.
     *
     * @return string  The plaintext message.
     * @throws Horde_Secret_Exception
     */
    public function read($key, $ciphertext)
    {
        $val = strlen($key) && strlen($ciphertext)
            ? $this->_getCipherOb($key)->decrypt($ciphertext)
            : '';

        if ($val instanceof PEAR_Error) {
            throw new Horde_Secret_Exception($val);
        }

        /* Bug #9121: Data may be null padded - need to remove this
         * padding. */
        return rtrim($val, "\0");
    }

    /**
     * Returns the cached crypt object.
     *
     * @param string $key  The key to use for [de|en]cryption.
     *
     * @return Crypt_Blowfish  The crypt object.
     * @throws Horde_Secret_Exception
     */
    protected function _getCipherOb($key)
    {
        $idx = hash('md5', $key);

        if (!isset($this->_cipherCache[$idx])) {
            if (!class_exists('Crypt_Blowfish')) {
                throw new Horde_Secret_Exception('Crypt_Blowfish library not found.');
            }
            $this->_cipherCache[$idx] = new Crypt_Blowfish($key);
        }

        return $this->_cipherCache[$idx];
    }

    /**
     * Generate a secret key (for encryption), either using a random
     * md5 string and storing it in a cookie if the user has cookies
     * enabled, or munging some known values if they don't.
     *
     * @param string $keyname  The name of the key to set.
     *
     * @return string  The secret key that has been generated.
     */
    public function setKey($keyname = 'generic')
    {
        $set = true;

        if (isset($_COOKIE[$this->_params['session_name']])) {
            if (isset($_COOKIE[$keyname . '_key'])) {
                $key = $_COOKIE[$keyname . '_key'];
                $set = false;
            } else {
                $key = $_COOKIE[$keyname . '_key'] = uniqid(mt_rand());
            }
        } else {
            $key = session_id();
        }

        if ($set) {
            $this->_setCookie($keyname, $key);
        }

        return $key;
    }

    /**
     * Return a secret key, either from a cookie, or if the cookie
     * isn't there, assume we are using a munged version of a known
     * base value.
     *
     * @param string $keyname  The name of the key to get.
     *
     * @return string  The secret key.
     */
    public function getKey($keyname = 'generic')
    {
        if (!isset($this->_keyCache[$keyname])) {
            if (isset($_COOKIE[$keyname . '_key'])) {
                $key = $_COOKIE[$keyname . '_key'];
            } else {
                $key = session_id();
                $this->_setCookie($keyname, $key);
            }

            $this->_keyCache[$keyname] = $key;
        }

        return $this->_keyCache[$keyname];
    }

    /**
     * Clears a secret key entry from the current cookie.
     *
     * @param string $keyname  The name of the key to clear.
     *
     * @return boolean  True if key existed, false if not.
     */
    public function clearKey($keyname = 'generic')
    {
        if (isset($_COOKIE[$this->_params['session_name']]) &&
            isset($_COOKIE[$keyname . '_key'])) {
            unset($_COOKIE[$keyname . '_key']);
            return true;
        }

        return false;
    }

    /**
     * Sets the cookie with the given keyname/key.
     *
     * @param string $keyname  The name of the key to set.
     * @param string $key      The key to use for encryption.
     */
    protected function _setCookie($keyname, $key)
    {
        @setcookie(
            $keyname . '_key',
            $key,
            (empty($this->_params['cookie_expire']) ? 0 : (time() + $this->_params['cookie_expire'])),
            $this->_params['cookie_path'],
            $this->_params['cookie_domain'],
            $this->_params['cookie_ssl']
        );
    }

}
