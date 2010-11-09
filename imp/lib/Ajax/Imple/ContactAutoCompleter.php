<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
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
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';

        $ret = array(
            'params' => $js_params,
            'raw_params' => array(
                'onSelect' => 'function (v) { if (!v.endsWith(";")) { v += ","; } return v + " "; }',
                'onType' => 'function (e) { return e.include("<") ? "" : e; }'
            )
        );

        $ac_browser = empty($GLOBALS['conf']['compose']['ac_browser'])
            ? 0
            : $GLOBALS['conf']['compose']['ac_browser'];

        if ($ac_browser && !$GLOBALS['session']->get('imp', 'ac_ajax')) {
            $success = $use_ajax = true;
            $sparams = IMP::getAddressbookSearchParams();
            foreach ($sparams['fields'] as $val) {
                array_map('strtolower', $val);
                sort($val);
                if ($val != array('email', 'name')) {
                    $success = false;
                    break;
                }
            }
            if ($success) {
                $addrlist = IMP_Compose::getAddressList();
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
                    $addrlist = IMP_Compose::getAddressList();
                }
                Horde::addInlineScript(array_merge(array(
                    'if (!window.IMP) window.IMP = {}'
                ), Horde::addInlineJsVars(array(
                    'IMP.ac_list' => $addrlist
                ), array('ret_vars' => true))));
                self::$_listOutput = true;
            }

            $ret['browser'] = 'IMP.ac_list';
        }

        return $ret;
    }

    /**
     * Perform the address search.
     *
     * @param array $args  Array with 1 key: 'input'.
     *
     * @return array  The data to send to the autocompleter JS code.
     */
    public function handle($args, $post)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getPost($args['input']))) {
            return array();
        }

        return IMP_Compose::expandAddresses($input, array('levenshtein' => true));
    }

}
