<?php

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
     * Get a list of users valid for the contexts
     *
     * @param string $context  Context in which to search
     *
     * @return array User information indexed by voice mailbox number
     */
    public function getExtensions($context)
    {

        static $entries = array();
        if (isset($entries[$context])) {
            return $entries[$context];
        }

        $this->_params['basedn'];

        $filter  = '(&';
        $filter .= '(objectClass=AsteriskVoiceMail)';
        $filter .= '(AstContext='.$context.')';
        $filter .= ')';

        $attributes = array(
            'cn',
            'AstVoicemailEmail',
            'AstVoicemailMailbox',
            'AstVoicemailPassword',
            'AstVoicemailOptions',
            'AstVoicemailPager',
            'telephoneNumber',
            'AstExtension'
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
        $entries[$context] = array();
        $i = 0;
        while ($i < $res['count']) {
            list($extension) = explode('@', $res[$i]['astvoicemailmailbox'][0]);
            $entries[$context][$extension] = array('extension' => $extension);

            $j = 0;
            $entries[$context][$extension]['mailboxopts'] = array();
            if (empty($res[$i]['astvoicemailoptions']['count'])) {
                $res[$i]['astvoicemailoptions']['count'] = -1;
            }
            while ($j < $res[$i]['astvoicemailoptions']['count']) {
                $entries[$context][$extension]['mailboxopts'][] =
                    $res[$i]['astvoicemailoptions'][$j];
                $j++;
            }

            $entries[$context][$extension]['mailboxpin'] =
                $res[$i]['astvoicemailpassword'][0];

            $entries[$context][$extension]['name'] =
                $res[$i]['cn'][0];

            $entries[$context][$extension]['email'] =
                $res[$i]['astvoicemailemail'][0];

            $entries[$context][$extension]['pageremail'] =
                $res[$i]['astvoicemailpager'][0];

            $j = 0;
            $entries[$context][$extension]['numbers'] = array();
            if (empty($res[$i]['telephonenumber']['count'])) {
                $res[$i]['telephonenumber']['count'] = -1;
            }
            while ($j < $res[$i]['telephonenumber']['count']) {
                $entries[$context][$extension]['numbers'][] =
                    $res[$i]['telephonenumber'][$j];
                $j++;
            }

            $j = 0;
            $entries[$context][$extension]['devices'] = array();
            if (empty($res[$i]['astextension']['count'])) {
                $res[$i]['astextension']['count'] = -1;
            }
            while ($j < $res[$i]['astextension']['count']) {
                $entries[$context][$extension]['devices'][] =
                    $res[$i]['astextension'][$j];
                $j++;
            }


            $i++;

        }

        ksort($entries[$context]);

        return($entries[$context]);
    }

    /**
     * Get a list of destinations valid for this extension.
     * A destination is either a telephone number, a VoIP device or an
     * Instant Messaging address (a special case of VoIP).
     *
     * @param string $context    Context for the extension
     * @param string $extension  Extension for which to return destinations
     */
    function getDestinations($context, $extension)
    {
        // FIXME: LDAP filter injection
        $filter = '(&(AstContext=%s)(AstVoicemailMailbox=%s))';
        $filter = sprintf($filter, $context, $extension);

        $attrs = array('telephoneNumber', 'AstExtensions');

        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
                           $filter, $attrs);

        if ($res === false) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Internal error searching the directory."));
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        if ($res === false) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Internal error searching the directory."));
        }

        if ($res['count'] != 1) {
            $msg = sprintf('Error while searching LDAP.  Code %s; Message "%s"',
                           ldap_errno($this->_LDAP), ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Wrong number of entries found for this search."));
        }

        return array('numbers' => $res['telephonenumbers'],
                     'devices' => $res['astextensions']);
    }

    /**
     * Save an extension to the LDAP tree
     *
     * @param string $context Context to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveExtension($context, $extension, $details)
    {
        // Check permissions
        parent::saveExtension($context, $extension, $details);

        // FIXME: Fix and uncomment the below
//        // Check to ensure the extension is unique within this context
//        $filter = "(&(objectClass=AstVoicemailMailbox)(context=$context))";
//        $reqattrs = array('dn', $ldapKey);
//        $res = @ldap_search($this->_LDAP, $this->_params['basedn'],
//                            $filter, $reqattrs);
//        if ($res === false) {
//            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
//                                                  ldap_error($this->_LDAP));
//            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
//            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
//        }
//        if (($res['count'] != 1) ||
//            ($res['count'] != 0 &&
//            !in_array($res[0][$ldapKey], $details[$appKey]))) {
//            throw new Shout_Exception(_("Duplicate extension found.  Not saving changes."));
//        }
        // FIXME: Quote these strings
        $uid = $extension . '@' . $context;
        $entry = array(
            'objectClass' => array('top', 'account', 'AsteriskVoicemail'),
            'uid' => $uid,
            'cn' => $details['name'],
            'AstVoicemailEmail' => $details['email'],
            'AstVoicemailMailbox' => $extension,
            'AstVoicemailPassword' => $details['mailboxpin'],
            'AstContext' => $context,
        );
        $rdn = 'uid=' . $uid;
        $dn = $rdn . ',' . $this->_params['basedn'];

        if (!empty($details['oldextension'])) {
            // This is a change to an existing extension
            // First, identify the DN to modify
            // FIXME: Quote these strings
            $olddn = $this->_getExtensionDn($context, $extension);

            // If the extension has changed we need to perform an object rename
            if ($extension != $details['oldextension']) {
                $res = ldap_rename($this->_LDAP, $olddn, $rdn,
                                   $this->_params['basedn'], true);

                if ($res === false) {
                    $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                    Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
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
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Shout_Exception(_("Error while modifying the directory.  Details have been logged for the administrator."));
            }

            return true;
        } else {
            // This is an add of a new extension
            $res = ldap_add($this->_LDAP, $dn, $entry);
            if ($res === false) {
                $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
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
     * @param string $context Context to delete the user from
     * @param string $extension Extension of the user to be deleted
     *
     * @return boolean True on success, PEAR::Error object on error
     */
    public function deleteExtension($context, $extension)
    {
        // Check permissions
        parent::deleteExtension($context, $extension);

        $dn = $this->_getExtensionDn($context, $extension);

        $res = @ldap_delete($this->_LDAP, $dn);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception(_("Error while deleting from the directory.  Details have been logged for the administrator."));
        }
        
        return true;
    }

    /**
     *
     * @param <type> $context
     * @param <type> $extension 
     */
    protected function _getExtensionDn($context, $extension)
    {
        // FIXME: Sanitize filter string against LDAP injection
        $filter = '(&(AstVoicemailMailbox=%s)(AstContext=%s))';
        $filter = sprintf($filter, $extension, $context);
        $attributes = array('dn');

        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
                           $filter, $attributes);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
        }

        if (ldap_count_entries($this->_LDAP, $res) < 1) {
            throw new Shout_Exception(_("No such extension found."));
        }

        $res = ldap_first_entry($this->_LDAP, $res);
        if ($res === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
        }

        $dn = ldap_get_dn($this->_LDAP, $res);
        if ($dn === false) {
            $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                  ldap_error($this->_LDAP));
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Shout_Exception(_("Internal LDAP error.  Details have been logged for the administrator."));
        }

        return $dn;
    }

    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @return boolean    True on success; exits (Horde::fatal()) on error.
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
                        $this->_params['hostspec']),
                __FILE__, __LINE__, PEAR_LOG_ERR);
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
                            ldap_error($conn)),
                    __FILE__, __LINE__, PEAR_LOG_WARNING);
                throw new Shout_Exception('Internal LDAP error. Details have been logged for the administrator.', ldap_errno($conn));
            }
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            if (!@ldap_start_tls($conn)) {
                Horde::logMessage(
                    sprintf('STARTTLS failed: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)),
                    __FILE__, __LINE__, PEAR_LOG_ERR);
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
                            @ldap_error($conn)),
                    __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Shout_Exception('Internal LDAP error. Details have been logged for the administrator.', ldap_errno($conn));
            }
        }

        /* Store the connection handle at the instance level. */
        $this->_LDAP = $conn;
    }

}
