<?php
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * Provides methods to connect to a PGP keyserver.
 *
 * Connects to a public key server via HKP (Horrowitz Keyserver Protocol).
 * http://tools.ietf.org/html/draft-shaw-openpgp-hkp-00
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 * @since     2.4.0
 */
class Horde_Crypt_Pgp_Keyserver
{
    /**
     * HTTP object.
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Keyserver hostname.
     *
     * @var string
     */
    protected $_keyserver;

    /**
     * PGP object.
     *
     * @var Horde_Crypt_Pgp
     */
    protected $_pgp;

    /**
     * Constructor.
     *
     * @param Horde_Crypt_Pgp $pgp  A Horde_Crypt_Pgp object.
     * @param array $params         Optional parameters:
     * <pre>
     *   - http: (Horde_Http_Client) The HTTP client object to use.
     *   - keyserver: (string) The public PGP keyserver to use.
     *   - port: (integer) The public PGP keyserver port.
     * </pre>
     */
    public function __construct(Horde_Crypt_Pgp $pgp, array $params = array())
    {
        $this->_pgp = $pgp;
        $this->_http = (isset($params['http']) && ($params['http'] instanceof Horde_Http_Client))
            ? $params['http']
            : new Horde_Http_Client();
        $this->_keyserver = isset($params['keyserver'])
            ? $params['keyserver']
            : 'pool.sks-keyservers.net';
        $this->_keyserver .= ':' . (isset($params['port']) ? $params['port'] : '11371');
    }

    /**
     * Returns PGP public key data retrieved from a public keyserver.
     *
     * @param string $keyid  The key ID of the PGP key.
     *
     * @return string  The PGP public key.
     * @throws Horde_Crypt_Exception
     */
    public function get($keyid)
    {
        /* Connect to the public keyserver. */
        $url = $this->_createUrl('/pks/lookup', array(
            'op' => 'get',
            'search' => $this->_pgp->getKeyIDString($keyid)
        ));

        try {
            $output = $this->_http->get($url)->getBody();
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Crypt_Exception($e);
        }

        /* Grab PGP key from output. */
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
     *
     * @throws Horde_Crypt_Exception
     */
    public function put($pubkey)
    {
        /* Get the key ID of the public key. */
        $info = $this->_pgp->pgpPacketInformation($pubkey);

        /* See if the public key already exists on the keyserver. */
        try {
            $this->get($info['keyid']);
        } catch (Horde_Crypt_Exception $e) {
            $pubkey = 'keytext=' . urlencode(rtrim($pubkey));
            try {
                $this->_http->post(
                    $this->_createUrl('/pks/add'),
                    $pubkey,
                    array(
                        'User-Agent: Horde Application Framework',
                        'Content-Type: application/x-www-form-urlencoded',
                        'Content-Length: ' . strlen($pubkey),
                        'Connection: close'
                    )
                );
            } catch (Horde_Http_Exception $e) {
                throw new Horde_Crypt_Exception($e);
            }
        }

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Key already exists on the public keyserver."));
    }

    /**
     * Returns the first matching key ID for an email address from a public
     * keyserver.
     *
     * @param string $address  The email address of the PGP key.
     *
     * @return string  The PGP key ID.
     * @throws Horde_Crypt_Exception
     */
    public function getKeyId($address)
    {
        $pubkey = null;

        /* Connect to the public keyserver. */
        $url = $this->_createUrl('/pks/lookup', array(
            'op' => 'index',
            'options' => 'mr',
            'search' => $address
        ));

        try {
            $output = $this->_http->get($url)->getBody();
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Crypt_Exception($e);
        }

        if (strpos($output, '-----BEGIN PGP PUBLIC KEY BLOCK') !== false) {
            $pubkey = $output;
        } elseif (strpos($output, 'pub:') !== false) {
            $output = explode("\n", $output);
            $keyids = $keyuids = array();
            $curid = null;

            foreach ($output as $line) {
                if (substr($line, 0, 4) == 'pub:') {
                    $line = explode(':', $line);
                    /* Ignore invalid lines and expired keys. */
                    if (count($line) != 7 ||
                        (!empty($line[5]) && $line[5] <= time())) {
                        continue;
                    }
                    $curid = $line[4];
                    $keyids[$curid] = $line[1];
                } elseif (!is_null($curid) && substr($line, 0, 4) == 'uid:') {
                    preg_match("/<([^>]+)>/", $line, $matches);
                    $keyuids[$curid][] = $matches[1];
                }
            }

            /* Remove keys without a matching UID. */
            foreach ($keyuids as $id => $uids) {
                $match = false;
                foreach ($uids as $uid) {
                    if ($uid == $address) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    unset($keyids[$id]);
                }
            }

            /* Sort by timestamp to use the newest key. */
            if (count($keyids)) {
                ksort($keyids);
                $pubkey = $this->get(array_pop($keyids));
            }
        }

        if ($pubkey) {
            $sig = $this->_pgp->pgpPacketSignature($pubkey, $address);
            if (!empty($sig['keyid']) &&
                (empty($sig['public_key']['expires']) ||
                 $sig['public_key']['expires'] > time())) {
                return substr($this->_pgp->getKeyIDString($sig['keyid']), 2);
            }
        }

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not obtain public key from the keyserver."));
    }

    /**
     * Create the URL for the keyserver.
     *
     * @param string $uri    Action URI.
     * @param array $params  List of parameters to add to URL.
     *
     * @return Horde_Url  Keyserver URL.
     */
    protected function _createUrl($uri, array $params = array())
    {
        $url = new Horde_Url($this->_keyserver . $uri, true);
        return $url->add($params);
    }

}
