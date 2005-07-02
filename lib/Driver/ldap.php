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
    function _getContexts($filter = "all")
    {
        # TODO Add caching mechanism here.  Possibly cache results per
        # filter $this->contexts['customer'] and return either data
        # or possibly a reference to that data
        switch ($filter) {
            case "customer":
                $searchfilter="(objectClass=vofficeCustomer)";
                break;
            case "extensions":
                $searchfilter="(objectClass=asteriskExtensions)";
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

        # Collect all the possible contexts from the backend
        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)$searchfilter)",
            array('context'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any customers " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $entries = array();
        $res = ldap_get_entries($this->_LDAP, $res);
        $i = 0;
        while ($i < $res['count']) {
            $entries[] = $res[$i]['context'][0];
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
    function _checkContextType($context, $type) {
        switch ($type) {
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
        print_r($res);
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
    function _getUsers($context)
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