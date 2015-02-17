<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Ingo_Storage API implementation to save Ingo data via the Horde preferences
 * system.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Prefs
extends Ingo_Storage
{
    /**
     */
    protected function _loadFromBackend()
    {
        if ($rules = @unserialize($this->_prefs()->getValue('rules'))) {
            $this->_rules = $rules;
        }
    }

    /**
     */
    protected function _removeUserData($user)
    {
        $this->_prefs($user)->remove('rules');
    }

    /**
     */
    protected function _storeBackend($action, $rule)
    {
        switch ($action) {
        case self::STORE_ADD:
            if (!strlen($rule->uid)) {
                $rule->uid = strval(new Horde_Support_Randomid());
            }
            break;
        }

        $this->_prefs()->setValue('rules', serialize($this->_rules));
    }

    /**
     * Get prefs object to use for storage.
     *
     * @param string $user  Username to use (if not default).
     *
     * @return Horde_Prefs  Prefs object.
     */
    protected function _prefs($user = null)
    {
        global $injector;

        return $injector->getInstance('Horde_Core_Factory_Prefs')->create('ingo', array(
            'cache' => false,
            'user' => is_null($user) ? Ingo::getUser() : $user
        ));
    }

}
