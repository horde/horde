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
     * Attach the object to a javascript event.
     */
    protected function _attach($js_params)
    {
        $ret = parent::_attach($js_params);

        $ac_browser = empty($GLOBALS['conf']['compose']['ac_browser'])
            ? 0
            : $GLOBALS['conf']['compose']['ac_browser'];

        if ($ac_browser && !$GLOBALS['session']->get('imp', 'ac_ajax')) {
            $use_ajax = true;
            $sparams = $this->_getAddressbookSearchParams();
            if (!array_diff($sparams->fields, array('email', 'name'))) {
                $addrlist = $this->getAddressList();
                $use_ajax = count($addrlist) > $ac_browser;
            }
            $GLOBALS['session']->set('imp', 'ac_ajax', $use_ajax);
        }

        if (!$ac_browser || $GLOBALS['session']->get('imp', 'ac_ajax')) {
            $ret['ajax'] = 'ContactAutoCompleter';
            $ret['params']['minChars'] = intval($GLOBALS['conf']['compose']['ac_threshold'] ? $GLOBALS['conf']['compose']['ac_threshold'] : 1);
        } else {
            if (!self::$_listOutput) {
                if (!isset($addrlist)) {
                    $addrlist = $this->getAddressList();
                }

                $GLOBALS['page_output']->addInlineScript(array_merge(array(
                    'if (!window.IMP) window.IMP = {}'
                ), $GLOBALS['page_output']->addInlineJsVars(array(
                    'IMP.ac_list' => $addrlist->addresses
                ), array('ret_vars' => true))));
                self::$_listOutput = true;
            }

            $ret['browser'] = 'IMP.ac_list';
        }

        return $ret;
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
