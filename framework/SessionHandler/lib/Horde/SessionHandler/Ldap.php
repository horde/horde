<?php
/**
 * SessionHandler implementation for LDAP directories.
 *
 * This code is adapted from the comments at
 * http://www.php.net/session-set-save-handler.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  SessionHandler
 */
class Horde_SessionHandler_Ldap extends Horde_SessionHandler_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var resource
     */
    protected $_conn;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'dn' - (string) The bind DN.
     * 'hostspec' - (string) The hostname of the ldap server.
     * 'password' - (string) The bind password.
     * 'port' - (integer) The port number of the ldap server.
     * 'version' - (integer) [OPTIONAL] TODO
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('dn', 'hostspec', 'password', 'port') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        parent::__construct($params);
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @throws Horde_SessionHandler_Exception
     */
    protected function _open($save_path = null, $session_name = null)
    {
        $this->_conn = @ldap_connect($this->_params['hostspec'], $this->_params['port']);

        // Set protocol version if necessary.
        if (isset($this->_params['version']) &&
            !@ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, $this->_params['version'])) {
            throw new Horde_SessionHandler_Exception(sprintf('Set LDAP protocol version to %d failed: [%d] %s', $this->_params['version'], ldap_errno($conn), ldap_error($conn)));
        }

        if (!@ldap_bind($this->_conn, $this->_params['dn'], $this->_params['password'])) {
            throw new Horde_SessionHandler_Exception('Could not bind to LDAP server.');
        }
    }

    /**
     * Close the backend.
     */
    protected function _close()
    {
        if (!@ldap_close($this->_conn) && $this->_logger) {
            $this->_logger->log('Could not unbind from LDAP server.', 'INFO');
        }
    }

    /**
     * Read the data for a particular session identifier from the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _read($id)
    {
        $sr = @ldap_search($this->_conn, $this->_params['dn'], "(cn=$id)");
        $info = @ldap_get_entries($this->_conn, $sr);

        return ($info['count'] > 0)
            ? $info[0]['session'][0]
            : '';
    }

    /**
     * Write session data to the backend.
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    protected function _write($id, $session_data)
    {
        $update = array(
            'objectClass' => array('phpsession', 'top'),
            'session' => $session_data
        );
        $dn = "cn=$id," . $this->_params['dn'];
        @ldap_delete($this->_conn, $dn);

        return @ldap_add($this->_conn, $dn, $update);
    }

    /**
     * Destroy the data for a particular session identifier in the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function destroy($id)
    {
        $dn = "cn=$id," . $this->_params['dn'];

        return @ldap_delete($this->_conn, $dn);
    }

    /**
     * Garbage collect stale sessions from the backend.
     *
     * @param integer $maxlifetime  The maximum age of a session.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function gc($maxlifetime = 300)
    {
        $sr = @ldap_search($this->_conn, $this->_params['dn'],
                           '(objectClass=phpsession)', array('+', 'cn'));
        $info = @ldap_get_entries($this->_conn, $sr);

        if ($info['count'] > 0) {
            for ($i = 0; $i < $info['count']; ++$i) {
                $id = $info[$i]['cn'][0];
                $dn = "cn=$id," . $this->_params['dn'];
                $ldapstamp = $info[$i]['modifytimestamp'][0];
                $year = substr($ldapstamp, 0, 4);
                $month = substr($ldapstamp, 4, 2);
                $day = substr($ldapstamp, 6, 2);
                $hour = substr($ldapstamp, 8, 2);
                $minute = substr($ldapstamp, 10, 2);
                $modified = gmmktime($hour, $minute, 0, $month, $day, $year);
                if (time() - $modified >= $maxlifetime) {
                    @ldap_delete($this->_conn, $dn);
                }
            }
        }

        return true;
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionIDs()
    {
        throw new Horde_SessionHandler_Exception('Driver does not support listing session IDs.');
    }

}
