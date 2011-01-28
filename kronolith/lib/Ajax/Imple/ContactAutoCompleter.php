<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
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

        $ret = array('params' => $js_params,
                     'raw_params' => array(
                         'onSelect' => 'function (v) { if (!v.endsWith(";")) { v += ","; } return v + " "; }',
                         'onType' => 'function (e) { return e.include("<") ? "" : e; }',
                      ));
        if (isset($this->_params['onAdd'])) {
            $ret['raw_params']['onAdd'] = $this->_params['onAdd'];
            $ret['raw_params']['onRemove'] = $this->_params['onRemove'];
        }
        if (empty($this->_params['pretty'])) {
            $ret['ajax'] = 'ContactAutoCompleter';
        } else {
            $ret['pretty'] = 'ContactAutoCompleter';
        }

        if (!empty($this->_params['var'])) {
            $ret['var'] = $this->_params['var'];
        }

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
     * @param string $addrString  The name(s) or address(es) to expand.
     *
     * @return array  All matching addresses.
     */
    protected function _getAddressList($addrString = '')
    {
        if (!preg_match('|[^\s]|', $addrString)) {
            return array();
        }

        $search = reset(array_filter(array_map('trim', Horde_Mime_Address::explode($addrString, ',;'))));

        $searchpref = Kronolith::getAddressbookSearchParams();

        try {
            $res = $GLOBALS['registry']->call('contacts/search', array($search, $searchpref['sources'], $searchpref['fields'], true));
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
