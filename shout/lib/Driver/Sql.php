<?php

class Shout_Driver_Sql extends Shout_Driver
{
    /**
     * Handle for the current database connection.
     * @var object $_db
     */
    protected $_db = null;

    /**
     * Boolean indicating whether or not we're connected to the LDAP
     * server.
     * @var boolean $_connected
     */
    protected $_connected = false;


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

    public function getContexts()
    {
        $this->_connect();

        $sql = 'SELECT context FROM %s';
        $sql = sprintf($sql, $this->_params['table']);
        $vars = array();

        $result = $this->_db->query($sql, $vars);
        if ($result instanceof PEAR_Error) {
            throw Shout_Exception($result);
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            throw Shout_Exception($row);
        }

        $contexts = array();
        while ($row && !($row instanceof PEAR_Error)) {
            /* Add this new foo to the $_foo list. */
            $contexts[] = $row['context'];

            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

         $result->free();
         return $contexts;
    }

    /**
     * Get a list of devices for a given context
     *
     * @param string $context    Context in which to search for devicess
     *
     * @return array  Array of devices within this context with their information
     *
     * @access private
     */
    public function getDevices($context)
    {
        $sql = 'SELECT id, name, alias, callerid, context, mailbox, host, ' .
               'permit, nat, secret, disallow, allow ' .
               'FROM %s WHERE accountcode = ?';
        $sql = sprintf($sql, $this->_params['table']);
        $args = array($context);
        $result = $this->_db->query($sql, $args);
        if ($result instanceof PEAR_Error) {
            throw new Shout_Exception($result);
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            throw new Shout_Exception($row);
        }

        $devices = array();
        while ($row && !($row instanceof PEAR_Error)) {
            // Asterisk uses the "name" field to indicate the registration
            // identifier.  We use the field "alias" to put a friendly name on
            // the device.  Thus devid -> name and name => alias
            $devid = $row['name'];
            $row['devid'] = $devid;
            $row['name'] = $row['alias'];
            unset($row['alias']);

            // Trim off the context from the mailbox number
            list($row['mailbox']) = explode('@', $row['mailbox']);

            // Hide the DB internal ID from the front-end
            unset($row['id']);

            $devices[$devid] = $row;

            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

        $result->free();
        return $devices;
    }

    /**
     * Save a device (add or edit) to the backend.
     *
     * @param string $context  The context in which this device is valid
     * @param array $info      Array of device details
     */
    public function saveDevice($context, $info)
    {
        // See getDevices() for an explanation of these conversions
        $info['alias'] = $info['name'];
        $info['mailbox'] = $info['mailbox'] . '@' . $context;

        if ($info['devid']) {
            // This is an edit
            $info['name'] = $info['devid'];
            $sql = 'UPDATE %s SET ';
        } else {
            // This is an add.  Generate a new unique ID and secret
            $devid = $context . uniqid();
            $secret = md5(uniqid(mt_rand));
            $sql = 'INSERT INTO %s (name, accountcode, callerid, mailbox, ' .
                   'secret, alias, canreinvite, nat, type) ' .
                   ' VALUES (?, ?, ?, ?, ?, ?, "no", "yes", "peer")';

        }


    }

    /**
     * Get a list of users valid for the contexts
     *
     * @param string $context Context on which to search
     *
     * @return array User information indexed by voice mailbox number
     */
    public function getExtensions($context)
    {
        throw new Shout_Exception("Not implemented yet.");
    }

    /**
     * Save a user to the LDAP tree
     *
     * @param string $context Context to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $userdetails Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     */
    public function saveExtension($context, $extension, $userdetails)
    {
        parent::saveExtension($context, $extension, $details);
        throw new Shout_Exception("Not implemented.");
    }

    /**
     * Deletes a user from the LDAP tree
     *
     * @param string $context Context to delete the user from
     * @param string $extension Extension of the user to be deleted
     *
     * @return boolean True on success, PEAR::Error object on error
     */
    public function deleteExtension($context, $extension)
    {
        throw new Shout_Exception("Not implemented.");
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws Shout_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        Horde::assertDriverConfig($this->_params, $this->_params['class'],
                                  array('phptype', 'charset', 'table'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent'])));
        if ($this->_write_db instanceof PEAR_Error) {
            throw Shout_Exception($this->_write_db);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;

        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent'])));
            if ($this->_db instanceof PEAR_Error) {
                throw Shout_Exception($this->_db);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;

            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db = $this->_write_db;
        }

        $this->_connected = true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     */
    protected function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }
    }
    
}
