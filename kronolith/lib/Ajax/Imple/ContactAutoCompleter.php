<?php
/**
 * Imple to attach the contact autocompleter to a HTML element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_ContactAutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        $opts = array();

        foreach (array('box', 'onAdd', 'onRemove', 'triggerContainer') as $val) {
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
    protected function _getAddressbookSearchParams()
    {
        $params = Kronolith::getAddressbookSearchParams();

        $ob = new stdClass;
        $ob->fields = $params['fields'];
        $ob->sources = $params['sources'];

        return $ob;
    }

}
