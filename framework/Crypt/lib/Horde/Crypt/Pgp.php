<?php
/**
 * Horde_Crypt_Pgp:: provides a framework for Horde applications to interact
 * with the GNU Privacy Guard program ("GnuPG").  GnuPG implements the OpenPGP
 * standard (RFC 2440).
 *
 * GnuPG Website: http://www.gnupg.org/
 *
 * This class has been developed with, and is only guaranteed to work with,
 * Version 1.21 or above of GnuPG.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Crypt
 */
class Horde_Crypt_Pgp extends Horde_Crypt
{
    /**
     * Armor Header Lines - From RFC 2440:
     *
     * An Armor Header Line consists of the appropriate header line text
     * surrounded by five (5) dashes ('-', 0x2D) on either side of the header
     * line text. The header line text is chosen based upon the type of data
     * that is being encoded in Armor, and how it is being encoded.
     *
     *  All Armor Header Lines are prefixed with 'PGP'.
     *
     *  The Armor Tail Line is composed in the same manner as the Armor Header
     *  Line, except the string "BEGIN" is replaced by the string "END."
     */

    /* Used for signed, encrypted, or compressed files. */
    const ARMOR_MESSAGE = 1;

    /* Used for signed files. */
    const ARMOR_SIGNED_MESSAGE = 2;

    /* Used for armoring public keys. */
    const ARMOR_PUBLIC_KEY = 3;

    /* Used for armoring private keys. */
    const ARMOR_PRIVATE_KEY = 4;

    /* Used for detached signatures, PGP/MIME signatures, and natures
     * following clearsigned messages. */
    const ARMOR_SIGNATURE = 5;

    /* Regular text contained in an PGP message. */
    const ARMOR_TEXT = 6;

    /**
     * Strings in armor header lines used to distinguish between the different
     * types of PGP decryption/encryption.
     *
     * @var array
     */
    protected $_armor = array(
        'MESSAGE' => self::ARMOR_MESSAGE,
        'SIGNED MESSAGE' => self::ARMOR_SIGNED_MESSAGE,
        'PUBLIC KEY BLOCK' => self::ARMOR_PUBLIC_KEY,
        'PRIVATE KEY BLOCK' => self::ARMOR_PRIVATE_KEY,
        'SIGNATURE' => self::ARMOR_SIGNATURE
    );

    /* The default public PGP keyserver to use. */
    const KEYSERVER_PUBLIC = 'pgp.mit.edu';

    /* The number of times the keyserver refuses connection before an error is
     * returned. */
    const KEYSERVER_REFUSE = 3;

    /* The number of seconds that PHP will attempt to connect to the keyserver
     * before it will stop processing the request. */
    const KEYSERVER_TIMEOUT = 10;

    /**
     * The list of PGP hash algorithms (from RFC 3156).
     *
     * @var array
     */
    protected $_hashAlg = array(
        1 => 'pgp-md5',
        2 => 'pgp-sha1',
        3 => 'pgp-ripemd160',
        5 => 'pgp-md2',
        6 => 'pgp-tiger192',
        7 => 'pgp-haval-5-160',
        8 => 'pgp-sha256',
        9 => 'pgp-sha384',
        10 => 'pgp-sha512',
        11 => 'pgp-sha224',
    );

    /**
     * GnuPG program location/common options.
     *
     * @var array
     */
    protected $_gnupg;

    /**
     * Filename of the temporary public keyring.
     *
     * @var string
     */
    protected $_publicKeyring;

