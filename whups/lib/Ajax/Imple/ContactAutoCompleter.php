<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Whups
 */
class Whups_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     * Attach the Imple object to a javascript event.
     *
     * @param array $js_params  See
     *                          Horde_Core_Ajax_Imple_AutoCompleter::_attach().
     *
     * @return array  See Horde_Core_Ajax_Imple_AutoCompleter::_attach().
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

        $ret['ajax'] = 'ContactAutoCompleter';

        return $ret;
    }

    /**
     * TODO
     *
     * @param array $args  TODO
     *
     * @return string  TODO
     */
    public function handle($args, $post)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getFormData($args['input']))) {
            return array();
        }

        return $this->_getAddressList($input);
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand.
     *
     * This function will not search if the address string is empty.
     *
     * @param string $str  The name(s) or address(es) to expand.
     *
     * @return array  All matching addresses.
     */
    protected function _getAddressList($str = '')
    {
        $str = trim($str);
        if (!strlen($str)) {
            return array();
        }
        $searchpref = Whups::getAddressbookSearchParams();
        try {
            $res = $GLOBALS['registry']->call('contacts/search', array($str, $searchpref['sources'], $searchpref['fields']));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        if (!count($res)) {
            return array();
        }

        /* The first key of the result will be the search term. The matching
         * entries are stored underneath this key. */
        $search = new Horde_Mail_Rfc822_List();
        foreach (reset($res) as $val) {
            if (!empty($val['email'])) {
                if (strpos($val['email'], ',') !== false) {
                    $search->add(new Horde_Mail_Rfc822_Group($val['name'], $val['email']));
                } else {
                    $addr_ob = new Horde_Mail_Rfc822_Address($val['email']);
                    if (!is_null($addr_ob->host)) {
                        $addr_ob->personal = $val['name'];
                        $search->add($addr_ob);
                    }
                }
            }
        }

        $sort_list = array();
        foreach ($search->addresses as $val) {
            $sort_list[$val] = @levenshtein($str, $val);
        }
        asort($sort_list, SORT_NUMERIC);

        return array_keys($sort_list);
    }
}
