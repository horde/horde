<?php
/**
 * Sam storage implementation for LDAP backend.

 * Requires SpamAssassin patch found at:
 * http://bugzilla.spamassassin.org/show_bug.cgi?id=2205
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @author  Neil Sequeira <neil@ncsconsulting.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
class Sam_Driver_Spamd_Ldap extends Sam_Driver_Spamd_Base
{
    /**
     * Handle for the current LDAP connection.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Constructor.
     *
     * @param string $user   A user name.
     * @param array $params  Class parameters:
     *                       - ldap: (Horde_Ldap) An LDAP handle pointing to
     *                         the directory server.
     *                       - uid: (string) The user ID attribute name.
     *                       - basedn: (string) The base DN.
     *                       - attribute: (string) The storage attribute.
     */
    public function __construct($user, $params = array())
    {
        foreach (array('ldap', 'uid', 'basedn', 'attribute', 'defaults') as $param) {
            if (!isset($params[$param])) {
                throw new InvalidArgumentException(
                    sprintf('"%s" parameter is missing', $param));
            }
        }

        $this->_ldap = $params['ldap'];
        $this->_options = $params['defaults'];
        unset($params['ldap'], $params['defaults']);

        parent::__construct($user, $params);
    }

    /**
     * Retrieves user preferences from the backend.
     *
     * @throws Sam_Exception
     */
    public function retrieve()
    {
        $attrib = Horde_String::lower($this->_params['attribute']);

        try {
            $search = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::create($this->_params['uid'], 'equals', $this->_user),
                array('attributes' => array($attrib)));

            $entry = $search->shiftEntry();
            if (!$entry) {
                throw new Sam_Exception(sprintf('LDAP user "%s" not found.', $this->_user));
            }

            foreach ($entry->getValue($attrib, 'all') as $attribute) {
                list($a, $v) = explode(' ', $attribute);
                $ra = $this->_mapOptionToAttribute($a);
                if (is_numeric($v)) {
                    if (strstr($v, '.')) {
                        $newoptions[$ra][] = (float)$v;
                    } else {
                        $newoptions[$ra][] = (int)$v;
                    }
                } else {
                    $newoptions[$ra][] = $v;
                }
            }
        } catch (Horde_Ldap_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Go through new options and pull single values out of their
         * arrays. */
        foreach ($newoptions as $k => $v) {
            if (count($v) > 1) {
                $this->_options[$k] = $v;
            } else {
                $this->_options[$k] = $v[0];
            }
        }
    }

    /**
     * Stores user preferences in the backend.
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options. Unused.
     *
     * @throws Sam_Exception
     */
    public function store($defaults = false)
    {
        $entry = array();
        foreach ($this->_options as $a => $v) {
            $sa = $this->_mapAttributeToOption($a);
            if (is_array($v)) {
                foreach ($v as $av) {
                    $entry[] = $sa . ' ' . $av;
                }
            } else {
                $entry[] = $sa . ' ' . $v;
            }
        }

        $userdn = sprintf('%s=%s,%s',
                          $this->_params['uid'],
                          $this->_user,
                          $this->_params['basedn']);
        try {
            $this->_ldap->modify(
                $userdn,
                array('replace' => array($this->_params['attribute'] => $entry)));
        } catch (Horde_Ldap_Exception $e) {
            throw new Sam_Exception($e);
        }
    }
}
