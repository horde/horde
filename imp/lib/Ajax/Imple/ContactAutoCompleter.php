<?php
/**
 * Copyright 2005-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attach the contact auto completer to a javascript element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_ContactAutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        return new IMP_Ajax_Imple_AutoCompleter_Pretty(
            $this->_getAutoCompleterParams()
        );
    }

    /**
     */
    protected function _getAutoCompleterParams()
    {
        global $conf;

        return array_merge(parent::_getAutoCompleterParams(), array(
            'minChars' => intval($conf['compose']['ac_threshold']) ?: 1
        ));
    }

    /**
     */
    protected function _getAddressbookSearchParams()
    {
        $params = $GLOBALS['injector']->getInstance('IMP_Contacts')->getAddressbookSearchParams();

        $ob = new stdClass;
        $ob->fields = $params['fields'];
        $ob->sources = $params['sources'];

        return $ob;
    }

}
