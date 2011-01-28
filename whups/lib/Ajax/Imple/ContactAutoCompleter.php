<?php
/**
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
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
            !($input = Horde_Util::getPost($args['input']))) {
            return array();
        }

        return array_map('htmlspecialchars', self::expandAddresses($input));
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand. This function
     * will not search if the address string is empty.
     *
     * @param string $addrString  The name(s) or address(es) to expand.
     * @param array $options      Additional options:
     * <pre>
     * 'levenshtein' - (boolean) If true, will sort the results using the
     *                 PHP levenshtein() scoring function.
     * </pre>
     *
     * @return array  All matching addresses.
     */
    static public function expandAddresses($addrString, $options = array())
    {
        if (!preg_match('|[^\s]|', $addrString)) {
            return array();
        }

        $addrString = reset(array_filter(array_map('trim', Horde_Mime_Address::explode($addrString, ',;'))));
        $addr_list = self::getAddressList($addrString);

        if (empty($options['levenshtein'])) {
            return $addr_list;
        }

        $sort_list = array();
        foreach ($addr_list as $val) {
            $sort_list[$val] = levenshtein($addrString, $val);
        }
        asort($sort_list, SORT_NUMERIC);

        return array_keys($sort_list);
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand.
     *
     * @param string $search  The term to search by.
     *
     * @return array  All matching addresses.
     */
    static public function getAddressList($search = '')
    {
        $sparams = Whups::getAddressbookSearchParams();
        try {
            $res = $GLOBALS['registry']->call('contacts/search', array($search, $sparams['sources'], $sparams['fields'], false));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        if (!count($res)) {
            return array();
        }

        /* The first key of the result will be the search term. The matching
         * entries are stored underneath this key. */
        $search = array();
        foreach (reset($res) as $val) {
            if (!empty($val['email'])) {
                if (strpos($val['email'], ',') !== false) {
                    $search[] = Horde_Mime_Address::encode($val['name'], 'personal') . ': ' . $val['email'] . ';';
                } else {
                    $mbox_host = explode('@', $val['email']);
                    if (isset($mbox_host[1])) {
                        $search[] = Horde_Mime_Address::writeAddress($mbox_host[0], $mbox_host[1], $val['name']);
                    }
                }
            }
        }

        return $search;
    }

}
