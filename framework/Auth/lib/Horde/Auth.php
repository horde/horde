<?php
/**
 * The Horde_Auth class provides some useful authentication-related utilities
 * and constants for the Auth package.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */
class Horde_Auth
{
    /**
     * Authentication failure reasons.
     *
     * <pre>
     * REASON_BADLOGIN - Bad username and/or password
     * REASON_FAILED   - Login failed
     * REASON_EXPIRED  - Password has expired
     * REASON_LOGOUT   - Logout due to user request
     * REASON_MESSAGE  - Logout with custom message
     * REASON_SESSION  - Logout due to session expiration
     * REASON_LOCKED   - User is locked
     * </pre>
     */
    const REASON_BADLOGIN = 1;
    const REASON_FAILED = 2;
    const REASON_EXPIRED = 3;
    const REASON_LOGOUT = 4;
    const REASON_MESSAGE = 5;
    const REASON_SESSION = 6;
    const REASON_LOCKED = 7;

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
        $class = __CLASS__ . '_' . ucfirst($driver);
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
        case 'sha1':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext)));
            return $show_encrypt ? '{SHA}' . $encrypted : $encrypted;

        case 'crypt':
        case 'crypt-des':
        case 'crypt-md5':
        case 'crypt-sha256':
        case 'crypt-sha512':
        case 'crypt-blowfish':
            return ($show_encrypt ? '{crypt}' : '') . crypt($plaintext, $salt);

        case 'md5-base64':
            $encrypted = base64_encode(pack('H*', hash('md5', $plaintext)));
            return $show_encrypt ? '{MD5}' . $encrypted : $encrypted;

        case 'ssha':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SSHA}' . $encrypted : $encrypted;

        case 'sha256':
        case 'ssha256':
            $encrypted = base64_encode(pack('H*', hash('sha256', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SSHA256}' . $encrypted : $encrypted;

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

        case 'crypt-sha256':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, strrpos($seed, '$'))
                : '$5$' . base64_encode(hash('md5', sprintf('%08X%08X%08X', mt_rand(), mt_rand(), mt_rand()), true)) . '$';

        case 'crypt-sha512':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, strrpos($seed, '$'))
                : '$6$' . base64_encode(hash('md5', sprintf('%08X%08X%08X', mt_rand(), mt_rand(), mt_rand()), true)) . '$';

        case 'ssha':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SSHA}|i', '', $seed)), 20)
                : substr(pack('H*', hash('sha1', substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

        case 'sha256':
        case 'ssha256':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SSHA256}|i', '', $seed)), 20)
                : substr(pack('H*', hash('sha256', substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

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

    /**
     * Checks whether a password matches some expected policy.
     *
     * @since   Horde_Auth 1.4.0
     *
     * @param string $password  A password.
     * @param array $policy     A configuration with policy rules. Supported
     *                          rules:
     *
     *   - minLength:   Minimum length of the password
     *   - maxLength:   Maximum length of the password
     *   - maxSpace:    Maximum number of white space characters
     *
     *     The following are the types of characters required in a
     *     password.  Either specific characters, character classes,
     *     or both can be required.  Specific types are:
     *
     *   - minUpper:    Minimum number of uppercase characters
     *   - minLower:    Minimum number of lowercase characters
     *   - minNumeric:  Minimum number of numeric characters (0-9)
     *   - minAlphaNum: Minimum number of alphanumeric characters
     *   - minAlpha:    Minimum number of alphabetic characters
     *   - minSymbol:   Minimum number of alphabetic characters
     *
     *     Alternatively (or in addition to), the minimum number of
     *     character classes can be configured by setting the
     *     following.  The valid range is 0 through 4 character
     *     classes may be required for a password. The classes are:
     *     'upper', 'lower', 'number', and 'symbol'.  For example: A
     *     password of 'p@ssw0rd' satisfies three classes ('number',
     *     'lower', and 'symbol'), while 'passw0rd' only satisfies two
     *     classes ('lower' and 'symbols').
     *
     *   - minClasses:  Minimum number (0 through 4) of character
     *                  classes.
     *
     * @throws Horde_Auth_Exception if the password does not match the policy.
     */
    static public function checkPasswordPolicy($password, array $policy)
    {
        // Check max/min lengths if specified in the policy.
        if (isset($policy['minLength']) &&
            strlen($password) < $policy['minLength']) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::t("The password must be at least %d characters long!"), $policy['minLength']));
        }
        if (isset($policy['maxLength']) &&
            strlen($password) > $policy['maxLength']) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::t("The password is too long; passwords may not be more than %d characters long!"), $policy['maxLength']));
        }

        // Dissect the password in a localized way.
        $classes = array();
        $alpha = $alnum = $num = $upper = $lower = $space = $symbol = 0;
        for ($i = 0; $i < strlen($password); $i++) {
            $char = substr($password, $i, 1);
            if (ctype_lower($char)) {
                $lower++; $alpha++; $alnum++; $classes['lower'] = 1;
            } elseif (ctype_upper($char)) {
                $upper++; $alpha++; $alnum++; $classes['upper'] = 1;
            } elseif (ctype_digit($char)) {
                $num++; $alnum++; $classes['number'] = 1;
            } elseif (ctype_punct($char)) {
                $symbol++; $classes['symbol'] = 1;
            } elseif (ctype_space($char)) {
                $space++; $classes['symbol'] = 1;
            }
        }

        // Check reamaining password policy options.
        if (isset($policy['minUpper']) && $policy['minUpper'] > $upper) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d uppercase character.", "The password must contain at least %d uppercase characters.", $policy['minUpper']), $policy['minUpper']));
        }
        if (isset($policy['minLower']) && $policy['minLower'] > $lower) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d lowercase character.", "The password must contain at least %d lowercase characters.", $policy['minLower']), $policy['minLower']));
        }
        if (isset($policy['minNumeric']) && $policy['minNumeric'] > $num) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d numeric character.", "The password must contain at least %d numeric characters.", $policy['minNumeric']), $policy['minNumeric']));
        }
        if (isset($policy['minAlpha']) && $policy['minAlpha'] > $alpha) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d alphabetic character.", "The password must contain at least %d alphabetic characters.", $policy['minAlpha']), $policy['minAlpha']));
        }
        if (isset($policy['minAlphaNum']) && $policy['minAlphaNum'] > $alnum) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d alphanumeric character.", "The password must contain at least %d alphanumeric characters.", $policy['minAlphaNum']), $policy['minAlphaNum']));
        }
        if (isset($policy['minClasses']) && $policy['minClasses'] > array_sum($classes)) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::t("The password must contain at least %d different types of characters. The types are: lower, upper, numeric, and symbols."), $policy['minClasses']));
        }
        if (isset($policy['maxSpace']) && $policy['maxSpace'] < $space) {
            if ($policy['maxSpace'] > 0) {
                throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::t("The password must contain less than %d whitespace characters."), $policy['maxSpace'] + 1));
            }
            throw new Horde_Auth_Exception(Horde_Auth_Translation::t("The password must not contain whitespace characters."));
        }
        if (isset($policy['minSymbol']) && $policy['minSymbol'] > $symbol) {
            throw new Horde_Auth_Exception(sprintf(Horde_Auth_Translation::ngettext("The password must contain at least %d symbol character.", "The password must contain at least %d symbol characters.", $policy['minSymbol']), $policy['minSymbol']));
        }
    }

    /**
     * Checks whether a password is too similar to a dictionary of strings.
     *
     * @since   Horde_Auth 1.4.0
     *
     * @param string $password  A password.
     * @param array $dict       A dictionary to check for similarity, for
     *                          example the user name or an old password.
     * @param float $percent    The maximum allowed similarity in percent.
     *
     * @throws Horde_Auth_Exception if the password is too similar.
     */
    static public function checkPasswordSimilarity($password, array $dict,
                                                   $max = 80)
    {
        // Check for pass == dict, simple reverse strings, etc.
        foreach ($dict as $test) {
            if ((strcasecmp($password, $test) == 0) ||
                (strcasecmp($password, strrev($test)) == 0)) {
                throw new Horde_Auth_Exception(Horde_Auth_Translation::t("The password is too simple to guess."));
            }
        }

        // Check for percentages similarity also.  This will catch very simple
        // Things like "password" -> "password2" or "xpasssword"...
        foreach ($dict as $test) {
            similar_text($password, $test, $percent);
            if ($percent > $max) {
                throw new Horde_Auth_Exception(Horde_Auth_Translation::t("The password is too simple to guess."));
            }
        }
    }
}
