<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * PGP backend that uses the gnupg binary.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */
class Horde_Crypt_Pgp_Backend_Binary
extends Horde_Crypt_Pgp_Backend
{
    /**
     * GnuPG program location/common options.
     *
     * @var array
     */
    protected $_gnupg;

    /**
     * Filename of the temporary private keyring.
     *
     * @var string
     */
    protected $_privateKeyring;

    /**
     * Filename of the temporary public keyring.
     *
     * @var string
     */
    protected $_publicKeyring;

    /**
     * The temporary directory to use.
     *
     * @var string
     */
    protected $_tempdir;

    /**
     * Constructor.
     *
     * @param string $gnupg  The path to the GnuPG binary.
     * @param string $temp   Location of temporary directory.
     */
    public function __construct($gnupg, $temp = null)
    {
        $this->_tempdir = Horde_Util::createTempDir(
            true,
            is_null($temp) ? sys_get_temp_dir() : $temp
        );

        /* Store the location of GnuPG and set common options. */
        $this->_gnupg = array(
            $gnupg,
            '--no-tty',
            '--no-secmem-warning',
            '--no-options',
            '--no-default-keyring',
            '--yes',
            '--homedir ' . $this->_tempdir
        );
    }

    /**
     */
    public function generateKey($opts)
    {
        /* Create temp files to hold the generated keys. */
        $pub_file = $this->_createTempFile('horde-pgp');
        $secret_file = $this->_createTempFile('horde-pgp');

        $expire = empty($opts['expire'])
            ? 0
            : date('Y-m-d', $opts['expire']);

        /* Create the config file necessary for GnuPG to run in batch mode. */
        /* TODO: Sanitize input, More user customizable? */
        $input = array(
            '%pubring ' . $pub_file,
            '%secring ' . $secret_file,
            'Key-Type: ' . $opts['key_type'],
            'Key-Length: ' . $opts['keylength'],
            'Subkey-Type: ' . $opts['subkey_type'],
            'Subkey-Length: ' . $opts['keylength'],
            'Name-Real: ' . $opts['name'],
            'Name-Email: ' . $opts['email'],
            'Expire-Date: ' . $expire,
            'Passphrase: ' . $opts['passphrase'],
            'Preferences: AES256 AES192 AES CAST5 3DES SHA256 SHA512 SHA384 SHA224 SHA1 ZLIB BZIP2 ZIP Uncompressed'
        );
        if (!empty($opts['comment'])) {
            $input[] = 'Name-Comment: ' . $comment;
        }
        $input[] = '%commit';

        /* Run through gpg binary. */
        $result = $this->_callGpg(
            array(
                '--gen-key',
                '--batch',
                '--armor'
            ),
            'w',
            $input,
            true,
            true
        );

        /* Get the keys from the temp files. */
        $public_key = file_get_contents($pub_file);
        $secret_key = file_get_contents($secret_file);

        /* If either key is empty, something went wrong. */
        if (empty($public_key) || empty($secret_key)) {
            throw new RuntimeException();
        }

        return array(
            'public' => $public_key,
            'private' => $secret_key
        );
    }

    /**
     */
    public function packetInfo($pgpdata)
    {
        $header = $keyid = null;
        $input = $this->_createTempFile('horde-pgp');
        $sig_id = $uid_idx = 0;
        $out = array();

        $this2 = $this;
        $packetInfoKeyId = function ($input) use ($this2) {
            $data = $this2->_callGpg(array('--with-colons', $input), 'r');
            return preg_match('/(sec|pub):.*:.*:.*:([A-F0-9]{16}):/', $data->stdout, $matches)
                ? $matches[2]
                : null;
        };

        $packetInfoHelper = function ($a) {
            return chr(hexdec($a[1]));
        };

        /* The list of PGP hash algorithms (from RFC 3156). */
        $hashAlg = array(
            1 => 'pgp-md5',
            2 => 'pgp-sha1',
            3 => 'pgp-ripemd160',
            5 => 'pgp-md2',
            6 => 'pgp-tiger192',
            7 => 'pgp-haval-5-160',
            8 => 'pgp-sha256',
            9 => 'pgp-sha384',
            10 => 'pgp-sha512',
            11 => 'pgp-sha224'
        );

        /* Store message in temporary file. */
        file_put_contents($input, $pgpdata);

        $cmdline = array(
            '--list-packets',
            $input
        );
        $result = $this->_callGpg($cmdline, 'r', null, false, false, true);

        foreach (explode("\n", $result->stdout) as $line) {
            /* Headers are prefaced with a ':' as the first character on the
             * line. */
            if (strpos($line, ':') === 0) {
                $lowerLine = Horde_String::lower($line);

                if (strpos($lowerLine, ':public key packet:') !== false) {
                    $header = 'public_key';
                } elseif (strpos($lowerLine, ':secret key packet:') !== false) {
                    $header = 'secret_key';
                } elseif (strpos($lowerLine, ':user id packet:') !== false) {
                    $uid_idx++;
                    $line = preg_replace_callback('/\\\\x([0-9a-f]{2})/', $packetInfoHelper, $line);
                    if (preg_match("/\"([^\<]+)\<([^\>]+)\>\"/", $line, $matches)) {
                        $header = 'id' . $uid_idx;
                        if (preg_match('/([^\(]+)\((.+)\)$/', trim($matches[1]), $comment_matches)) {
                            $out['signature'][$header]['name'] = trim($comment_matches[1]);
                            $out['signature'][$header]['comment'] = $comment_matches[2];
                        } else {
                            $out['signature'][$header]['name'] = trim($matches[1]);
                            $out['signature'][$header]['comment'] = '';
                        }
                        $out['signature'][$header]['email'] = $matches[2];
                        if (is_null($keyid)) {
                            $keyid = $packetInfoKeyId($input);
                        }
                        $out['signature'][$header]['keyid'] = $keyid;
                    }
                } elseif (strpos($lowerLine, ':signature packet:') !== false) {
                    if (empty($header) || empty($uid_idx)) {
                        $header = '_SIGNATURE';
                    }
                    if (preg_match("/keyid\s+([0-9A-F]+)/i", $line, $matches)) {
                        $sig_id = $matches[1];
                        $out['signature'][$header]['sig_' . $sig_id]['keyid'] = $matches[1];
                        $out['keyid'] = $matches[1];
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
                        $out[$header]['created'] = $matches[1];
                        $out[$header]['expires'] = $matches[2];
                    } elseif (preg_match("/\s+[sp]key\[0\]:\s+\[(\d+)/i", $line, $matches)) {
                        $out[$header]['size'] = $matches[1];
                    } elseif (preg_match("/\s+keyid:\s+([0-9A-F]+)/i", $line, $matches)) {
                        $keyid = $matches[1];
                    }
                } elseif ($header == 'literal' || $header == 'encrypted') {
                    $out[$header] = true;
                } elseif ($header) {
                    if (preg_match("/version\s+\d+,\s+created\s+(\d+)/i", $line, $matches)) {
                        $out['signature'][$header]['sig_' . $sig_id]['created'] = $matches[1];
                    } elseif (isset($out['signature'][$header]['sig_' . $sig_id]['created']) &&
                              preg_match('/expires after (\d+y\d+d\d+h\d+m)\)$/', $line, $matches)) {
                        $expires = $matches[1];
                        preg_match('/^(\d+)y(\d+)d(\d+)h(\d+)m$/', $expires, $matches);
                        list(, $years, $days, $hours, $minutes) = $matches;
                        $out['signature'][$header]['sig_' . $sig_id]['expires'] =
                            strtotime('+ ' . $years . ' years + ' . $days . ' days + ' . $hours . ' hours + ' . $minutes . ' minutes', $out['signature'][$header]['sig_' . $sig_id]['created']);
                    } elseif (preg_match("/digest algo\s+(\d{1})/", $line, $matches)) {
                        $micalg = $hashAlg[$matches[1]];
                        $out['signature'][$header]['sig_' . $sig_id]['micalg'] = $micalg;
                        if ($header == '_SIGNATURE') {
                            /* Likely a signature block, not a key. */
                            $out['signature']['_SIGNATURE']['micalg'] = $micalg;
                        }

                        if (is_null($keyid)) {
                            $keyid = $packetInfoKeyId($input);
                        }

                        if ($sig_id == $keyid) {
                            /* Self signing signature - we can assume
                             * the micalg value from this signature is
                             * that for the key */
                            $out['signature']['_SIGNATURE']['micalg'] = $micalg;
                            $out['signature'][$header]['micalg'] = $micalg;
                        }
                    }
                }
            }
        }

        if (is_null($keyid)) {
            $keyid = $packetInfoKeyId($input);
        }

        $keyid && $out['keyid'] = $keyid;

        return $out;
    }

    /**
     */
    public function getSignersKeyID($text)
    {
        $input = $this->_createTempFile('horde-pgp');
        file_put_contents($input, $text);

        $result = $this->_callGpg(
            array(
                '--verify',
                $input
            ),
            'r',
            null,
            true,
            true,
            true
        );

        if (preg_match('/gpg:\sSignature\smade.*ID\s+([A-F0-9]{8})\s+/', $result->stderr, $matches)) {
            return $matches[1];
        }

        throw new RuntimeException();
    }

    /**
     */
    public function getFingerprintsFromKey($pgpdata)
    {
        /* Store the key in a temporary keyring. */
        $keyring = $this->_putInKeyring($pgpdata);

        $result = $this->_callGpg(
            array(
                '--fingerprint',
                $keyring,
            ),
            'r',
            null,
            true,
            false,
            true
        );
        if (!$result || !$result->stdout) {
            throw new RuntimeException();
        }

        /* Parse fingerprints and key ids from output. */
        $fingerprints = array();
        $keyid = null;
        $lines = explode("\n", $result->stdout);

        foreach ($lines as $line) {
            if (preg_match('/pub\s+\w+\/(\w{8})/', $line, $matches)) {
                $keyid = '0x' . $matches[1];
            } elseif ($keyid && preg_match('/^\s+[\s\w]+=\s*([\w\s]+)$/m', $line, $matches)) {
                $fingerprints[$keyid] = str_replace(' ', '', $matches[1]);
                $keyid = null;
            }
        }

        return empty($fingerprints)
            ? false
            : $fingerprints;
    }

    /**
     */
    public function isEncryptedSymmetrically($text)
    {
        $result = $this->_callGpg(
            array(
                '--decrypt',
                '--batch',
                '--passphrase ""'
            ),
            'w',
            $text,
            true,
            true,
            true,
            true
        );

        return (strpos($result->stderr, 'gpg: encrypted with 1 passphrase') !== false);
    }

    /**
     */
    public function encryptMessage($text, $params)
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
        $result = $this->_callGpg(
            $cmdline,
            'w',
            empty($params['symmetric']) ? null : $params['passphrase'],
            true,
            true
        );

        // TODO: error logging
        // $error = preg_replace('/\n.*/', '', $result->stderr);

        if (empty($result->output)) {
            throw new RuntimeException();
        }

        return $result->output;
    }

    /**
     */
    public function encryptSignature($text, $params)
    {
        /* Create temp files for input. */
        $input = $this->_createTempFile('horde-pgp');

        /* Encryption requires both keyrings. */
        $pub_keyring = $this->_putInKeyring(array($params['pubkey']));
        $sec_keyring = $this->_putInKeyring(
            array($params['privkey']),
            'private'
        );

        /* Store message in temporary file. */
        file_put_contents($input, $text);

        /* Sign the document. */
        $result = $this->_callGpg(
            array(
                '--armor',
                '--batch',
                '--passphrase-fd 0',
                $sec_keyring,
                $pub_keyring,
                (isset($params['sigtype']) && ($params['sigtype'] == 'cleartext')) ? '--clearsign' : '--detach-sign',
                $input
            ),
            'w',
            $params['passphrase'],
            true,
            true
        );

        // TODO: error logging
        // $error = preg_replace('/\n.*/', '', $result->stderr);

        if (empty($result->output)) {
            throw new RuntimeException();
        }

        return $result->output;
    }

    /**
     */
    public function decryptMessage($text, $params)
    {
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
        if (empty($params['no_passphrase'])) {
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

        $result = $this->_callGpg(
            $cmdline,
            empty($params['no_passphrase']) ? 'w' : 'r',
            empty($params['no_passphrase']) ? $params['passphrase'] : null,
            true,
            true,
            true
        );

        // TODO: error logging
        // $error = preg_replace('/\n.*/', '', $result->stderr);

        if (empty($result->output)) {
            throw new RuntimeException();
        }

        return $this->_checkSignatureResult($result->stderr, $result->output);
    }

    /**
     */
    public function decryptSignature($text, $params)
    {
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
            '--charset ' . (isset($params['charset']) ? $params['charset'] : 'UTF-8'),
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
        $result = $this->_callGpg($cmdline, 'r', null, true, true, true);

        return $this->_checkSignatureResult($result->stderr, $result->stderr);
    }

    /**
     */
    public function getPublicKeyFromPrivateKey($data)
    {
        $this->_putInKeyring(array($data), 'private');
        $fingerprints = $this->getFingerprintsFromKey($data);
        reset($fingerprints);

        $cmdline = array(
            '--armor',
            '--export',
            key($fingerprints)
        );

        $result = $this->_callGpg($cmdline, 'r', array(), true, true);

        if (empty($result->output)) {
            throw new RuntimeException();
        }

        return $result->output;
    }

    /**
     * Checks signature result from the GnuPG binary.
     *
     * @param string $result   The signature result.
     * @param string $message  The decrypted message data.
     *
     * @return object  See decryptSignature().
     */
    protected function _checkSignatureResult($result, $message = null)
    {
        /* Good signature:
         *   gpg: Good signature from "blah blah blah (Comment)"
         * Bad signature:
         *   gpg: BAD signature from "blah blah blah (Comment)" */
        if (strpos($result, 'gpg: BAD signature') !== false) {
            throw new RuntimeException();
        }

        $ob = new stdClass;
        $ob->message = $message;
        $ob->result = $result;

        return $ob;
    }

    /**
     * Function that handles interfacing with the GnuPG binary.
     *
     * @param array $options      Options and commands to pass to GnuPG.
     * @param string $mode        'r' to read from stdout, 'w' to write to
     *                            stdin.
     * @param array $input        Input to write to stdin.
     * @param boolean $output     Collect and store output in object returned?
     * @param boolean $stderr     Collect and store stderr in object returned?
     * @param boolean $parseable  Is parseable output required? The gpg binary
     *                            would be executed with C locale then.
     * @param boolean $verbose    Run GnuPG with verbose flag?
     *
     * @return stdClass  Class with members output, stderr, and stdout.
     * @throws Horde_Crypt_Exception
     * @todo This method should be protected, but due to closures not having
     *       proper access to $this without assigning it to another variable
     *       which does not give it access to non-puplic members, we must
     *       make this public until H6 when we can require at least PHP 5.4.
     */
    public function _callGpg($options, $mode, $input = array(),
                                $output = false, $stderr = false,
                                $parseable = false, $verbose = false)
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

        $language = getenv('LANGUAGE');
        if ($parseable) {
            putenv('LANGUAGE=C');
        }
        if ($mode == 'w') {
            if ($fp = popen($cmdline, 'w')) {
                putenv('LANGUAGE=' . $language);
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
                putenv('LANGUAGE=' . $language);
                throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to pgp binary."));
            }
        } elseif ($mode == 'r') {
            if ($fp = popen($cmdline, 'r')) {
                putenv('LANGUAGE=' . $language);
                while (!feof($fp)) {
                    $data->stdout .= fgets($fp, 1024);
                }
            } else {
                putenv('LANGUAGE=' . $language);
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
     * Creates a temporary gpg keyring.
     *
     * @param string $type  The type of key to analyze. 'public' or 'private'.
     *
     * @return string  Command line keystring option to use with gpg program.
     */
    protected function _createKeyring($type = 'public')
    {
        switch (Horde_String::lower($type)) {
        case 'public':
            if (empty($this->_publicKeyring)) {
                $this->_publicKeyring = $this->_createTempFile('horde-pgp');
            }
            return '--keyring ' . $this->_publicKeyring;

        case 'private':
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
     * @param string $type  The type of key(s) to add. 'public' or 'private'.
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

        /* Gnupg v2: --secret-keyring is not used, so import everything into
         * the main keyring also. */
        if ($type == 'private') {
            $this->_putInKeyring($keys);
        }

        /* Create the keyrings if they don't already exist. */
        $keyring = $this->_createKeyring($type);

        /* Store the key(s) in the keyring. */
        $this->_callGpg(
            array(
                '--allow-secret-key-import',
                '--batch',
                '--fast-import',
                $keyring
            ),
            'w',
            array_values($keys)
        );

        return $keyring;
    }

    /**
     * Create a temporary file that will be deleted at the end of this
     * process.
     *
     * @param string  $descrip  Description string to use in filename.
     * @param boolean $delete   Delete the file automatically?
     *
     * @return string  Filename of a temporary file.
     */
    protected function _createTempFile($descrip = 'horde-crypt',
                                       $delete = true)
    {
        return Horde_Util::getTempFile(
            $descrip,
            $delete,
            $this->_tempdir,
            true
        );
    }

}
