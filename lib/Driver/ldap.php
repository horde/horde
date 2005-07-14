<?php

// {{{ Shout_Driver_ldap class
class Shout_Driver_ldap extends Shout_Driver
{

    // {{{ Class local variables
    /**
     * Handle for the current database connection.
     * @var object LDAP $_LDAP
     */
    var $_LDAP;

    /**
     * Boolean indicating whether or not we're connected to the LDAP
     * server.
     * @var boolean $_connected
     */
    var $_connected = false;
    // }}}

    // {{{ Shout_Driver_ldap constructor
    /**
    * Constructs a new Shout LDAP driver object.
    *
    * @param array  $params    A hash containing connection parameters.
    */
    function Shout_Driver_ldap($params = array())
    {
        parent::Shout_Driver($params);
        $this->_connect();
    }
    // }}}

    // {{{ getContexts method
    /**
    * Get a list of contexts from the backend
    *
    * @param string $filter Search filter
    *
    * @return array Contexts valid for this system
    *
    * @access private
    */
    function getContexts($searchfilters = SHOUT_CONTEXT_ALL,
                         $filterperms = null)
    {
        if ($filterperms == null) {
            $filterperms = PERMS_SHOW|PERMS_READ;
        }

        # TODO Add caching mechanism here.  Possibly cache results per
        # filter $this->contexts['customer'] and return either data
        # or possibly a reference to that data

        # Determine which combination of contexts need to be returned
        if ($searchfilters == SHOUT_CONTEXT_ALL) {
            $searchfilter="(objectClass=asteriskObject)";
        } else {
            $searchfilter = "(|";
            if ($searchfilters & SHOUT_CONTEXT_CUSTOMERS) {
                $searchfilter.="(objectClass=vofficeCustomer)";
            } else {
                $searchfilter.="(!(objectClass=vofficeCustomer))";
            }

            if ($searchfilters & SHOUT_CONTEXT_EXTENSIONS) {
                $searchfilter.="(objectClass=asteriskExtensions)";
            } else {
                $searchfilter.="(!(objectClass=asteriskExtensions))";
            }

            if ($searchfilters & SHOUT_CONTEXT_MOH) {
                $searchfilter.="(objectClass=asteriskMusicOnHold)";
            } else {
                $searchfilter.="(!(objectClass=asteriskMusicOnHold))";
            }

            if ($searchfilters & SHOUT_CONTEXT_CONFERENCE) {
                $searchfilter.="(objectClass=asteriskMeetMe)";
            } else {
                $searchfilter.="(!(objectClass=asteriskMeetMe))";
            }
            $searchfilter .= ")";
        }


        # Collect all the possible contexts from the backend
        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)$searchfilter)",
            array('context'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any contexts " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $entries = array();
        $res = ldap_get_entries($this->_LDAP, $res);
        $i = 0;
        while ($i < $res['count']) {
            $context = $res[$i]['context'][0];
            if (Shout::checkRights("shout:contexts:$context", $filterperms)) {
                $entries[] = $context;
            }
            $i++;
        }
        # return the array
        return $entries;
    }
    // }}}

    // {{{ _checkContextType method
    /**
     * For the given context and type, make sure the context has the
     * appropriate properties, that it is effectively of that "type"
     *
     * @param string $context the context to check type for
     *
     * @param string $type the type to verify the context is of
     *
     * @return boolean true of the context is of type, false if not
     *
     * @access public
     */
    function checkContextType($context, $type) {
        switch ($type) {
            case "users":
                $searchfilter = "(objectClass=vofficeCustomer)";
                break;
            case "dialplan":
                $searchfilter = "(objectClass=asteriskExtensions)";
                break;
            case "moh":
                $searchfilter="(objectClass=asteriskMusicOnHold)";
                break;
            case "conference":
                $searchfilter="(objectClass=asteriskMeetMe)";
                break;
            case "all":
            default:
                $searchfilter="";
                break;
        }

        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)$searchfilter(context=$context))",
            array("context"));
        if (!$res) {
            return PEAR::raiseError("Unable to search directory for context
type");
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        if (!$res) {
            return PEAR::raiseError("Unable to get results from LDAP query");
        }

        if ($res['count'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ _getUsers method
    /**
     * Get a list of users valid for the contexts
     *
     * @param string $context Context on which to search
     *
     * @return array User information indexed by voice mailbox number
     */
    function getUsers($context)
    {
        $search = ldap_search($this->_LDAP,
            SHOUT_USERS_BRANCH.','.$this->_params['basedn'],
            '(&(objectClass='.SHOUT_USER_OBJECTCLASS.')(context='.$context.'))',
            array('voiceMailbox', 'asteriskUserDialOptions',
                'asteriskVoiceMailboxOptions', 'voiceMailboxPin',
                'cn', 'telephoneNumber',
                'asteriskUserDialTimeout', 'mail', 'asteriskPager'));
        if (!$search) {
            return PEAR::raiseError("Unable to search directory");
        }
        $res = ldap_get_entries($this->_LDAP, $search);
        $entries = array();
        $i = 0;
        while ($i < $res['count']) {
            $extension = $res[$i]['voicemailbox'][0];
            $entries[$extension] = array();

            @$entries[$extension]['dialopts'] =
                $res[$i]['asteriskuserdialoptions'];

            @$entries[$extension]['mailboxopts'] =
                $res[$i]['asteriskvoicemailboxoptions'];

            @$entries[$extension]['mailboxpin'] =
                $res[$i]['voicemailboxpin'][0];

            @$entries[$extension]['name'] =
                $res[$i]['cn'][0];

            @$entries[$extension]['phonenumbers'] =
                $res[$i]['telephonenumber'];

            @$entries[$extension]['dialtimeout'] =
                $res[$i]['asteriskuserdialtimeout'][0];

            @$entries[$extension]['email'] =
                $res[$i]['mail'][0];

            @$entries[$extension]['pageremail'] =
                $res[$i]['asteriskpager'][0];

            $i++;
        }

        return $entries;
    }
    // }}}

    // {{{ _getHomeContext method
    /**
     * Returns the name of the user's default context
     *
     * @return string User's default context
     */
    function getHomeContext()
    {
        $res = ldap_search($this->_LDAP,
            SHOUT_USERS_BRANCH.','.$this->_params['basedn'],
            "(&(mail=".Auth::getAuth().")(objectClass=asteriskUser))",
            array('context'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any customers " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        # Assume the user only has one context.  The schema encforces this
        return $res[0]['context'][0];
    }
    // }}}

    // {{{
    /**
     * Get a context's properties
     *
     * @param string $context Context to get properties for
     *
     * @return integer Bitfield of properties valid for this context
     */
    function getContextProperties($context)
    {

        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)(context=$context))",
            array('objectClass'));
        if(!$res) {
            return PEAR::raiseError(_("Unable to get properties for $context"));
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        $properties = 0;
        if ($res['count'] != 1) {
            return PEAR::raiseError(_("Incorrect number of properties found
for $context"));
        }

        foreach ($res[0]['objectclass'] as $objectClass) {
            switch ($objectClass) {
                case "vofficeCustomer":
                    $properties = $properties | SHOUT_CONTEXT_CUSTOMERS;
                    break;

                case "asteriskExtensions":
                    $properties = $properties | SHOUT_CONTEXT_EXTENSIONS;
                    break;

                case "asteriskMusicOnHold":
                    $properties = $properties | SHOUT_CONTEXT_MOH;
                    break;

                case "asteriskMeetMe":
                    $properties = $properties | SHOUT_CONTEXT_CONFERENCE;
                    break;
            }
        }
        return $properties;
    }
    // }}}

    // {{{ _getDialplan method
    /**
     * Get a context's dialplan and return as a multi-dimensional associative
     * array
     *
     * @param string $context Context to return extensions for
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    function getDialplan($context)
    {
        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskExtensions)(context=$context))",
            array('asteriskExtensionLine', 'asteriskIncludeLine',
                'asteriskIgnorePat', 'description',
                'asteriskExtensionBareLine'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any extensions " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        $retdialplan = array();
        $i = 0;
        while ($i < $res['count']) {
            # Handle extension lines
            if (isset($res[$i]['asteriskextensionline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskextensionline']['count']) {
                    @$line = $res[$i]['asteriskextensionline'][$j];

                    # Basic sanity check for length.  FIXME
                    if (strlen($line) < 5) {
                        break;
                    }
                    # Can't use strtok here because there may be ','s in the arg
                    # string

                    # Get the extension
                    $token1 = strpos($line, ',');
                    $token2 = strpos($line, ',', $token1 + 1);

                    $extension = substr($line, 0, $token1);
                    if (!isset($retdialplan[$extension])) {
                        $retdialplan[$extension] = array();
                    }
                    $token1++;
                    # Get the priority
                    $priority = substr($line, $token1, $token2 - $token1);
                    $retdialplan[$extension][$priority] = array();
                    $token2++;

                    # Get Application and args
                    $application = substr($line, $token2);

                    # Merge all that data into the returning array
                    $retdialplan['extensions'][$extension][$priority] =
                        $application;
                    $j++;
                }

                # Sort the extensions data
                foreach ($retdialplan['extensions'] as $extension) {
                    ksort($extension);
                }
                ksort($retdialplan['extensions']);
            }
            # Handle include lines
            if (isset($res[$i]['asteriskincludeline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskincludeline']['count']) {
                    @$line = $res[$i]['asteriskincludeline'][$j];
                    $retdialplan['include'][$j] = $line;
                    $j++;
                }
            }

            # Handle ignorepat
            if (isset($res[$i]['asteriskignorepat'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskignorepat']['count']) {
                    @$line = $res[$i]['asteriskignorepat'][$j];
                    $retdialplan['include'][$j] = $line;
                    $j++;
                }
            }
            # Handle ignorepat
            if (isset($res[$i]['asteriskextensionbareline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskextensionbareline']['count']) {
                    @$line = $res[$i]['asteriskextensionbareline'][$j];
                    $retdialplan['bareline'][$j] = $line;
                    $j++;
                }
            }

            # Increment object
            $i++;
        }
        return $retdialplan;
    }
    // }}}

    // {{{ getUserPhoneNumbers method
    /**
     * Get a list of phone numbers for the given user from the backend
     *
     * @param string $extension Extension on which to search
     *
     * @param string $context Context for which this user is valid
     *
     * @return array Phone numbers for this user
     *
     * @access public
     */
    function getUserPhoneNumbers($extension, $context = null)
    {
        $userfilter = "(".$this->userkey."=".$username.",".
            $this->usersOU.",".$this->_params['basedn'].")";
        $searchfilter = "(&".$userfilter;
        foreach ($prefs["searchfilters"]["phoneuser"] as $filter) {
            $searchfilter .= "($filter)";
        }
        $searchfilter .= ")";

        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
$searchfilter,
            array("userNumber"));
        if (!res) {
            return PEAR::raiseError("Unable to locate any LDAP entries for
$searchfilter under ".$this->_params['basedn']);
        }
        // FIXME
    }

    // {{{ getUserVoicemailInfo method
    /**
     * Get the named user's voicemail particulars from LDAP
     *
     * @param string $extension Extension for which voicemail information should
     *                          be returned
     * @param optional string $context Context to which this extension belongs
     *
     * @return array Array containing voicemail options, user's name, email
     *               and pager addresses and PIN number
     *
     * @access public
     */
    function getUserVoicemailInfo($extension, $context = null)
    {
        $userfilter = "(&(objectClass=asteriskVoiceMailbox)(context=$context))";
        $res = ldap_search($this->_LDAP, $this->_params['basedn'],
$userfilter,
            array('asteriskVoiceMailboxOptions', 'mail', 'asteriskPager',
                'voiceMailboxPin', 'cn'));
        return $res;
    }
    // }}}

    // {{{ _connect method
    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @return boolean    True on success; exits (Horde::fatal()) on error.
     *
     * @access private
     */
    function _connect()
    {
        if (!$this->_connected) {
            # FIXME What else is needed for this assert?
            Horde::assertDriverConfig($this->_params, 'storage',
                array('hostspec', 'basedn', 'binddn', 'password'));

            # FIXME Add other sane defaults here (mostly objectClass related)
            if (!isset($this->_params['userObjectclass'])) {
                $this->_params['userObjectclass'] = 'asteriskUser';
            }

            $this->_LDAP = ldap_connect($this->_params['hostspec'], 389); #FIXME
            if (!$this->_LDAP) {
                Horde::fatal("Unable to connect to LDAP server $hostname on
$port", __FILE__, __LINE__); #FIXME: $port
            }
            $res = ldap_set_option($this->_LDAP, LDAP_OPT_PROTOCOL_VERSION,
$this->_params['version']);
            if (!$res) {
                return PEAR::raiseError("Unable to set LDAP protocol version");
            }
            $res = ldap_bind($this->_LDAP, $this->_params['binddn'],
$this->_params['password']);
            if (!$res) {
                return PEAR::raiseError("Unable to bind to the LDAP server.
Check authentication credentials.");
            }

            $this->_connected = true;
        }
        return true;
    }
    // }}}
}