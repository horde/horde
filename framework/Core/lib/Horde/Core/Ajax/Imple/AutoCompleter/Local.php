<?php
/**
 * The local (browser-side) autocompleter.
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
class Horde_Core_Ajax_Imple_AutoCompleter_Local extends Horde_Core_Ajax_Imple_AutoCompleter_Ajax
{
    /**
     * The search array to send to the browser.
     *
     * @var mixed
     */
    protected $_search;

    /**
     * @param mixed $search  The search array to use. If a string,
     *                       autocompleter will use this JS variable name to
     *                       do the search. If an array, this data will be
     *                       passed to the autocompleter automatically.
     * @param array $params  Configuration options:
     *   - autoSelect: TODO
     *   - choices: (integer) TODO
     *   - frequency: (integer) TODO
     *   - fullSearch: (integer) TODO
     *   - ignoreCase: (integer) TODO
     *   - minChars: (integer) Minimum # of characters before search is made.
     *   - onSelect: (string) Javascript code to run on select.
     *   - onShow: (string) Javascript code to run on show.
     *   - onType: (string) Javascript code to run on type.
     *   - paramName: (string) TODO
     *   - partialChars: (integer) TODO
     *   - partialSearch: (integer) TODO
     *   - score: (integer) TODO
     *   - tokens: (array) Valid token separators.
     */
    public function __construct($search, array $params = array())
    {
        $this->_search = $search;

        parent::__construct(array_merge(array(
            'fullSearch' => 1,
            'partialSearch' => 1,
            'score' => 1,
            'tokens' => array(',', ';')
        ), $params));
    }

    /**
     */
    public function generate(Horde_Core_Ajax_Imple_AutoCompleter $ac)
    {
        $dom_id = $ac->getDomId();

        return 'new Autocompleter.Local(' .
            Horde_Serialize::serialize($dom_id, Horde_Serialize::JSON) . ',' .
            (is_string($this->_search) ? $this->_search : Horde_Serialize::serialize($this->_search, Horde_Serialize::JSON)) . ',' .
            '{' . implode(',', $this->_getOpts($ac)) . '})';
    }

}