    /**
     * Filename of the temporary private keyring.
     *
     * @var string
     */
    protected $_privateKeyring;

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  The following parameters:
     * <pre>
     * 'program' - (string) [REQUIRED] The path to the GnuPG binary.
     * 'proxy_host - (string) Proxy host.
     * 'proxy_port - (integer) Proxy port.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (empty($params['program'])) {
            throw new InvalidArgumentException('The location of the GnuPG binary must be given to the Horde_Crypt_Pgp:: class.');
        }

        /* Store the location of GnuPG and set common options. */
        $this->_gnupg = array(
            $params['program'],
            '--no-tty',
            '--no-secmem-warning',
            '--no-options',
            '--no-default-keyring',
            '--yes',
            '--homedir ' . $this->_tempdir
        );

        if (strncasecmp(PHP_OS, 'WIN', 3)) {
            array_unshift($this->_gnupg, 'LANG= ;');
        }

        $this->_params = $params;
    }

    /**
     * Generates a personal Public/Private keypair combination.
     *
     * @param string $realname    The name to use for the key.
     * @param string $email       The email to use for the key.
     * @param string $passphrase  The passphrase to use for the key.
     * @param string $comment     The comment to use for the key.
     * @param integer $keylength  The keylength to use for the key.
     *
     * @return array  An array consisting of:
     * <pre>
     * Key            Value
     * --------------------------
     * 'public'   =>  Public Key
     * 'private'  =>  Private Key
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    public function generateKey($realname, $email, $passphrase, $comment = '',
                                $keylength = 1024)
    {
        /* Create temp files to hold the generated keys. */
        $pub_file = $this->_createTempFile('horde-pgp');
        $secret_file = $this->_createTempFile('horde-pgp');

        /* Create the config file necessary for GnuPG to run in batch mode. */
        /* TODO: Sanitize input, More user customizable? */
        $input = array(
            '%pubring ' . $pub_file,
            '%secring ' . $secret_file,
            'Key-Type: DSA',
            'Key-Length: 1024',
            'Subkey-Type: ELG-E',
            'Subkey-Length: ' . $keylength,
            'Name-Real: ' . $realname,
            'Name-Email: ' . $email,
            'Expire-Date: 0',
            'Passphrase: ' . $passphrase
        );
        if (!empty($comment)) {
            $input[] = 'Name-Comment: ' . $comment;
        }
        $input[] = '%commit';

        /* Run through gpg binary. */
        $cmdline = array(
            '--gen-key',
            '--batch',
            '--armor'
        );

        $result = $this->_callGpg($cmdline, 'w', $input, true, true);

        /* Get the keys from the temp files. */
        $public_key = file($pub_file);
        $secret_key = file($secret_file);

        /* If either key is empty, something went wrong. */
        if (empty($public_key) || empty($secret_key)) {
            $msg = Horde_Crypt_Translation::t("Public/Private keypair not generated successfully.");
            if (!empty($result->stderr)) {
                $msg .= ' ' . Horde_Crypt_Translation::t("Returned error message:") . ' ' . $result->stderr;
            }
            throw new Horde_Crypt_Exception($msg);
        }

        return array('public' => $public_key, 'private' => $secret_key);
    }

    /**
     * Returns information on a PGP data block.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  An array with information on the PGP data block. If an
     *                element is not present in the data block, it will
     *                likewise not be set in the array.
     * <pre>
     * Array Format:
     * -------------
     * [public_key]/[secret_key] => Array
     *   (
     *     [created] => Key creation - UNIX timestamp
     *     [expires] => Key expiration - UNIX timestamp (0 = never expires)
     *     [size]    => Size of the key in bits
     *   )
     *
     * [keyid] => Key ID of the PGP data (if available)
     *            16-bit hex value (as of Horde 3.2)
     *
     * [signature] => Array (
     *     [id{n}/'_SIGNATURE'] => Array (
     *         [name]        => Full Name
     *         [comment]     => Comment
     *         [email]       => E-mail Address
     *         [keyid]       => 16-bit hex value (as of Horde 3.2)
     *         [created]     => Signature creation - UNIX timestamp
     *         [expires]     => Signature expiration - UNIX timestamp
     *         [micalg]      => The hash used to create the signature
     *         [sig_{hex}]   => Array [details of a sig verifying the ID] (
     *             [created]     => Signature creation - UNIX timestamp
     *             [expires]     => Signature expiration - UNIX timestamp
     *             [keyid]       => 16-bit hex value (as of Horde 3.2)
     *             [micalg]      => The hash used to create the signature
     *         )
     *     )
     * )
     * </pre>
     *
     * Each user ID will be stored in the array 'signature' and have data
     * associated with it, including an array for information on each
     * signature that has signed that UID. Signatures not associated with a
     * UID (e.g. revocation signatures and sub keys) will be stored under the
     * special keyword '_SIGNATURE'.
     *
     * @throws Horde_Crypt_Exception
     */
    public function pgpPacketInformation($pgpdata)
    {
        $data_array = array();
        $keyid = '';
        $header = null;
        $input = $this->_createTempFile('horde-pgp');
        $sig_id = $uid_idx = 0;

        /* Store message in temporary file. */
        file_put_contents($input, $pgpdata);

        $cmdline = array(
            '--list-packets',
            $input
        );
        $result = $this->_callGpg($cmdline, 'r');

        foreach (explode("\n", $result->stdout) as $line) {
            /* Headers are prefaced with a ':' as the first character on the
               line. */
            if (strpos($line, ':') === 0) {
                $lowerLine = Horde_String::lower($line);

                /* If we have a key (rather than a signature block), get the
                   key's ID */
                if (strpos($lowerLine, ':public key packet:') !== false ||
                    strpos($lowerLine, ':secret key packet:') !== false) {
                    $cmdline = array(
                        '--with-colons',
                        $input
                    );
                    $data = $this->_callGpg($cmdline, 'r');
                    if (preg_match("/(sec|pub):.*:.*:.*:([A-F0-9]{16}):/", $data->stdout, $matches)) {
                        $keyid = $matches[2];
                    }
                }

                if (strpos($lowerLine, ':public key packet:') !== false) {
                    $header = 'public_key';
                } elseif (strpos($lowerLine, ':secret key packet:') !== false) {
                    $header = 'secret_key';
                } elseif (strpos($lowerLine, ':user id packet:') !== false) {
                    $uid_idx++;
                    $line = preg_replace_callback('/\\\\x([0-9a-f]{2})/', array($this, '_pgpPacketInformationHelper'), $line);
                    if (preg_match("/\"([^\<]+)\<([^\>]+)\>\"/", $line, $matches)) {
                        $header = 'id' . $uid_idx;
                        if (preg_match('/([^\(]+)\((.+)\)$/', trim($matches[1]), $comment_matches)) {
                            $data_array['signature'][$header]['name'] = trim($comment_matches[1]);
                            $data_array['signature'][$header]['comment'] = $comment_matches[2];
                        } else {
                            $data_array['signature'][$header]['name'] = trim($matches[1]);
                            $data_array['signature'][$header]['comment'] = '';
                        }
                        $data_array['signature'][$header]['email'] = $matches[2];
                        $data_array['signature'][$header]['keyid'] = $keyid;
                    }
                } elseif (strpos($lowerLine, ':signature packet:') !== false) {
                    if (empty($header) || empty($uid_idx)) {
                        $header = '_SIGNATURE';
                    }
                    if (preg_match("/keyid\s+([0-9A-F]+)/i", $line, $matches)) {
                        $sig_id = $matches[1];
                        $data_array['signature'][$header]['sig_' . $sig_id]['keyid'] = $matches[1];
                        $data_array['keyid'] = $matches[1];
                    }
                } elseif (strpos($lowerLine, ':literal data packet:') !== false) {
                    $header = 'literal';
                } elseif (strpos($lowerLine, ':encrypted data packet:') !== false) {
                    $header = 'encrypted';
                } else {
                    $header = null;
                }
            } else {
                if ($header == 'secret_key' || $header == 'public_key') {
                    if (preg_match("/created\s+(\d+),\s+expires\s+(\d+)/i", $line, $matches)) {
                        $data_array[$header]['created'] = $matches[1];
                        $data_array[$header]['expires'] = $matches[2];
                    } elseif (preg_match("/\s+[sp]key\[0\]:\s+\[(\d+)/i", $line, $matches)) {
                        $data_array[$header]['size'] = $matches[1];
                    }
                } elseif ($header == 'literal' || $header == 'encrypted') {
                    $data_array[$header] = true;
                } elseif ($header) {
                    if (preg_match("/version\s+\d+,\s+created\s+(\d+)/i", $line, $matches)) {
                        $data_array['signature'][$header]['sig_' . $sig_id]['created'] = $matches[1];
                    } elseif (isset($data_array['signature'][$header]['sig_' . $sig_id]['created']) &&
                              preg_match('/expires after (\d+y\d+d\d+h\d+m)\)$/', $line, $matches)) {
                        $expires = $matches[1];
                        preg_match('/^(\d+)y(\d+)d(\d+)h(\d+)m$/', $expires, $matches);
                        list(, $years, $days, $hours, $minutes) = $matches;
                        $data_array['signature'][$header]['sig_' . $sig_id]['expires'] =
                            strtotime('+ ' . $years . ' years + ' . $days . ' days + ' . $hours . ' hours + ' . $minutes . ' minutes', $data_array['signature'][$header]['sig_' . $sig_id]['created']);
                    } elseif (preg_match("/digest algo\s+(\d{1})/", $line, $matches)) {
                        $micalg = $this->_hashAlg[$matches[1]];
                        $data_array['signature'][$header]['sig_' . $sig_id]['micalg'] = $micalg;
                        if ($header == '_SIGNATURE') {
                            /* Likely a signature block, not a key. */
                            $data_array['signature']['_SIGNATURE']['micalg'] = $micalg;
                        }
                        if ($sig_id == $keyid) {
                            /* Self signing signature - we can assume
                             * the micalg value from this signature is
                             * that for the key */
                            $data_array['signature']['_SIGNATURE']['micalg'] = $micalg;
                            $data_array['signature'][$header]['micalg'] = $micalg;
                        }
                    }
                }
            }
        }

        $keyid && $data_array['keyid'] = $keyid;

        return $data_array;
    }

    /**
     * TODO
     */
    protected function _pgpPacketInformationHelper($a)
    {
        return chr(hexdec($a[1]));
    }

    /**
     * Returns human readable information on a PGP key.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return string  Tabular information on the PGP key.
     * @throws Horde_Crypt_Exception
     */
    public function pgpPrettyKey($pgpdata)
    {
        $msg = '';
        $packet_info = $this->pgpPacketInformation($pgpdata);
        $fingerprints = $this->getFingerprintsFromKey($pgpdata);

        if (!empty($packet_info['signature'])) {
            /* Making the property names the same width for all
             * localizations .*/
            $leftrow = array(Horde_Crypt_Translation::t("Name"), Horde_Crypt_Translation::t("Key Type"), Horde_Crypt_Translation::t("Key Creation"),
                             Horde_Crypt_Translation::t("Expiration Date"), Horde_Crypt_Translation::t("Key Length"),
                             Horde_Crypt_Translation::t("Comment"), Horde_Crypt_Translation::t("E-Mail"), Horde_Crypt_Translation::t("Hash-Algorithm"),
                             Horde_Crypt_Translation::t("Key ID"), Horde_Crypt_Translation::t("Key Fingerprint"));
            $leftwidth = array_map('strlen', $leftrow);
            $maxwidth  = max($leftwidth) + 2;
            array_walk($leftrow, array($this, '_pgpPrettyKeyFormatter'), $maxwidth);

            foreach (array_keys($packet_info['signature']) as $uid_idx) {
                if ($uid_idx == '_SIGNATURE') {
                    continue;
                }
                $key_info = $this->pgpPacketSignatureByUidIndex($pgpdata, $uid_idx);

                if (!empty($key_info['keyid'])) {
                    $key_info['keyid'] = $this->_getKeyIDString($key_info['keyid']);
                } else {
                    $key_info['keyid'] = null;
                }

                $fingerprint = isset($fingerprints[$key_info['keyid']]) ? $fingerprints[$key_info['keyid']] : null;

                $msg .= $leftrow[0] . (isset($key_info['name']) ? stripcslashes($key_info['name']) : '') . "\n"
                    . $leftrow[1] . (($key_info['key_type'] == 'public_key') ? Horde_Crypt_Translation::t("Public Key") : Horde_Crypt_Translation::t("Private Key")) . "\n"
                    . $leftrow[2] . strftime("%D", $key_info['key_created']) . "\n"
                    . $leftrow[3] . (empty($key_info['key_expires']) ? '[' . Horde_Crypt_Translation::t("Never") . ']' : strftime("%D", $key_info['key_expires'])) . "\n"
                    . $leftrow[4] . $key_info['key_size'] . " Bytes\n"
                    . $leftrow[5] . (empty($key_info['comment']) ? '[' . Horde_Crypt_Translation::t("None") . ']' : $key_info['comment']) . "\n"
                    . $leftrow[6] . (empty($key_info['email']) ? '[' . Horde_Crypt_Translation::t("None") . ']' : $key_info['email']) . "\n"
                    . $leftrow[7] . (empty($key_info['micalg']) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $key_info['micalg']) . "\n"
                    . $leftrow[8] . (empty($key_info['keyid']) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $key_info['keyid']) . "\n"
                    . $leftrow[9] . (empty($fingerprint) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $fingerprint) . "\n\n";
            }
        }

        return $msg;
    }

    /**
     * TODO
     */
    protected function _pgpPrettyKeyFormatter(&$s, $k, $m)
    {
        $s .= ':' . str_repeat(' ', $m - Horde_String::length($s));
    }

    /**
     * TODO
     */
    protected function _getKeyIDString($keyid)
    {
        /* Get the 8 character key ID string. */
        if (strpos($keyid, '0x') === 0) {
            $keyid = substr($keyid, 2);
        }
        if (strlen($keyid) > 8) {
            $keyid = substr($keyid, -8);
        }
        return '0x' . $keyid;
    }

    /**
     * Returns only information on the first ID that matches the email address
     * input.
     *
     * @param string $pgpdata  The PGP data block.
     * @param string $email    An e-mail address.
     *
     * @return array  An array with information on the PGP data block. If an
     *                element is not present in the data block, it will
     *                likewise not be set in the array.
     * <pre>
     * Array Fields:
     * -------------
     * key_created  =>  Key creation - UNIX timestamp
     * key_expires  =>  Key expiration - UNIX timestamp (0 = never expires)
     * key_size     =>  Size of the key in bits
     * key_type     =>  The key type (public_key or secret_key)
     * name         =>  Full Name
     * comment      =>  Comment
     * email        =>  E-mail Address
     * keyid        =>  16-bit hex value
     * created      =>  Signature creation - UNIX timestamp
     * micalg       =>  The hash used to create the signature
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    public function pgpPacketSignature($pgpdata, $email)
    {
        $data = $this->pgpPacketInformation($pgpdata);
        $key_type = null;
        $return_array = array();

        /* Check that [signature] key exists. */
        if (!isset($data['signature'])) {
            return $return_array;
        }

        /* Store the signature information now. */
        if (($email == '_SIGNATURE') &&
            isset($data['signature']['_SIGNATURE'])) {
            foreach ($data['signature'][$email] as $key => $value) {
                $return_array[$key] = $value;
            }
        } else {
            $uid_idx = 1;

            while (isset($data['signature']['id' . $uid_idx])) {
                if ($data['signature']['id' . $uid_idx]['email'] == $email) {
                    foreach ($data['signature']['id' . $uid_idx] as $key => $val) {
                        $return_array[$key] = $val;
                    }
                    break;
                }
                $uid_idx++;
            }
        }

        return $this->_pgpPacketSignature($data, $return_array);
    }

    /**
     * Returns information on a PGP signature embedded in PGP data.  Similar
     * to pgpPacketSignature(), but returns information by unique User ID
     * Index (format id{n} where n is an integer of 1 or greater).
     *
     * @param string $pgpdata  See pgpPacketSignature().
     * @param string $uid_idx  The UID index.
     *
     * @return array  See pgpPacketSignature().
     * @throws Horde_Crypt_Exception
     */
    public function pgpPacketSignatureByUidIndex($pgpdata, $uid_idx)
    {
        $data = $this->pgpPacketInformation($pgpdata);
        $key_type = null;
        $return_array = array();

        /* Search for the UID index. */
        if (!isset($data['signature']) ||
            !isset($data['signature'][$uid_idx])) {
            return $return_array;
        }

        /* Store the signature information now. */
        foreach ($data['signature'][$uid_idx] as $key => $value) {
            $return_array[$key] = $value;
        }

        return $this->_pgpPacketSignature($data, $return_array);
    }

    /**
     * Adds some data to the pgpPacketSignature*() function array.
     *
     * @param array $data      See pgpPacketSignature().
     * @param array $retarray  The return array.
     *
     * @return array  The return array.
     */
    protected function _pgpPacketSignature($data, $retarray)
    {
        /* If empty, return now. */
        if (empty($retarray)) {
            return $retarray;
        }

        $key_type = null;

        /* Store any public/private key information. */
        if (isset($data['public_key'])) {
            $key_type = 'public_key';
        } elseif (isset($data['secret_key'])) {
            $key_type = 'secret_key';
        }

        if ($key_type) {
            $retarray['key_type'] = $key_type;
            if (isset($data[$key_type]['created'])) {
                $retarray['key_created'] = $data[$key_type]['created'];
            }
            if (isset($data[$key_type]['expires'])) {
                $retarray['key_expires'] = $data[$key_type]['expires'];
            }
            if (isset($data[$key_type]['size'])) {
                $retarray['key_size'] = $data[$key_type]['size'];
            }
        }

        return $retarray;
    }

    /**
     * Returns the key ID of the key used to sign a block of PGP data.
     *
     * @param string $text  The PGP signed text block.
     *
     * @return string  The key ID of the key used to sign $text.
     * @throws Horde_Crypt_Exception
     */
    public function getSignersKeyID($text)
    {
        $keyid = null;

        $input = $this->_createTempFile('horde-pgp');
        file_put_contents($input, $text);

        $cmdline = array(
            '--verify',
            $input
        );
        $result = $this->_callGpg($cmdline, 'r', null, true, true);
        if (preg_match('/gpg:\sSignature\smade.*ID\s+([A-F0-9]{8})\s+/', $result->stderr, $matches)) {
            $keyid = $matches[1];
        }

        return $keyid;
    }

    /**
     * Verify a passphrase for a given public/private keypair.
     *
     * @param string $public_key   The user's PGP public key.
     * @param string $private_key  The user's PGP private key.
     * @param string $passphrase   The user's passphrase.
     *
     * @return boolean  Returns true on valid passphrase, false on invalid
     *                  passphrase.
     * @throws Horde_Crypt_Exception
     */
    public function verifyPassphrase($public_key, $private_key, $passphrase)
    {
        /* Get e-mail address of public key. */
        $key_info = $this->pgpPacketInformation($public_key);
        if (!isset($key_info['signature']['id1']['email'])) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not determine the recipient's e-mail address."));
        }

        /* Encrypt a test message. */
        try {
            $result = $this->encrypt('Test', array('type' => 'message', 'pubkey' => $public_key, 'recips' => array($key_info['signature']['id1']['email'] => $public_key)));
        } catch (Horde_Crypt_Exception $e) {
            return false;
        }

        /* Try to decrypt the message. */
        try {
            $this->decrypt($result, array('type' => 'message', 'pubkey' => $public_key, 'privkey' => $private_key, 'passphrase' => $passphrase));
        } catch (Horde_Crypt_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Parses a message into text and PGP components.
     *
     * @param string $text  The text to parse.
     *
     * @return array  An array with the parsed text, returned in blocks of
     *                text corresponding to their actual order. Keys:
     * <pre>
     * 'type' -  (integer) The type of data contained in block.
     *           Valid types are defined at the top of this class
     *           (the ARMOR_* constants).
     * 'data' - (array) The data for each section. Each line has been stripped
     *          of EOL characters.
     * </pre>
     */
    public function parsePGPData($text)
    {
        $data = array();
        $temp = array(
            'type' => self::ARMOR_TEXT
        );

        $buffer = explode("\n", $text);
        while (list(,$val) = each($buffer)) {
            $val = rtrim($val, "\r");
            if (preg_match('/^-----(BEGIN|END) PGP ([^-]+)-----\s*$/', $val, $matches)) {
                if (isset($temp['data'])) {
                    $data[] = $temp;
                }
                $temp= array();

                if ($matches[1] == 'BEGIN') {
                    $temp['type'] = $this->_armor[$matches[2]];
                    $temp['data'][] = $val;
                } elseif ($matches[1] == 'END') {
                    $temp['type'] = self::ARMOR_TEXT;
                    $data[count($data) - 1]['data'][] = $val;
                }
            } else {
                $temp['data'][] = $val;
            }
        }

        if (isset($temp['data']) &&
            ((count($temp['data']) > 1) || !empty($temp['data'][0]))) {
            $data[] = $temp;
        }

        return $data;
    }

    /**
     * Returns a PGP public key from a public keyserver.
     *
     * @param string $keyid    The key ID of the PGP key.
     * @param string $server   The keyserver to use.
     * @param float $timeout   The keyserver timeout.
     * @param string $address  The email address of the PGP key.
     *
     * @return string  The PGP public key.
     */
    public function getPublicKeyserver($keyid,
                                       $server = self::KEYSERVER_PUBLIC,
                                       $timeout = self::KEYSERVER_TIMEOUT,
                                       $address = null)
    {
        if (empty($keyid) && !empty($address)) {
            $keyid = $this->getKeyID($address, $server, $timeout);
        }

        /* Connect to the public keyserver. */
        $uri = '/pks/lookup?op=get&search=' . $this->_getKeyIDString($keyid);
        $output = $this->_connectKeyserver('GET', $server, $uri, '', $timeout);

        /* Strip HTML Tags from output. */
        if (($start = strstr($output, '-----BEGIN'))) {
            $length = strpos($start, '-----END') + 34;
            return substr($start, 0, $length);
        }

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not obtain public key from the keyserver."));
    }

    /**
     * Sends a PGP public key to a public keyserver.
     *
     * @param string $pubkey  The PGP public key
     * @param string $server  The keyserver to use.
     * @param float $timeout  The keyserver timeout.
     *
     * @throws Horde_Crypt_Exception
     */
    public function putPublicKeyserver($pubkey,
                                       $server = self::KEYSERVER_PUBLIC,
                                       $timeout = self::KEYSERVER_TIMEOUT)
    {
        /* Get the key ID of the public key. */
        $info = $this->pgpPacketInformation($pubkey);

        /* See if the public key already exists on the keyserver. */
        try {
            $this->getPublicKeyserver($info['keyid'], $server, $timeout);
        } catch (Horde_Crypt_Exception $e) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Key already exists on the public keyserver."));
        }

        /* Connect to the public keyserver. _connectKeyserver() */
        $pubkey = 'keytext=' . urlencode(rtrim($pubkey));
        $cmd = array(
            'Host: ' . $server . ':11371',
            'User-Agent: Horde Application Framework',
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($pubkey),
            'Connection: close',
            '',
            $pubkey
        );

        return $this->_connectKeyserver('POST', $server, '/pks/add', implode("\r\n", $cmd), $timeout);
    }

    /**
     * Returns the first matching key ID for an email address from a
     * public keyserver.
     *
     * @param string $address  The email address of the PGP key.
     * @param string $server   The keyserver to use.
     * @param float $timeout   The keyserver timeout.
     *
     * @return string  The PGP key ID.
     * @throws Horde_Crypt_Exception
     */
    public function getKeyID($address, $server = self::KEYSERVER_PUBLIC,
                             $timeout = self::KEYSERVER_TIMEOUT)
    {
        /* Connect to the public keyserver. */
        $uri = '/pks/lookup?op=index&options=mr&search=' . urlencode($address);
        $output = $this->_connectKeyserver('GET', $server, $uri, '', $timeout);

        if (($start = strstr($output, '-----BEGIN PGP PUBLIC KEY BLOCK'))) {
            /* The server returned the matching key immediately. */
            $length = strpos($start, '-----END PGP PUBLIC KEY BLOCK') + 34;
            $info = $this->pgpPacketInformation(substr($start, 0, $length));
            if (!empty($info['keyid']) &&
                (empty($info['public_key']['expires']) ||
                 $info['public_key']['expires'] > time())) {
                return $info['keyid'];
            }
        } elseif (strpos($output, 'pub:') !== false) {
            $output = explode("\n", $output);
            $keyids = array();
            foreach ($output as $line) {
                if (substr($line, 0, 4) == 'pub:') {
                    $line = explode(':', $line);
                    /* Ignore invalid lines and expired keys. */
                    if (count($line) != 7 ||
                        (!empty($line[5]) && $line[5] <= time())) {
                        continue;
                    }
                    $keyids[$line[4]] = $line[1];
                }
            }
            /* Sort by timestamp to use the newest key. */
            if (count($keyids)) {
                ksort($keyids);
                return array_pop($keyids);
            }
        }

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not obtain public key from the keyserver."));
    }

    /**
     * Get the fingerprints from a key block.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  The fingerprints in $pgpdata indexed by key id.
     * @throws Horde_Crypt_Exception
     */
    public function getFingerprintsFromKey($pgpdata)
    {
        $fingerprints = array();

        /* Store the key in a temporary keyring. */
        $keyring = $this->_putInKeyring($pgpdata);

        /* Options for the GPG binary. */
        $cmdline = array(
            '--fingerprint',
            $keyring,
        );

        $result = $this->_callGpg($cmdline, 'r');
        if (!$result || !$result->stdout) {
            return $fingerprints;
        }

        /* Parse fingerprints and key ids from output. */
        $lines = explode("\n", $result->stdout);
        $keyid = null;
        foreach ($lines as $line) {
            if (preg_match('/pub\s+\w+\/(\w{8})/', $line, $matches)) {
                $keyid = '0x' . $matches[1];
            } elseif ($keyid && preg_match('/^\s+[\s\w]+=\s*([\w\s]+)$/m', $line, $matches)) {
                $fingerprints[$keyid] = trim($matches[1]);
                $keyid = null;
            }
        }

        return $fingerprints;
    }

    /**
     * Connects to a public key server via HKP (Horrowitz Keyserver Protocol).
     *
     * @param string $method    POST, GET, etc.
     * @param string $server    The keyserver to use.
     * @param string $resource  The URI to access (relative to the server).
     * @param string $command   The PGP command to run.
     * @param float $timeout    The timeout value.
     *
     * @return string  The text from standard output on success.
     * @throws Horde_Crypt_Exception
     */
    protected function _connectKeyserver($method, $server, $resource,
                                         $command, $timeout)
    {
        $connRefuse = 0;
        $output = '';

        $port = '11371';
        if (!empty($this->_params['proxy_host'])) {
            $resource = 'http://' . $server . ':' . $port . $resource;

            $server = $this->_params['proxy_host'];
            $port = isset($this->_params['proxy_port'])
                ? $this->_params['proxy_port']
                : 80;
        }

        $command = $method . ' ' . $resource . ' HTTP/1.0' . ($command ? "\r\n" . $command : '');

        /* Attempt to get the key from the keyserver. */
        do {
            $errno = $errstr = null;

            /* The HKP server is located on port 11371. */
            $fp = @fsockopen($server, $port, $errno, $errstr, $timeout);
            if ($fp) {
                fputs($fp, $command . "\n\n");
                while (!feof($fp)) {
                    $output .= fgets($fp, 1024);
                }
                fclose($fp);
                return $output;
            }
        } while (++$connRefuse < self::KEYSERVER_REFUSE);

        if ($errno == 0) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Connection refused to the public keyserver."));
        } else {
            $charset = 'UTF-8';
            $lang_charset = setlocale(LC_ALL, 0);
            if ((strpos($lang_charset, ';') === false) &&
                (strpos($lang_charset, '/') === false)) {
                $lang_charset = explode('.', $lang_charset);
                if ((count($lang_charset) == 2) && !empty($lang_charset[1])) {
                    $charset = $lang_charset[1];
                }
            }
            throw new Horde_Crypt_Exception(sprintf(Horde_Crypt_Translation::t("Connection refused to the public keyserver. Reason: %s (%s)"), Horde_String::convertCharset($errstr, $charset, 'UTF-8'), $errno));
        }
    }

    /**
     * Encrypts text using PGP.
     *
     * @param string $text   The text to be PGP encrypted.
     * @param array $params  The parameters needed for encryption.
     *                       See the individual _encrypt*() functions for the
     *                       parameter requirements.
     *
     * @return string  The encrypted message.
     * @throws Horde_Crypt_Exception
     */
    public function encrypt($text, $params = array())
    {
        if (isset($params['type'])) {
            if ($params['type'] === 'message') {
                return $this->_encryptMessage($text, $params);
            } elseif ($params['type'] === 'signature') {
                return $this->_encryptSignature($text, $params);
            }
        }
    }

    /**
     * Decrypts text using PGP.
     *
     * @param string $text   The text to be PGP decrypted.
     * @param array $params  The parameters needed for decryption.
     *                       See the individual _decrypt*() functions for the
     *                       parameter requirements.
     *
     * @return stdClass  An object with the following properties:
     * <pre>
     * 'message' - (string) The signature result text.
     * 'result' - (boolean) The result of the signature test.
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    public function decrypt($text, $params = array())
    {
        if (isset($params['type'])) {
            if ($params['type'] === 'message') {
                return $this->_decryptMessage($text, $params);
            } elseif (($params['type'] === 'signature') ||
                      ($params['type'] === 'detached-signature')) {
                return $this->_decryptSignature($text, $params);
            }
        }
    }

    /**
     * Returns whether a text has been encrypted symmetrically.
     *
     * @param string $text  The PGP encrypted text.
     *
     * @return boolean  True if the text is symmetricallly encrypted.
     * @throws Horde_Crypt_Exception
     */
    public function encryptedSymmetrically($text)
    {
        $cmdline = array(
            '--decrypt',
            '--batch'
        );
        $result = $this->_callGpg($cmdline, 'w', $text, true, true, true);
        return strpos($result->stderr, 'gpg: encrypted with 1 passphrase') !== false;
    }

    /**
     * Creates a temporary gpg keyring.
     *
     * @param string $type  The type of key to analyze. Either 'public'
     *                      (Default) or 'private'
     *
     * @return string  Command line keystring option to use with gpg program.
     */
    protected function _createKeyring($type = 'public')
    {
        $type = Horde_String::lower($type);

        if ($type === 'public') {
            if (empty($this->_publicKeyring)) {
                $this->_publicKeyring = $this->_createTempFile('horde-pgp');
            }
            return '--keyring ' . $this->_publicKeyring;
        } elseif ($type === 'private') {
            if (empty($this->_privateKeyring)) {
                $this->_privateKeyring = $this->_createTempFile('horde-pgp');
            }
            return '--secret-keyring ' . $this->_privateKeyring;
        }
    }

    /**
     * Adds PGP keys to the keyring.
     *
     * @param mixed $keys   A single key or an array of key(s) to add to the
     *                      keyring.
     * @param string $type  The type of key(s) to add. Either 'public'
     *                      (Default) or 'private'
     *
     * @return string  Command line keystring option to use with gpg program.
     * @throws Horde_Crypt_Exception
     */
    protected function _putInKeyring($keys = array(), $type = 'public')
    {
        $type = Horde_String::lower($type);

        if (!is_array($keys)) {
            $keys = array($keys);
        }

        /* Create the keyrings if they don't already exist. */
        $keyring = $this->_createKeyring($type);

        /* Store the key(s) in the keyring. */
        $cmdline = array(
            '--allow-secret-key-import',
            '--fast-import',
            $keyring
        );
        $this->_callGpg($cmdline, 'w', array_values($keys));

        return $keyring;
    }

    /**
     * Encrypts a message in PGP format using a public key.
     *
     * @param string $text   The text to be encrypted.
     * @param array $params  The parameters needed for encryption.
     * <pre>
     * Parameters:
     * ===========
     * 'type'       => 'message' (REQUIRED)
     * 'symmetric'  => Whether to use symmetric instead of asymmetric
     *                 encryption (defaults to false)
     * 'recips'     => An array with the e-mail address of the recipient as
     *                 the key and that person's public key as the value.
     *                 (REQUIRED if 'symmetric' is false)
     * 'passphrase' => The passphrase for the symmetric encryption (REQUIRED if
     *                 'symmetric' is true)
     * </pre>
     *
     * @return string  The encrypted message.
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptMessage($text, $params)
    {
        /* Create temp files for input. */
        $input = $this->_createTempFile('horde-pgp');
        file_put_contents($input, $text);

        /* Build command line. */
        $cmdline = array(
            '--armor',
            '--batch',
            '--always-trust'
        );

        if (empty($params['symmetric'])) {
            /* Store public key in temporary keyring. */
            $keyring = $this->_putInKeyring(array_values($params['recips']));

            $cmdline[] = $keyring;
            $cmdline[] = '--encrypt';
            foreach (array_keys($params['recips']) as $val) {
                $cmdline[] = '--recipient ' . $val;
            }
        } else {
            $cmdline[] = '--symmetric';
            $cmdline[] = '--passphrase-fd 0';
        }
        $cmdline[] = $input;

        /* Encrypt the document. */
        $result = $this->_callGpg($cmdline, 'w', empty($params['symmetric']) ? null : $params['passphrase'], true, true);
        if (empty($result->output)) {
            $error = preg_replace('/\n.*/', '', $result->stderr);
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not PGP encrypt message: ") . $error);
        }

        return $result->output;
    }

    /**
     * Signs a message in PGP format using a private key.
     *
     * @param string $text   The text to be signed.
     * @param array $params  The parameters needed for signing.
     * <pre>
     * Parameters:
     * ===========
     * 'type'        =>  'signature' (REQUIRED)
     * 'pubkey'      =>  PGP public key. (REQUIRED)
     * 'privkey'     =>  PGP private key. (REQUIRED)
     * 'passphrase'  =>  Passphrase for PGP Key. (REQUIRED)
     * 'sigtype'     =>  Determine the signature type to use. (Optional)
     *                   'cleartext'  --  Make a clear text signature
     *                   'detach'     --  Make a detached signature (DEFAULT)
     * </pre>
     *
     * @return string  The signed message.
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptSignature($text, $params)
    {
        /* Check for required parameters. */
        if (!isset($params['pubkey']) ||
            !isset($params['privkey']) ||
            !isset($params['passphrase'])) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A public PGP key, private PGP key, and passphrase are required to sign a message."));
        }

        /* Create temp files for input. */
        $input = $this->_createTempFile('horde-pgp');

        /* Encryption requires both keyrings. */
        $pub_keyring = $this->_putInKeyring(array($params['pubkey']));
        $sec_keyring = $this->_putInKeyring(array($params['privkey']), 'private');

        /* Store message in temporary file. */
        file_put_contents($input, $text);

        /* Determine the signature type to use. */
        $cmdline = array();
        if (isset($params['sigtype']) &&
            $params['sigtype'] == 'cleartext') {
            $sign_type = '--clearsign';
        } else {
            $sign_type = '--detach-sign';
        }

        /* Additional GPG options. */
        $cmdline += array(
            '--armor',
            '--batch',
            '--passphrase-fd 0',
            $sec_keyring,
            $pub_keyring,
            $sign_type,
            $input
        );

        /* Sign the document. */
        $result = $this->_callGpg($cmdline, 'w', $params['passphrase'], true, true);
        if (empty($result->output)) {
            $error = preg_replace('/\n.*/', '', $result->stderr);
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not PGP sign message: ") . $error);
        }

        return $result->output;
    }

    /**
     * Decrypts an PGP encrypted message using a private/public keypair and a
     * passhprase.
     *
     * @param string $text   The text to be decrypted.
     * @param array $params  The parameters needed for decryption.
     * <pre>
     * Parameters:
     * ===========
     * 'type'        =>  'message' (REQUIRED)
     * 'pubkey'      =>  PGP public key. (REQUIRED for asymmetric encryption)
     * 'privkey'     =>  PGP private key. (REQUIRED for asymmetric encryption)
     * 'passphrase'  =>  Passphrase for PGP Key. (REQUIRED)
     * </pre>
     *
     * @return stdClass  An object with the following properties:
     * <pre>
     * 'message'     -  The decrypted message.
     * 'sig_result'  -  The result of the signature test.
     * </pre>
     * @return stdClass  An object with the following properties:
     * <pre>
     * 'message' - (string) The signature result text.
     * 'result' - (boolean) The result of the signature test.
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    protected function _decryptMessage($text, $params)
    {
        $good_sig_flag = false;

        /* Check for required parameters. */
        if (!isset($params['passphrase']) && empty($params['no_passphrase'])) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A passphrase is required to decrypt a message."));
        }

        /* Create temp files. */
        $input = $this->_createTempFile('horde-pgp');

        /* Store message in file. */
        file_put_contents($input, $text);

        /* Build command line. */
        $cmdline = array(
            '--always-trust',
            '--armor',
            '--batch'
        );
        if (empty($param['no_passphrase'])) {
            $cmdline[] = '--passphrase-fd 0';
        }
        if (!empty($params['pubkey']) && !empty($params['privkey'])) {
            /* Decryption requires both keyrings. */
            $pub_keyring = $this->_putInKeyring(array($params['pubkey']));
            $sec_keyring = $this->_putInKeyring(array($params['privkey']), 'private');
            $cmdline[] = $sec_keyring;
            $cmdline[] = $pub_keyring;
        }
        $cmdline[] = '--decrypt';
        $cmdline[] = $input;

        /* Decrypt the document now. */
        if (empty($params['no_passphrase'])) {
            $result = $this->_callGpg($cmdline, 'w', $params['passphrase'], true, true);
        } else {
            $result = $this->_callGpg($cmdline, 'r', null, true, true);
        }
        if (empty($result->output)) {
            $error = preg_replace('/\n.*/', '', $result->stderr);
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not decrypt PGP data: ") . $error);
        }

        /* Create the return object. */
        return $this->_checkSignatureResult($result->stderr, $result->output);
    }

    /**
     * Decrypts an PGP signed message using a public key.
     *
     * @param string $text   The text to be verified.
     * @param array $params  The parameters needed for verification.
     * <pre>
     * Parameters:
     * ===========
     * 'type'       =>  'signature' or 'detached-signature' (REQUIRED)
     * 'pubkey'     =>  PGP public key. (REQUIRED)
     * 'signature'  =>  PGP signature block. (REQUIRED for detached signature)
     * </pre>
     *
     * @return stdClass  An object with the following properties:
     * <pre>
     * 'message' - (string) The signature result text.
     * 'result' - (boolean) The result of the signature test.
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    protected function _decryptSignature($text, $params)
    {
        /* Check for required parameters. */
        if (!isset($params['pubkey'])) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A public PGP key is required to verify a signed message."));
        }
        if (($params['type'] === 'detached-signature') &&
            !isset($params['signature'])) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("The detached PGP signature block is required to verify the signed message."));
        }

        $good_sig_flag = 0;

        /* Create temp files for input. */
        $input = $this->_createTempFile('horde-pgp');

        /* Store public key in temporary keyring. */
        $keyring = $this->_putInKeyring($params['pubkey']);

        /* Store the message in a temporary file. */
        file_put_contents($input, $text);

        /* Options for the GPG binary. */
        $cmdline = array(
            '--armor',
            '--always-trust',
            '--batch',
            '--charset UTF-8',
            $keyring,
            '--verify'
        );

        /* Extra stuff to do if we are using a detached signature. */
        if ($params['type'] === 'detached-signature') {
            $sigfile = $this->_createTempFile('horde-pgp');
            $cmdline[] = $sigfile . ' ' . $input;
            file_put_contents($sigfile, $params['signature']);
        } else {
            $cmdline[] = $input;
        }

        /* Verify the signature.  We need to catch standard error output,
         * since this is where the signature information is sent. */
        $result = $this->_callGpg($cmdline, 'r', null, true, true);
        return $this->_checkSignatureResult($result->stderr, $result->stderr);
    }

    /**
     * Checks signature result from the GnuPG binary.
     *
     * @param string $result   The signature result.
     * @param string $message  The decrypted message data.
     *
     * @return stdClass  An object with the following properties:
     * <pre>
     * 'message' - (string) The signature result text.
     * 'result' - (boolean) The result of the signature test.
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    protected function _checkSignatureResult($result, $message = null)
    {
        /* Good signature:
         *   gpg: Good signature from "blah blah blah (Comment)"
         * Bad signature:
         *   gpg: BAD signature from "blah blah blah (Comment)" */
        if (strpos($result, 'gpg: BAD signature') !== false) {
            throw new Horde_Crypt_Exception($result);
        }

        $ob = new stdClass;
        $ob->message = $message;
        $ob->result = (strpos($result, 'gpg: Good signature') !== false);

        return $ob;
    }

    /**
     * Signs a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     * @param array $params               The parameters required for signing.
     *                                    @see _encryptSignature().
     *
     * @return mixed  A Horde_Mime_Part object that is signed according to RFC
     *                3156.
     * @throws Horde_Crypt_Exception
     */
    public function signMIMEPart($mime_part, $params = array())
    {
        $params = array_merge($params, array('type' => 'signature', 'sigtype' => 'detach'));

        /* RFC 3156 Requirements for a PGP signed message:
         * + Content-Type params 'micalg' & 'protocol' are REQUIRED.
         * + The digitally signed message MUST be constrained to 7 bits.
         * + The MIME headers MUST be a part of the signed data. */
        $msg_sign = $this->encrypt($mime_part->toString(array('headers' => true, 'canonical' => true, 'encode' => Horde_Mime_Part::ENCODE_7BIT)), $params);

        /* Add the PGP signature. */
        $pgp_sign = new Horde_Mime_Part();
        $pgp_sign->setType('application/pgp-signature');
        $pgp_sign->setCharset($this->_params['email_charset']);
        $pgp_sign->setDisposition('inline');
        $pgp_sign->setDescription(Horde_String::convertCharset(Horde_Crypt_Translation::t("PGP Digital Signature"), 'UTF-8', $this->_params['email_charset']));
        $pgp_sign->setContents($msg_sign, array('encoding' => '7bit'));

        /* Get the algorithim information from the signature. Since we are
         * analyzing a signature packet, we need to use the special keyword
         * '_SIGNATURE' - see Horde_Crypt_Pgp. */
        $sig_info = $this->pgpPacketSignature($msg_sign, '_SIGNATURE');

        /* Setup the multipart MIME Part. */
        $part = new Horde_Mime_Part();
        $part->setType('multipart/signed');
        $part->setContents("This message is in MIME format and has been PGP signed.\n");
        $part->addPart($mime_part);
        $part->addPart($pgp_sign);
        $part->setContentTypeParameter('protocol', 'application/pgp-signature');
        $part->setContentTypeParameter('micalg', $sig_info['micalg']);

        return $part;
    }

    /**
     * Encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part  The object to encrypt.
     * @param array $params               The parameters required for
     *                                    encryption.
     *                                    @see _encryptMessage().
     *
     * @return mixed  A Horde_Mime_Part object that is encrypted according to
     *                RFC 3156.
     * @throws Horde_Crypt_Exception
     */
    public function encryptMIMEPart($mime_part, $params = array())
    {
        $params = array_merge($params, array('type' => 'message'));

        $signenc_body = $mime_part->toString(array('headers' => true, 'canonical' => true));
        $message_encrypt = $this->encrypt($signenc_body, $params);

        /* Set up MIME Structure according to RFC 3156. */
        $part = new Horde_Mime_Part();
        $part->setType('multipart/encrypted');
        $part->setCharset($this->_params['email_charset']);
        $part->setContentTypeParameter('protocol', 'application/pgp-encrypted');
        $part->setDescription(Horde_String::convertCharset(Horde_Crypt_Translation::t("PGP Encrypted Data"), 'UTF-8', $this->_params['email_charset']));
        $part->setContents("This message is in MIME format and has been PGP encrypted.\n");

        $part1 = new Horde_Mime_Part();
        $part1->setType('application/pgp-encrypted');
        $part1->setCharset(null);
        $part1->setContents("Version: 1\n", array('encoding' => '7bit'));
        $part->addPart($part1);

        $part2 = new Horde_Mime_Part();
        $part2->setType('application/octet-stream');
        $part2->setCharset(null);
        $part2->setContents($message_encrypt, array('encoding' => '7bit'));
        $part2->setDisposition('inline');
        $part->addPart($part2);

        return $part;
    }

    /**
     * Signs and encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part   The object to sign and encrypt.
     * @param array $sign_params           The parameters required for
     *                                     signing. @see _encryptSignature().
     * @param array $encrypt_params        The parameters required for
     *                                     encryption. @see _encryptMessage().
     *
     * @return mixed  A Horde_Mime_Part object that is signed and encrypted
     *                according to RFC 3156.
     * @throws Horde_Crypt_Exception
     */
    public function signAndEncryptMIMEPart($mime_part, $sign_params = array(),
                                           $encrypt_params = array())
    {
        /* RFC 3156 requires that the entire signed message be encrypted.  We
         * need to explicitly call using Horde_Crypt_Pgp:: because we don't
         * know whether a subclass has extended these methods. */
        $part = $this->signMIMEPart($mime_part, $sign_params);
        $part = $this->encryptMIMEPart($part, $encrypt_params);
        $part->setContents("This message is in MIME format and has been PGP signed and encrypted.\n");

        $part->setCharset($this->_params['email_charset']);
        $part->setDescription(Horde_String::convertCharset(Horde_Crypt_Translation::t("PGP Signed/Encrypted Data"), 'UTF-8', $this->_params['email_charset']));

        return $part;
    }

    /**
     * Generates a Horde_Mime_Part object, in accordance with RFC 3156, that
     * contains a public key.
     *
     * @param string $key  The public key.
     *
     * @return Horde_Mime_Part  An object that contains the public key.
     */
    public function publicKeyMIMEPart($key)
    {
        $part = new Horde_Mime_Part();
        $part->setType('application/pgp-keys');
        $part->setCharset($this->_params['email_charset']);
        $part->setDescription(Horde_String::convertCharset(Horde_Crypt_Translation::t("PGP Public Key"), 'UTF-8', $this->_params['email_charset']));
        $part->setContents($key, array('encoding' => '7bit'));

        return $part;
    }

    /**
     * Function that handles interfacing with the GnuPG binary.
     *
     * @param array $options    Options and commands to pass to GnuPG.
     * @param string $mode      'r' to read from stdout, 'w' to write to
     *                          stdin.
     * @param array $input      Input to write to stdin.
     * @param boolean $output   Collect and store output in object returned?
     * @param boolean $stderr   Collect and store stderr in object returned?
     * @param boolean $verbose  Run GnuPG with verbose flag?
     *
     * @return stdClass  Class with members output, stderr, and stdout.
     * @throws Horde_Crypt_Exception
     */
    protected function _callGpg($options, $mode, $input = array(),
                                $output = false, $stderr = false,
                                $verbose = false)
    {
        $data = new stdClass;
        $data->output = null;
        $data->stderr = null;
        $data->stdout = null;

        /* Verbose output? */
        if (!$verbose) {
            array_unshift($options, '--quiet');
        }

        /* Create temp files for output. */
        if ($output) {
            $output_file = $this->_createTempFile('horde-pgp', false);
            array_unshift($options, '--output ' . $output_file);

            /* Do we need standard error output? */
            if ($stderr) {
                $stderr_file = $this->_createTempFile('horde-pgp', false);
                $options[] = '2> ' . $stderr_file;
            }
        }

        /* Silence errors if not requested. */
        if (!$output || !$stderr) {
            $options[] = '2> /dev/null';
        }

        /* Build the command line string now. */
        $cmdline = implode(' ', array_merge($this->_gnupg, $options));

        if ($mode == 'w') {
            if ($fp = popen($cmdline, 'w')) {
                $win32 = !strncasecmp(PHP_OS, 'WIN', 3);

                if (!is_array($input)) {
                    $input = array($input);
                }

                foreach ($input as $line) {
                    if ($win32 && (strpos($line, "\x0d\x0a") !== false)) {
                        $chunks = explode("\x0d\x0a", $line);
                        foreach ($chunks as $chunk) {
                            fputs($fp, $chunk . "\n");
                        }
                    } else {
                        fputs($fp, $line . "\n");
                    }
                }
            } else {
                throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to pgp binary."));
            }
        } elseif ($mode == 'r') {
            if ($fp = popen($cmdline, 'r')) {
                while (!feof($fp)) {
                    $data->stdout .= fgets($fp, 1024);
                }
            } else {
                throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to pgp binary."));
            }
        }
        pclose($fp);

        if ($output) {
            $data->output = file_get_contents($output_file);
            unlink($output_file);
            if ($stderr) {
                $data->stderr = file_get_contents($stderr_file);
                unlink($stderr_file);
            }
        }

        return $data;
    }

    /**
     * Generates a revocation certificate.
     *
     * @param string $key         The private key.
     * @param string $email       The email to use for the key.
     * @param string $passphrase  The passphrase to use for the key.
     *
     * @return string  The revocation certificate.
     * @throws Horde_Crypt_Exception
     */
    public function generateRevocation($key, $email, $passphrase)
    {
        $keyring = $this->_putInKeyring($key, 'private');

        /* Prepare the canned answers. */
        $input = array(
            'y', // Really generate a revocation certificate
            '0', // Refuse to specify a reason
            '',  // Empty comment
            'y', // Confirm empty comment
        );
        if (!empty($passphrase)) {
            $input[] = $passphrase;
        }

        /* Run through gpg binary. */
        $cmdline = array(
            $keyring,
            '--command-fd 0',
            '--gen-revoke ' . $email,
        );
        $results = $this->_callGpg($cmdline, 'w', $input, true);

        /* If the key is empty, something went wrong. */
        if (empty($results->output)) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Revocation key not generated successfully."));
        }

        return $results->output;
    }

}
