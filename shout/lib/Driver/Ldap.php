<?php
/**
 * Provides the LDAP backend driver for the Shout application.
 *
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */

class Shout_Driver_Ldap extends Shout_Driver
{
    var $_ldapKey;  // Index used for storing objects
    var $_appKey;   // Index used for moving info to/from the app

    /**
     * Handle for the current database connection.
     * @var object LDAP $_LDAP
     */
    private $_LDAP;

    /**
     * Boolean indicating whether or not we're connected to the LDAP
     * server.
     * @var boolean $_connected
     */
    private $_connected = false;


    /**
    * Constructs a new Shout LDAP driver object.
    *
    * @param array  $params    A hash containing connection parameters.
    */
    function __construct($params = array())
    {
        parent::__construct($params);
        $this->_connect();
    }

    /**
     * Get a list of users valid for the accounts
     *
     * @param string $account  Account in which to search
     *
     * @return array User information indexed by voice mailbox number
     */
    public function getExtensions($account)
    {
        if (empty($account)) {
            throw new Shout_Exception('Must specify an account code');
        }
        static $entries = array();
        if (isset($entries[$account])) {
            return $entries[$account];
        }

        $this->_params['basedn'];

        $filter  = '(&';
        $filter .= '(objectClass=AsteriskVoiceMail)';
        $filter .= '(objectClass=AsteriskUser)';
        $filter .= '(AstContext='.$account.')';
        $filter .= ')';

        $attributes = array(
            'cn',
            'AstVoicemailEmail',
            'AstVoicemailMailbox',
            'AstVoicemailPassword',
            'AstVoicemailOptions',
            'AstVoicemailPager',
            'telephoneNumber',
            'AstUserChannel'
        );

        $search = ldap_search($this->_LDAP, $this->_params['basedn'], $filter, $attributes);
        if ($search === false) {
            throw new Shout_Exception("Unable to search directory: " .
                ldap_error($this->_LDAP), ldap_errno($this->_LDAP));
        }

        $res = ldap_get_entries($this->_LDAP, $search);
        if ($res === false) {
            throw new Shout_Exception("Unable to fetch results from directory: " .
                ldap_error($this->_LDAP), ldap_errno($this->_LDAP));
        }

        // ATTRIBUTES RETURNED FROM ldap_get_entries ARE ALL LOWER CASE!!
        // It's a PHP thing.
        $entries[$account] = array();
        $i = 0;
        while ($i < $res['count']) {
            list($extension) = explode('@', $res[$i]['astvoicemailmailbox'][0]);
            $entries[$account][$extension] = array('extension' => $extension);

            $j = 0;
            $entries[$account][$extension]['mailboxopts'] = array();
            if (empty($res[$i]['astvoicemailoptions']['count'])) {
                $res[$i]['astvoicemailoptions']['count'] = -1;
            }
            while ($j < $res[$i]['astvoicemailoptions']['count']) {
                $entries[$account][$extension]['mailboxopts'][] =
                    $res[$i]['astvoicemailoptions'][$j];
                $j++;
            }

            $entries[$account][$extension]['mailboxpin'] =
                $res[$i]['astvoicemailpassword'][0];

            $entries[$account][$extension]['name'] =
                $res[$i]['cn'][0];

            $entries[$account][$extension]['email'] =
                $res[$i]['astvoicemailemail'][0];

            $entries[$account][$extension]['pageremail'] =
                $res[$i]['astvoicemailpager'][0];

            $j = 0;
            $entries[$account][$extension]['numbers'] = array();
            if (empty($res[$i]['telephonenumber']['count'])) {
                $res[$i]['telephonenumber']['count'] = -1;
            }
            while ($j < $res[$i]['telephonenumber']['count']) {
                $entries[$account][$extension]['numbers'][] =
                    $res[$i]['telephonenumber'][$j];
                $j++;
            }

            $j = 0;
            $entries[$account][$extension]['devices'] = array();
            if (empty($res[$i]['astuserchannel']['count'])) {
                $res[$i]['astuserchannel']['count'] = -1;
            }
            while ($j < $res[$i]['astuserchannel']['count']) {
                // Trim off the Asterisk channel type from the device string
                $device = explode('/', $res[$i]['astuserchannel'][$j], 2);
                $entries[$account][$extension]['devices'][] = $device[1];
                $j++;
            }


            $i++;

        }

        ksort($entries[$account]);

        return($entries[$account]);
    }

