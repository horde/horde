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
     * @param string $context    Context in which to search for devices
     * @param string $extension  Extension in which to search for devices
     *
     * @return array  Array of devices within this context with their information
     *
     * @access private
     */
    public function getDevices($context, $extension)
    {
        $sql = 'SELECT id, name, callerid, context, host, permit, nat, ' .
               'secret, disallow, allow FROM %s WHERE mailbox = ?';
        $sql = sprintf($sql, $this->_params['table']);
        $args = array($extension . '@' . $context);

        $result = $this->_db->query($sql, $args);
        if (is_a($result instanceof PEAR_Error)) {
            throw Shout_Exception($result);
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            throw Shout_Exception($row);
        }

        $devices = array();
        while ($row && !($row instanceof PEAR_Error)) {
            /* Add this new foo to the $_foo list. */
            $devices[] = $row;

            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

        $result->free();

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
     * @throws Horde_Exception
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
            throw Horde_Exception($this->_write_db);
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
                throw Horde_Exception($this->_db);
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
