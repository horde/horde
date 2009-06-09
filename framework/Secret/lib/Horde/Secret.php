<?php
/**
 * The Secret:: class provides an API for encrypting and decrypting
 * small pieces of data with the use of a shared key.
 *
 * The Secret:: functions use the Horde Cipher:: class if mcrypt is not
 * available.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Secret
 */
class Horde_Secret
{
    /**
     * Cipher cache.
     *
     * @var array
     */
    static protected $_cipherCache = array();

    /**
     * Key cache.
     *
     * @var array
     */
    static protected $_keyCache = array();

    /**
     * Take a small piece of data and encrypt it with a key.
     *
     * @param string $key      The key to use for encryption.
     * @param string $message  The plaintext message.
     *
     * @return string  The ciphertext message.
     */
    static public function write($key, $message)
    {
        if (!strlen($key)) {
            return false;
        }

        $ret = Secret::_getMcryptData($key, $message, 'encrypt');
        if ($ret !== false) {
            return $ret;
        }

        $ptr = Secret::_getCipherOb($key);
        return $ptr->encrypt($message);
    }

    /**
     * Decrypt a message encrypted with Secret::write().
     *
     * @param string $key      The key to use for decryption.
     * @param string $message  The ciphertext message.
     *
     * @return string  The plaintext message.
     */
    static public function read($key, $ciphertext)
    {
        $ret = Secret::_getMcryptData($key, $ciphertext, 'decrypt');
        if ($ret !== false) {
            return rtrim($ret, "\0");
        }

        $ptr = Secret::_getCipherOb($key);
        return $ptr->decrypt($ciphertext);
    }

    /**
     * TODO
     */
    static protected function _getMcryptData($key, $text, $type)
    {
        $ret = false;

        if (Horde_Util::extensionExists('mcrypt')) {
            $old_error = error_reporting(0);
            $td = mcrypt_module_open(MCRYPT_GOST, '', MCRYPT_MODE_ECB, '');
            if ($td) {
                $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
                mcrypt_generic_init($td, $key, $iv);
                $ret = ($type == 'encrypt') ? mcrypt_generic($td, $text) : mdecrypt_generic($td, $text);
                mcrypt_generic_deinit($td);
            }
            error_reporting($old_error);
        }

        return $ret;
    }

    /**
     * TODO
     */
    static protected function _getCipherOb($key)
    {
        $idx = hash('md5', $key);

        if (!isset(self::$_cipherCache[$idx])) {
            self::$_cipherCache[$idx] = &Horde_Cipher::factory('blowfish');
            self::$_cipherCache[$idx]->setBlockMode('ofb64');
            self::$_cipherCache[$idx]->setKey($key);
        }

        return self::$_cipherCache[$idx];
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
    static public function setKey($keyname = 'generic')
    {
        if (isset($_COOKIE[$GLOBALS['conf']['session']['name']])) {
            if (isset($_COOKIE[$keyname . '_key'])) {
                $key = $_COOKIE[$keyname . '_key'];
            } else {
                $key = hash('md5', mt_rand());
                $_COOKIE[$keyname . '_key'] = $key;
                Secret::_setCookie($keyname, $key);
            }
        } else {
            $key = session_id();
            Secret::_setCookie($keyname, $key);
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
    static public function getKey($keyname = 'generic')
    {
        if (!isset(self::$_keyCache[$keyname])) {
            if (isset($_COOKIE[$keyname . '_key'])) {
                self::$_keyCache[$keyname] = $_COOKIE[$keyname . '_key'];
            } else {
                self::$_keyCache[$keyname] = session_id();
                Secret::_setCookie($keyname, self::$_keyCache[$keyname]);
            }
        }

        return self::$_keyCache[$keyname];
    }

    /**
     * TODO
     */
    static protected function _setCookie($keyname, $key)
    {
        global $conf;

        $old_error = error_reporting(0);
        setcookie(
            $keyname . '_key',
            $key,
            $conf['session']['timeout'] ? time() + $conf['session']['timeout'] : 0,
            $conf['cookie']['path'],
            $conf['cookie']['domain'],
            $conf['use_ssl'] == 1 ? 1 : 0
        );
        error_reporting($old_error);
    }

    /**
     * Clears a secret key entry from the current cookie.
     *
     * @param string $keyname  The name of the key to clear.
     *
     * @return boolean  True if key existed, false if not.
     */
    static public function clearKey($keyname = 'generic')
    {
        if (isset($_COOKIE[$GLOBALS['conf']['session']['name']]) &&
            isset($_COOKIE[$keyname . '_key'])) {
            unset($_COOKIE[$keyname . '_key']);
            return true;
        }
        return false;
    }

}

// TODO - Remove once apps are transitioned to Horde 4
class Secret extends Horde_Secret {}
