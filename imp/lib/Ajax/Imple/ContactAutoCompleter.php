<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_ContactAutoCompleter
{
    /**
     * Has the address book been output to the browser?
     *
     * @var boolean
     */
    static protected $_listOutput = false;

    /**
     */
    protected function _getAutoCompleter()
    {
        global $conf, $page_output, $session;

        $ac_browser = empty($conf['compose']['ac_browser'])
            ? 0
            : $conf['compose']['ac_browser'];
        $params = $this->_getAutoCompleterParams();

        if ($ac_browser && !$session->get('imp', 'ac_ajax')) {
            $use_ajax = true;
            $sparams = $this->_getAddressbookSearchParams();
            if (!array_diff($sparams->fields, array('email', 'name'))) {
                $addrlist = $this->getAddressList();
                $use_ajax = count($addrlist) > $ac_browser;
            }
            $session->set('imp', 'ac_ajax', $use_ajax);
        }

        if (!$ac_browser || $session->get('imp', 'ac_ajax')) {
            $params['minChars'] = intval($conf['compose']['ac_threshold'])
                ? $conf['compose']['ac_threshold']
                : 1;
            return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax($params);
        }

        if (!self::$_listOutput) {
            if (!isset($addrlist)) {
                $addrlist = $this->getAddressList();
            }

            $page_output->addInlineJsVars(array(
                'IMP_ac_list' => $addrlist->addresses
            ));
            self::$_listOutput = true;
        }

        return new Horde_Core_Ajax_Imple_AutoCompleter_Local('IMP_ac_list', $params);
    }

    /**
     */
    protected function _getAddressbookSearchParams()
    {
        $params = IMP::getAddressbookSearchParams();

        $ob = new stdClass;
        $ob->fields = $params['fields'];
        $ob->sources = $params['sources'];

        return $ob;
    }

}
