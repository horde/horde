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
    * Get a list of contexts from the backend and filter for which contexts
    * the current user can read/write
    *
    * @return array Contexts valid for this user
    *
    * @access public
    */
    function getContexts()
    {
        # Collect all the possible contexts from the backend
        $res = ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            '(&(objectClass=asteriskObject)(objectClass=vofficeCustomer))',
            array('context'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any customers " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn']) .
            "matching those search filters";
        }
        # Get the list of valid contexts for this user
        # Possibly create the idea of an Asterisk Global Admin in the
        # permissions system where an arbitrary user has permissions in all
        # contexts


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


    
    // {{{ 
    function getUserPhoneNumbers($username, $context = null)
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