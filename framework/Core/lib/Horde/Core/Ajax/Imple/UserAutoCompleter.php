<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * JavaScript autocompleter for Horde users.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Imple_UserAutoCompleter
extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax(
            array('tokens' => array(','))
        );
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        return $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Auth')
            ->create()
            ->searchUsers($input);
    }
}
