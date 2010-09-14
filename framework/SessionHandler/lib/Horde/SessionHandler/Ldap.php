<?php
/**
 * Horde_SessionHandler implementation for LDAP directories.
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
class Horde_SessionHandler_Ldap extends Horde_SessionHandler_Base
{
    /**
     * Horde_Ldap object.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'dn' - (string) [REQUIRED] TODO
     * 'ldap' - (Horde_Ldap) [REQUIRED] The Horde_Ldap object.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('dn', 'ldap') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

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
        try {
            $this->_ldap->bind();
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_SessionHandler_Exception($e);
        }
    }

    /**
     * Close the backend.
     */
    protected function _close()
    {
        $this->_ldap->disconnect();
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
        try {
            $result = $this->_ldap->search(null, "(cn=$id)");
            if ($result->count()) {
                $entry = reset();
                return $entry['session'][0];
            }
        } catch (Horde_Ldap_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
        }

        return '';
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

        $this->_ldap->delete($dn);

        return $this->_ldap->add(Horde_Ldap_Entry::createFresh($dn, $update));
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

        return $this->_ldap->delete($dn);
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
        $info = $this->_ldap->search(null, '(objectClass=phpsession)', array('attributes' => array('+', 'cn')));

        foreach ($info as $val) {
            $id = $val['cn'][0];
            $dn = "cn=$id," . $this->_params['dn'];
            $ldapstamp = $val['modifytimestamp'][0];
            $year = substr($ldapstamp, 0, 4);
            $month = substr($ldapstamp, 4, 2);
            $day = substr($ldapstamp, 6, 2);
            $hour = substr($ldapstamp, 8, 2);
            $minute = substr($ldapstamp, 10, 2);
            $modified = gmmktime($hour, $minute, 0, $month, $day, $year);
            if (time() - $modified >= $maxlifetime) {
                $this->_ldap->delete($dn);
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
