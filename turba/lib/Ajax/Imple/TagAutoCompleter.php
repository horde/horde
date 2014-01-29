<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Turba
 */
class Turba_Ajax_Imple_TagAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        $opts = array();

        foreach (array('box', 'triggerContainer') as $val) {
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
        return array_values($GLOBALS['injector']->getInstance('Turba_Tagger')->listTags($input));
    }

}
