<?php
/**
 * This class provides Horde-specific code that extends the base LDAP driver.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Group_Ldap extends Horde_Group_Ldap
{
    /**
     * Creates a new group.
     *
     * @param string $name   A group name.
     * @param string $email  The group's email address.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    public function create($name, $email = null)
    {
        try {
            $entry = Horde::callHook('groupldap', array($name, $email));
            return $this->_create($name, $entry);
        } catch (Horde_Exception_HookNotSet $e) {
            return parent::create($name, $email);
        }
    }
}