    /**
     * Add a new destination valid for this extension.
     * A destination is either a telephone number or a VoIP device.
     *
     * @param string $account      Account for the extension
     * @param string $extension    Extension for which to return destinations
     * @param string $type         Destination type ("device" or "number")
     * @param string $destination  The destination itself
     *
     * @return boolean  True on success.
     */
    function addDestination($account, $extension, $type, $destination)
    {
        // FIXME: Permissions check
        $dn = $this->_getExtensionDn($account, $extension);
        $attr = $this->_getDestAttr($type, $destination);

        $res = ldap_mod_add($this->_LDAP, $dn, $attr);
        if ($res === false) {
            $msg = sprintf('Error while modifying the LDAP entry.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Internal error modifing the directory.  Details have been logged for the administrator."));
        }

        return true;
    }

    /**
     * Get a list of destinations valid for this extension.
     * A destination is either a telephone number or a VoIP device.
     *
     * @param string $account    Account for the extension
     * @param string $extension  Extension for which to return destinations
     */
    function getDestinations($account, $extension)
    {
        // FIXME: LDAP filter injection
        $filter = '(&(AstContext=%s)(AstVoicemailMailbox=%s))';
        $filter = sprintf($filter, $account, $extension);

        $attrs = array('telephoneNumber', 'AstUserChannel');

        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
                           $filter, $attrs);

        if ($res === false) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Internal error searching the directory."));
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        if ($res === false) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Internal error searching the directory."));
        }

