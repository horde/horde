<?php
/**
 * The Horde_Auth:: class provides a common abstracted interface for various
 * authentication backends.  It also provides some useful
 * authentication-related utilities.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth
{
    /**
     * Authentication failure reasons.
     *
     * <pre>
     * REASON_BADLOGIN - Bad username and/or password
     * REASON_FAILED - Login failed
     * REASON_EXPIRED - Password has expired
     * REASON_LOGOUT - Logout due to user request
     * REASON_MESSAGE - Logout with custom message
     * REASON_SESSION - Logout due to session expiration
     * </pre>
     */
    const REASON_BADLOGIN = 1;
    const REASON_FAILED = 2;
    const REASON_EXPIRED = 3;
    const REASON_LOGOUT = 4;
    const REASON_MESSAGE = 5;
    const REASON_SESSION = 6;

    /**
     * 64 characters that are valid for APRMD5 passwords.
     */
    const APRMD5_VALID = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Characters used when generating a password.
     */
    const VOWELS = 'aeiouy';
    const CONSONANTS = 'bcdfghjklmnpqrstvwxz';
    const NUMBERS = '0123456789';

    /**
     * Attempts to return a concrete Horde_Auth_Base instance based on
     * $driver.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use (class must extend Horde_Auth_Base).
     * @param array $params   A hash containing any additional configuration
     *                        or parameters a subclass might need.
     *
     * @return Horde_Auth_Base  The newly created concrete instance.
     * @throws Horde_Auth_Exception
     */
    static public function factory($driver, $params = null)
    {
        /* Base drivers (in Auth/ directory). */
        $class = __CLASS__ . '_' . $driver;
        if (@class_exists($class)) {
            return new $class($params);
        }

        /* Explicit class name, */
        $class = $driver;
        if (@class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Auth_Exception(__CLASS__ . ': Class definition of ' . $driver . ' not found.');
    }

    /**
     * Formats a password using the current encryption.
     *
     * @param string $plaintext      The plaintext password to encrypt.
     * @param string $salt           The salt to use to encrypt the password.
     *                               If not present, a new salt will be
     *                               generated.
     * @param string $encryption     The kind of pasword encryption to use.
     *                               Defaults to md5-hex.
     * @param boolean $show_encrypt  Some password systems prepend the kind of
     *                               encryption to the crypted password ({SHA},
     *                               etc). Defaults to false.
     *
     * @return string  The encrypted password.
     */
    static public function getCryptedPassword($plaintext, $salt = '',
                                              $encryption = 'md5-hex',
                                              $show_encrypt = false)
    {
        /* Get the salt to use. */
        $salt = self::getSalt($encryption, $salt, $plaintext);

        /* Encrypt the password. */
        switch ($encryption) {
        case 'plain':
            return $plaintext;

        case 'msad':
            return Horde_String::convertCharset('"' . $plaintext . '"', 'ISO-8859-1', 'UTF-16LE');

        case 'sha':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext)));
            return $show_encrypt ? '{SHA}' . $encrypted : $encrypted;

        case 'crypt':
        case 'crypt-des':
        case 'crypt-md5':
        case 'crypt-blowfish':
            return ($show_encrypt ? '{crypt}' : '') . crypt($plaintext, $salt);

        case 'md5-base64':
            $encrypted = base64_encode(pack('H*', hash('md5', $plaintext)));
            return $show_encrypt ? '{MD5}' . $encrypted : $encrypted;

        case 'ssha':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SSHA}' . $encrypted : $encrypted;

        case 'smd5':
            $encrypted = base64_encode(pack('H*', hash('md5', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SMD5}' . $encrypted : $encrypted;

        case 'aprmd5':
            $length = strlen($plaintext);
            $context = $plaintext . '$apr1$' . $salt;
            $binary = pack('H*', hash('md5', $plaintext . $salt . $plaintext));

            for ($i = $length; $i > 0; $i -= 16) {
                $context .= substr($binary, 0, ($i > 16 ? 16 : $i));
            }
            for ($i = $length; $i > 0; $i >>= 1) {
                $context .= ($i & 1) ? chr(0) : $plaintext[0];
            }

            $binary = pack('H*', hash('md5', $context));

            for ($i = 0; $i < 1000; ++$i) {
                $new = ($i & 1) ? $plaintext : substr($binary, 0, 16);
                if ($i % 3) {
                    $new .= $salt;
                }
                if ($i % 7) {
                    $new .= $plaintext;
                }
                $new .= ($i & 1) ? substr($binary, 0, 16) : $plaintext;
                $binary = pack('H*', hash('md5', $new));
            }

            $p = array();
            for ($i = 0; $i < 5; $i++) {
                $k = $i + 6;
                $j = $i + 12;
                if ($j == 16) {
                    $j = 5;
                }
                $p[] = self::_toAPRMD5((ord($binary[$i]) << 16) |
                                       (ord($binary[$k]) << 8) |
                                       (ord($binary[$j])),
                                       5);
            }

            return '$apr1$' . $salt . '$' . implode('', $p) . self::_toAPRMD5(ord($binary[11]), 3);

        case 'md5-hex':
        default:
            return ($show_encrypt) ? '{MD5}' . hash('md5', $plaintext) : hash('md5', $plaintext);
        }
    }

    /**
     * Returns a salt for the appropriate kind of password encryption.
     * Optionally takes a seed and a plaintext password, to extract the seed
     * of an existing password, or for encryption types that use the plaintext
     * in the generation of the salt.
     *
     * @param string $encryption  The kind of pasword encryption to use.
     *                            Defaults to md5-hex.
     * @param string $seed        The seed to get the salt from (probably a
     *                            previously generated password). Defaults to
     *                            generating a new seed.
     * @param string $plaintext   The plaintext password that we're generating
     *                            a salt for. Defaults to none.
     *
     * @return string  The generated or extracted salt.
     */
    static public function getSalt($encryption = 'md5-hex', $seed = '',
                                   $plaintext = '')
    {
        switch ($encryption) {
        case 'crypt':
        case 'crypt-des':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 2)
                : substr(base64_encode(hash('md5', mt_rand(), true)), 0, 2);

        case 'crypt-md5':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 12)
                : '$1$' . base64_encode(hash('md5', sprintf('%08X%08X', mt_rand(), mt_rand()), true)) . '$';

        case 'crypt-blowfish':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 16)
                : '$2$' . base64_encode(hash('md5', sprintf('%08X%08X%08X', mt_rand(), mt_rand(), mt_rand()), true)) . '$';

        case 'ssha':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SSHA}|i', '', $seed)), 20)
                : substr(pack('H*', sha1(substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

        case 'smd5':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SMD5}|i', '', $seed)), 16)
                : substr(pack('H*', hash('md5', substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

        case 'aprmd5':
            if ($seed) {
                return substr(preg_replace('/^\$apr1\$(.{8}).*/', '\\1', $seed), 0, 8);
            } else {
                $salt = '';
                $valid = self::APRMD5_VALID;
                for ($i = 0; $i < 8; ++$i) {
                    $salt .= $valid[mt_rand(0, 63)];
                }
                return $salt;
            }

        default:
            return '';
        }
    }

    /**
     * Converts to allowed 64 characters for APRMD5 passwords.
     *
     * @param string $value   The value to convert
     * @param integer $count  The number of iterations
     *
     * @return string  $value converted to the 64 MD5 characters.
     */
    static protected function _toAPRMD5($value, $count)
    {
        $aprmd5 = '';
        $count = abs($count);
        $valid = self::APRMD5_VALID;

        while (--$count) {
            $aprmd5 .= $valid[$value & 0x3f];
            $value >>= 6;
        }

        return $aprmd5;
    }

    /**
     * Generates a random, hopefully pronounceable, password. This can be used
     * when resetting automatically a user's password.
     *
     * @return string A random password
     */
    static public function genRandomPassword()
    {
        /* Alternate consonant and vowel random chars with two random numbers
         * at the end. This should produce a fairly pronounceable password. */
        return substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::VOWELS, mt_rand(0, strlen(self::VOWELS) - 1), 1) .
            substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::VOWELS, mt_rand(0, strlen(self::VOWELS) - 1), 1) .
            substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::NUMBERS, mt_rand(0, strlen(self::NUMBERS) - 1), 1) .
            substr(self::NUMBERS, mt_rand(0, strlen(self::NUMBERS) - 1), 1);
    }

}
