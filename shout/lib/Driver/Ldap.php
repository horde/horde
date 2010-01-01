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

        /* These next lines will translate between indexes used in the
         * application and LDAP.  The rationale is that translation here will
         * help make Congregation more driver-independant.  The keys used to
         * contruct user arrays should be more appropriate to human-legibility
         * (name instead of 'cn' and email instead of 'mail').  This translation
         * is only needed because LDAP indexes users based on an arbitrary
         * attribute and the application indexes by extension/context.  In my
         * environment users are indexed by their 'mail' attribute and others
         * may index based on 'cn' or 'uid'.  Any time a new $prefs['uid'] needs
         * to be supported, this function should be checked and possibly
         * extended to handle that translation.
         */
        switch($this->_params['uid']) {
        case 'cn':
            $this->_ldapKey = 'cn';
            $this->_appKey = 'name';
            break;
        case 'mail':
            $this->_ldapKey = 'mail';
            $this->_appKey = 'email';
            break;
        case 'uid':
            # FIXME Probably a better app key to map here
            # There is no value that maps uid to LDAP so we can choose to use
            # either extension or name, or anything really.  I want to
            # support it since it's a very common DN attribute.
            # Since it's entirely administrator's preference, I'll
            # set it to name for now
            $this->_ldapKey = 'uid';
            $this->_appKey = 'name';
            break;
        case 'voiceMailbox':
            $this->_ldapKey = 'voiceMailbox';
            $this->_appKey = 'extension';
            break;
        }
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
     * Get a context's dialplan and return as a multi-dimensional associative
     * array
     *
     * @param string $context Context to return extensions for
     *
     * @param boolean $preprocess Parse includes and barelines and add their
     *                            information into the extensions array
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    public function getDialplan($context, $preprocess = false)
    {
        # FIXME Implement preprocess functionality.  Don't forget to cache!
        static $dialplans = array();
        if (isset($dialplans[$context])) {
            return $dialplans[$context];
        }

        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=".SHOUT_CONTEXT_EXTENSIONS_OBJECTCLASS.")(context=$context))",
            array(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE, SHOUT_DIALPLAN_INCLUDE_ATTRIBUTE,
                SHOUT_DIALPLAN_IGNOREPAT_ATTRIBUTE, 'description',
                SHOUT_DIALPLAN_BARELINE_ATTRIBUTE));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any extensions " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        $dialplans[$context] = array();
        $dialplans[$context]['name'] = $context;
        $i = 0;
        while ($i < $res['count']) {
            # Handle extension lines
            if (isset($res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)])) {
                $j = 0;
                while ($j < $res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)]['count']) {
                    @$line = $res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)][$j];

                    # Basic sanity check for length.  FIXME
                    if (strlen($line) < 5) {
                        break;
                    }
                    # Can't use strtok here because there may be commass in the
                    # arg string

                    # Get the extension
                    $token1 = strpos($line, ',');
                    $token2 = strpos($line, ',', $token1 + 1);
                    $token3 = strpos($line, '(', $token2 + 1);

                    $extension = substr($line, 0, $token1);
                    if (!isset($dialplans[$context]['extensions'][$extension])) {
                        $dialplan[$context]['extensions'][$extension] = array();
                    }
                    $token1++;
                    # Get the priority
                    $priority = substr($line, $token1, $token2 - $token1);
                    $dialplans[$context]['extensions'][$extension][$priority] =
                        array();
                    $token2++;

                    # Get Application and args
                    $application = substr($line, $token2, $token3 - $token2);

                    if ($token3) {
                        $application = substr($line, $token2, $token3 - $token2);
                        $args = substr($line, $token3);
                        $args = preg_replace('/^\(/', '', $args);
                        $args = preg_replace('/\)$/', '', $args);
                    } else {
                        # This application must not have any args
                        $application = substr($line, $token2);
                        $args = '';
                    }

                    # Merge all that data into the returning array
                    $dialplans[$context]['extensions'][$extension][$priority]['application'] =
                        $application;
                    $dialplans[$context]['extensions'][$extension][$priority]['args'] =
                        $args;
                    $j++;
                }

                # Sort the extensions data
                foreach ($dialplans[$context]['extensions'] as
                    $extension => $data) {
                    ksort($dialplans[$context]['extensions'][$extension]);
                }
                uksort($dialplans[$context]['extensions'],
                    array(new Shout, "extensort"));
            }
            # Handle include lines
            if (isset($res[$i]['asteriskincludeline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskincludeline']['count']) {
                    @$line = $res[$i]['asteriskincludeline'][$j];
                    $dialplans[$context]['includes'][$j] = $line;
                    $j++;
                }
            }

            # Handle ignorepat
            if (isset($res[$i]['asteriskignorepat'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskignorepat']['count']) {
                    @$line = $res[$i]['asteriskignorepat'][$j];
                    $dialplans[$context]['ignorepats'][$j] = $line;
                    $j++;
                }
            }
            # Handle ignorepat
            if (isset($res[$i]['asteriskextensionbareline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskextensionbareline']['count']) {
                    @$line = $res[$i]['asteriskextensionbareline'][$j];
                    $dialplans[$context]['barelines'][$j] = $line;
                    $j++;
                }
            }

            # Increment object
            $i++;
        }
        return $dialplans[$context];
    }

    /**
     * Get the limits for the current user, the user's context, and global
     * Return the most specific values in every case.  Return default values
     * where no data is found.  If $extension is specified, $context must
     * also be specified.
     *
     * @param optional string $context Context to search
     *
     * @param optional string $extension Extension/user to search
     *
     * @return array Array with elements indicating various limits
     */
     # FIXME Figure out how this fits into Shout/Congregation better
    public function getLimits($context = null, $extension = null)
    {

        $limits = array('telephonenumbersmax',
                        'voicemailboxesmax',
                        'asteriskusersmax');

        if(!is_null($extension) && is_null($context)) {
            return PEAR::raiseError("Extension specified but no context " .
                "given.");
        }

        if (!is_null($context) && isset($limits[$context])) {
            if (!is_null($extension) &&
                isset($limits[$context][$extension])) {
                return $limits[$context][$extension];
            }
            return $limits[$context];
        }

        # Set some default limits (to unlimited)
        static $cachedlimits = array();
        # Initialize the limits with defaults
        if (count($cachedlimits) < 1) {
            foreach ($limits as $limit) {
                $cachedlimits[$limit] = 99999;
            }
        }

        # Collect the global limits
        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            '(&(objectClass=asteriskLimits)(cn=globals))',
            $limits);

        if (!$res) {
            return PEAR::raiseError('Unable to search the LDAP server for ' .
                'global limits');
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        # There should only have been one object returned so we'll just take the
        # first result returned
        if ($res['count'] > 0) {
            foreach ($limits as $limit) {
                if (isset($res[0][$limit][0])) {
                    $cachedlimits[$limit] = $res[0][$limit][0];
                }
            }
        } else {
            return PEAR::raiseError("No global object found.");
        }

        # Get limits for the context, if provided
        if (isset($context)) {
            $res = ldap_search($this->_LDAP,
                SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
                "(&(objectClass=asteriskLimits)(cn=$context))");

            if (!$res) {
                return PEAR::raiseError('Unable to search the LDAP server ' .
                    "for $context specific limits");
            }

            $cachedlimits[$context][$extension] = array();
            if ($res['count'] > 0) {
                foreach ($limits as $limit) {
                    if (isset($res[0][$limit][0])) {
                        $cachedlimits[$context][$limit] = $res[0][$limit][0];
                    } else {
                        # If no value is provided use the global limit
                        $cachedlimits[$context][$limit] = $cachedlimits[$limit];
                    }
                }
            } else {

                foreach ($limits as $limit) {
                    $cachedlimits[$context][$limit] =
                        $cachedlimits[$limit];
                }
            }

            if (isset($extension)) {
                $res = @ldap_search($this->_LDAP,
                    SHOUT_USERS_BRANCH.','.$this->_params['basedn'],
                    "(&(objectClass=asteriskLimits)(voiceMailbox=$extension)".
                    "(context=$context))");

                if (!$res) {
                    return PEAR::raiseError('Unable to search the LDAP server '.
                        "for Extension $extension, $context specific limits");
                }

                $cachedlimits[$context][$extension] = array();
                if ($res['count'] > 0) {
                    foreach ($limits as $limit) {
                        if (isset($res[0][$limit][0])) {
                            $cachedlimits[$context][$extension][$limit] =
                                $res[0][$limit][0];
                        } else {
                            # If no value is provided use the context limit
                            $cachedlimits[$context][$extension][$limit] =
                                $cachedlimits[$context][$limit];
                        }
                    }
                } else {
                    foreach ($limits as $limit) {
                        $cachedlimits[$context][$extension][$limit] =
                            $cachedlimits[$context][$limit];
                    }
                }
                return $cachedlimits[$context][$extension];
            }
            return $cachedlimits[$context];
        }
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
            $filter = '(&(AstVoicemailMailbox=%s)(AstContext=%s))';
            $filter = sprintf($filter, $details['oldextension'], $context);
            $attributes = array('dn');

            $res = ldap_search($this->_LDAP, $this->_params['basedn'],
                               $filter, $attributes);
            if ($res === false) {
                $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Shout_Exception(_("Error while searching the directory.  Details have been logged for the administrator."));
            }

            // If the extension has changed we need to perform an object rename
            if ($extension != $details['oldextension']) {
                $res = ldap_rename($this->_LDAP, $res['dn'], $rdn,
                                   $this->_params['basedn'], true);

                if ($res === false) {
                $msg = sprintf('LDAP Error (%s): %s', ldap_errno($this->_LDAP),
                                                      ldap_error($this->_LDAP));
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Shout_Exception(_("Error while modifying the directory.  Details have been logged for the administrator."));
                }
            }

            // Now apply the changes
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

        throw new Shout_Exception(_("Unspecified error."));
    }

    /**
     * Deletes a user from the LDAP tree
     *
     * @param string $context Context to delete the user from
     * @param string $extension Extension of the user to be deleted
     *
     * @return boolean True on success, PEAR::Error object on error
     */
    public function deleteUser($context, $extension)
    {
        $ldapKey = &$this->_ldapKey;
        $appKey = &$this->_appKey;

        if (!Shout::checkRights("shout:contexts:$context:users",
            PERMS_DELETE, 1)) {
            return PEAR::raiseError("No permission to delete users in this " .
                "context.");
        }

        $validusers = $this->getUsers($context);
        if (!isset($validusers[$extension])) {
            return PEAR::raiseError("That extension does not exist.");
        }

        $dn = "$ldapKey=".$validusers[$extension][$appKey];
        $dn .= ',' . SHOUT_USERS_BRANCH . ',' . $this->_params['basedn'];

        $res = @ldap_delete($this->_LDAP, $dn);
        if (!$res) {
            return PEAR::raiseError("Unable to delete $extension from " .
                "$context: " . ldap_error($this->_LDAP));
        }
        return true;
    }


    /* Needed because uksort can't take a classed function as its callback arg */
    protected function _sortexten($e1, $e2)
    {
        print "$e1 and $e2\n";
        $ret =  Shout::extensort($e1, $e2);
        print "returning $ret";
        return $ret;
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