        if ($res['count'] != 1) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Wrong number of entries found for this search."));
        }

        return array('numbers' => $res['telephonenumbers'],
                     'devices' => $res['astuserchannel']);
    }

    function deleteDestination($account, $extension, $type, $destination)
    {
        $dn = $this->_getExtensionDn($account, $extension);
        $attr = $this->_getDestAttr($type, $destination);

        $res = ldap_mod_del($this->_LDAP, $dn, $attr);
        if ($res === false) {
            $msg = sprintf('Error while modifying the LDAP entry.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Internal error modifing the directory.  Details have been logged for the administrator."));
        }

        return true;
    }

    protected function _getDestAttr($type, $destination)
    {
        switch ($type) {
        case 'number':
            // FIXME: Strip this number down to just digits
            // FIXME: Add check for non-international numbers?
            $attr = array('telephoneNumber' => $destination);
            break;

        case 'device':
            // FIXME: Check that the device is valid and associated with this
            // account.
            // FIXME: Allow for different device types
            $attr = array('AstUserChannel' => "SIP/" . $destination);
            break;

        default:
            throw new Shout_Exception(_("Invalid destination type specified."));
            break;
        }

        return $attr;
    }

    /**
     * Save an extension to the LDAP tree
     *
     * @param string $account Account to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveExtension($account, $extension, $details)
    {
        // Check permissions
        parent::saveExtension($account, $extension, $details);

        // FIXME: Fix and uncomment the below
//        // Check to ensure the extension is unique within this account
//        $filter = "(&(objectClass=AstVoicemailMailbox)(context=$account))";
//        $reqattrs = array('dn', $ldapKey);
//        $res = @ldap_search($this->_LDAP, $this->_params['basedn'],
//                            $filter, $reqattrs);
//        if ($res === false) {
//            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
//                                                  ldap_error($this->_LDAP));
//            Horde::logMessage($msg, 'ERR');
//            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
//        }
//        if (($res['count'] != 1) ||
//            ($res['count'] != 0 &&
//            !in_array($res[0][$ldapKey], $details[$appKey]))) {
//            throw new Shout_Exception(_("Duplicate extension found.  Not saving changes."));
//        }
        // FIXME: Quote these strings
        $uid = $extension . '@' . $account;
        $entry = array(
            'objectClass' => array('top', 'account',
                                   'AsteriskVoicemail', 'AsteriskUser'),
            'uid' => $uid,
            'cn' => $details['name'],
            'AstVoicemailEmail' => $details['email'],
            'AstVoicemailMailbox' => $extension,
            'AstVoicemailPassword' => $details['mailboxpin'],
            'AstContext' => $account,
        );
        $rdn = 'uid=' . $uid;
        $dn = $rdn . ',' . $this->_params['basedn'];

        if (!empty($details['oldextension'])) {
            // This is a change to an existing extension
            // First, identify the DN to modify
            // FIXME: Quote these strings
            $olddn = $this->_getExtensionDn($account, $extension);

            // If the extension has changed we need to perform an object rename
            if ($extension != $details['oldextension']) {
                $res = ldap_rename($this->_LDAP, $olddn, $rdn,
                                   $this->_params['basedn'], true);

                if ($res === false) {
                    $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                    Horde::logMessage($msg, 'ERR');
                    throw new Shout_Exception(_("Error while modifying the directory.  Details have been logged for the administrator."));
                }
            }

            // Now apply the changes
            // Avoid changing the objectClass, just in case
            unset($entry['objectClass']);
            $res = ldap_modify($this->_LDAP, $dn, $entry);
            if ($res === false) {
                $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                Horde::logMessage($msg, 'ERR');
                throw new Shout_Exception(_("Error while modifying the directory.  Details have been logged for the administrator."));
            }

            return true;
        } else {
            // This is an add of a new extension
            $res = ldap_add($this->_LDAP, $dn, $entry);
            if ($res === false) {
                $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                Horde::logMessage($msg, 'ERR');
                throw new Shout_Exception(_("Error while modifying the directory.  Details have been logged for the administrator."));
            }
            return true;
        }

        // Catch-all.  We should not get here.
        throw new Shout_Exception(_("Unspecified error."));
    }

    /**
     * Deletes an extension from the LDAP tree
     *
     * @param string $account Account to delete the user from
     * @param string $extension Extension of the user to be deleted
     *
     * @return boolean True on success, PEAR::Error object on error
     */
    public function deleteExtension($account, $extension)
    {
        // Check permissions
        parent::deleteExtension($account, $extension);

        $dn = $this->_getExtensionDn($account, $extension);

        $res = @ldap_delete($this->_LDAP, $dn);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Horde_Exception(_("Error while deleting from the directory.  Details have been logged for the administrator."));
        }

        return true;
    }

    /**
     *
     * @param <type> $account
     * @param <type> $extension
     */
    protected function _getExtensionDn($account, $extension)
    {
        // FIXME: Sanitize filter string against LDAP injection
        $filter = '(&(AstVoicemailMailbox=%s)(AstContext=%s))';
        $filter = sprintf($filter, $extension, $account);
        $attributes = array('dn');

        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
                           $filter, $attributes);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
        }

        if (ldap_count_entries($this->_LDAP, $res) < 1) {
            throw new Shout_Exception(_("No such extension found."));
        }

        $res = ldap_first_entry($this->_LDAP, $res);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
        }

        $dn = ldap_get_dn($this->_LDAP, $res);
        if ($dn === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, 'ERR');
            throw new Shout_Exception(_("Internal LDAP error.  Details have been logged for the administrator."));
        }

        return $dn;
    }

    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @return boolean    True on success.
     * @throws Shout_Exception
     *
     * @access private
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        if (!Horde_Util::extensionExists('ldap')) {
            throw new Shout_Exception('Required LDAP extension not found.');
        }

        Horde::assertDriverConfig($this->_params, $this->_params['class'],
            array('hostspec', 'basedn', 'writedn'));

        /* Open an unbound connection to the LDAP server */
        $conn = ldap_connect($this->_params['hostspec'], $this->_params['port']);
        if (!$conn) {
             Horde::logMessage(
                sprintf('Failed to open an LDAP connection to %s.',
                        $this->_params['hostspec']), 'ERR');
            throw new Shout_Exception('Internal LDAP error. Details have been logged for the administrator.');
        }

        /* Set hte LDAP protocol version. */
        if (isset($this->_params['version'])) {
            $result = ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION,
                                       $this->_params['version']);
            if ($result === false) {
                Horde::logMessage(
                    sprintf('Set LDAP protocol version to %d failed: [%d] %s',
                            $this->_params['version'],
                            ldap_errno($conn),
                            ldap_error($conn)), 'WARN');
                throw new Shout_Exception('Internal LDAP error. Details have been logged for the administrator.', ldap_errno($conn));
            }
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            if (!@ldap_start_tls($conn)) {
                Horde::logMessage(
                    sprintf('STARTTLS failed: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)), 'ERR');
            }
        }

        /* If necessary, bind to the LDAP server as the user with search
         * permissions. */
        if (!empty($this->_params['searchdn'])) {
            $bind = ldap_bind($conn, $this->_params['searchdn'],
                              $this->_params['searchpw']);
            if ($bind === false) {
                Horde::logMessage(
                    sprintf('Bind to server %s:%d with DN %s failed: [%d] %s',
                            $this->_params['hostspec'],
                            $this->_params['port'],
                            $this->_params['searchdn'],
                            @ldap_errno($conn),
                            @ldap_error($conn)), 'ERR');
                throw new Shout_Exception('Internal LDAP error. Details have been logged for the administrator.', ldap_errno($conn));
            }
        }

        /* Store the connection handle at the instance level. */
        $this->_LDAP = $conn;
    }

}
