<?php
/**
 * Auto completer for contact entries.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _attach($js_params)
    {
        $js_params['tokens'] = array(',');
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';

        return array(
            'ajax' => 'ContactAutoCompleter',
            'params' => $js_params,
            'raw_params' => array(
                'onType' => 'function (e) { return e.include("<") ? "" : e; }'
            )
        );
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

        return array_map('strval', $this->getAddressList($input, array(
            'levenshtein' => true
        ))->base_addresses);
    }

    /**
     * Uses the Registry to expand names.
     * This function will not search if the address string is empty.
     *
     * @param string $str  The name(s) or address(es) to expand.
     * @param array $opts  Additional options:
     *   - levenshtein: (boolean)  Do levenshtein sorting.
     *
     * @return Horde_Mail_Rfc822_List  Expand results.
     */
    public function getAddressList($str = '', array $opts = array())
    {
        $str = trim($str);
        if (!strlen($str)) {
            return new Horde_Mail_Rfc822_List();
        }

        $searchpref = $this->_getAddressbookSearchParams();

        try {
            $search = $GLOBALS['registry']->call('contacts/search', array($str, array(
                'fields' => $searchpref->fields,
                'returnFields' => array('email', 'name'),
                'rfc822Return' => true,
                'sources' => $searchpref->sources
            )));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return new Horde_Mail_Rfc822_List();
        }

        if (empty($levenshtein)) {
            return $search;
        }

        $sort_list = array();
        foreach ($search->addresses as $val) {
            $sort_list[$val] = @levenshtein($str, $val);
        }
        asort($sort_list, SORT_NUMERIC);

        return new Horde_Mail_Rfc822_List($sort_list);
    }

    /**
     * Return search parameters necessary to do a contacts API search.
     *
     * @return object  Object with these properties:
     *   - fields: TODO
     *   - sources: TODO
     */
    abstract protected function _getAddressbookSearchParams();

}
