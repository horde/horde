<?php
/**
 * The AJAX autocompleter.
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
class Horde_Core_Ajax_Imple_AutoCompleter_Ajax extends Horde_Core_Ajax_Imple_AutoCompleter_Base
{
    /**
     * The list of the parameters that are raw javascript.
     *
     * @var array
     */
    protected $_raw = array('onSelect', 'onShow', 'onType', 'filterCallback');

    /**
     * @param array $params  Configuration options:
     *   - autoSelect: TODO
     *   - frequency: (integer) TODO
     *   - indicator: (string) TODO
     *   - minChars: (integer) Minimum # of characters before search is made.
     *   - onSelect: (string) Javascript code to run on select.
     *   - onShow: (string) Javascript code to run on show.
     *   - onType: (string) Javascript code to run on type.
     *   - filterCallback: (string) Javascript code to run to apply any filtering
     *                     to results returned by the handler.
     *   - paramName: (string) TODO
     *   - tokens: (array) Valid token separators.
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'tokens' => array(',', ';')
        ), $params));
    }

    /**
     */
    public function generate(Horde_Core_Ajax_Imple_AutoCompleter $ac)
    {
        $dom_id = $ac->getDomId();

        return 'new Ajax.Autocompleter(' .
            Horde_Serialize::serialize($dom_id, Horde_Serialize::JSON) . ',' .
            Horde_Serialize::serialize(strval($ac->getImpleUrl()->setRaw(true)->add(array('input' => $dom_id))), Horde_Serialize::JSON) . ',' .
            '{' . implode(',', $this->_getOpts($ac)) . '})';
    }

    /**
     * Return the encode list of options.
     *
     * @return array  Options list.
     */
    protected function _getOpts(Horde_Core_Ajax_Imple_AutoCompleter $ac)
    {
        $opts = array();

        if (!isset($this->params['indicator'])) {
            $this->params['indicator'] = $ac->getDomId() . '_loading_img';
        }

        foreach ($this->params as $key => $val) {
            $opts[] = $key . ':' . (in_array($key, $this->_raw) ? $val : Horde_Serialize::serialize($val, Horde_Serialize::JSON));
        }

        return $opts;
    }

}
