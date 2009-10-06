<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Ajax_Imple_ContactAutoCompleter extends Horde_Ajax_Imple_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => TODO (optional)
     * 'resultsId' => TODO (optional)
     * </pre>
     */
    public function __construct($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }
        if (empty($params['resultsId'])) {
            $params['resultsId'] = $params['triggerId'] . '_results';
        }

        parent::__construct($params);
    }

    /**
     * Attach the Imple object to a javascript event.
     */
    public function attach()
    {
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('autocomplete.js', 'horde');

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"',
            '"' . $this->_getUrl('ContactAutoCompleter', 'kronolith', array('input' => $this->_params['triggerId'])) . '"'
        );

        $js_params = array(
            'tokens: [",", ";"]',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"',
            'afterUpdateElement: function(f, t) { if (!f.value.endsWith(";")) { f.value += ","; } f.value += " "; }'
        );

        $params[] = '{' . implode(',', $js_params) . '}';

        Horde::addInlineScript('new Ajax.Autocompleter(' . implode(',', $params) . ')', 'dom');
    }

    /**
     * TODO
     *
     * @param array $args  TODO
     *
     * @return string  TODO
     */
    public function handle($args)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getFormData($args['input']))) {
            return array();
        }

        return array_map('htmlspecialchars', $this->_expandAddresses($input));
    }

    /**
     * Uses the Registry to expand names and return error information for
     * any address that is either not valid or fails to expand. This function
     * will not search if the address string is empty.
     *
     * @param string $addrString  The name(s) or address(es) to expand.
     *
     * @return array  All matching addresses.
     */
    protected function _expandAddresses($addrString)
    {
        return preg_match('|[^\s]|', $addrString)
            ? $this->getAddressList(reset(array_filter(array_map('trim', Horde_Mime_Address::explode($addrString, ',;')))))
            : '';
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
        $src = explode("\t", $GLOBALS['prefs']->getValue('search_sources'));
        if ((count($src) == 1) && empty($src[0])) {
            $src = array();
        }

        $fields = array();
        if (($val = $GLOBALS['prefs']->getValue('search_fields'))) {
            $field_arr = explode("\n", $val);
            foreach ($field_arr as $field) {
                $field = trim($field);
                if (!empty($field)) {
                    $tmp = explode("\t", $field);
                    if (count($tmp) > 1) {
                        $source = array_splice($tmp, 0, 1);
                        $fields[$source[0]] = $tmp;
                    }
                }
            }
        }

        try {
            $res = $GLOBALS['registry']->call('contacts/search', array($search, $src, $fields, true));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
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
