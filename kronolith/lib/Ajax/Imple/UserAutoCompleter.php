<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * JavaScript autocompleter for Horde users.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Ajax_Imple_UserAutoCompleter
extends Horde_Core_Ajax_Imple_UserAutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        $opts = array();

        foreach (array('beforeUpdate', 'box', 'displayFilter', 'onAdd', 'onRemove', 'triggerContainer') as $val) {
            if (isset($this->_params[$val])) {
                $opts[$val] = $this->_params[$val];
            }
        }

        return empty($this->_params['pretty'])
            ? new Horde_Core_Ajax_Imple_AutoCompleter_Ajax($opts)
            : new Horde_Core_Ajax_Imple_AutoCompleter_Pretty($opts);
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        global $injector;

        $identFactory = $injector->getInstance('Horde_Core_Factory_Identity');
        $users = array();
        foreach (parent::_handleAutoCompleter($input) as $user) {
            $name = $identFactory->create($user)->getName();
            if ($name != $user) {
                $user = $name . ' [' . $user . ']';
            }
            $users[] = $user;
        }
        return $users;
    }
}