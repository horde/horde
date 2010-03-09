<?php
/**
 * Attach an auto completer to a javascript element.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Ajax
 */
abstract class Horde_Ajax_Imple_AutoCompleter extends Horde_Ajax_Imple_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => (string) [optional] TODO
     * </pre>
     */
    public function __construct($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        $params = array(
            '"' . $this->_params['triggerId'] . '"'
        );

        $config = $this->_attach(array('tokens' => array(',', ';')));

        Horde::addScriptFile('autocomplete.js', 'horde');
        Horde::addScriptFile('KeyNavList.js', 'horde');
        Horde::addScriptFile('liquidmetal.js', 'horde');
        if (isset($config['ajax'])) {
            $func = 'Ajax.Autocompleter';
            $params[] = '"' . $this->_getUrl($config['ajax'], $GLOBALS['registry']->getApp(), array('input' => $this->_params['triggerId'])) . '"';
        } elseif (isset($config['browser'])) {
            $func = 'Autocompleter.Local';
            $params[] = $config['browser'];
            $config['params'] = array_merge(array(
                'partialSearch' => 1,
                'fullSearch' => 1,
                'score' => 1
            ), $config['params']);
        } elseif (isset($config['pretty'])) {
            Horde::addScriptFile('prettyautocomplete.js', 'horde');
            $func = 'PrettyAutocompleter';
            $config['params'] = array_merge(array(
                'boxClass' => 'hordeACBox kronolithLongField',
                'trigger' => $this->_params['triggerId'],
                'uri' => (string)$this->_getUrl($config['pretty'], $GLOBALS['registry']->getApp()),
                'URI_IMG_HORDE' => (string)Horde_Themes::img(null, 'horde'),
                'box' => !empty($this->_params['box']) ? $this->_params['box'] : ''
            ), $config['params']);

            if (!empty($this->_params['existing'])) {
                $config['params']['existing'] = $this->_params['existing'];
            }
        } else {
            return;
        }

        $config['raw_params'] = !empty($config['raw_params']) ? $config['raw_params'] : array();
        foreach ($config['raw_params'] as $name => $val) {
            $config['params'][$name] = 1;
        }

        $js_params = Horde_Serialize::serialize($config['params'], Horde_Serialize::JSON);

        foreach ($config['raw_params'] as $name => $val) {
            $js_params = str_replace('"' . $name . '":1', '"' . $name . '":' . $val, $js_params);
        }

        Horde::addScriptFile('effects.js', 'horde');

        Horde::addInlineScript((isset($config['var']) ? $config['var'] . ' = ' : '') . 'new ' . $func . '(' . implode(',', $params) . ',' . $js_params . ')', 'dom');
    }

    /**
     * Attach the object to a javascript event.
     *
     * @return array  An array with the following elements:
     * <pre>
     * ONE of the following:
     * 'ajax' - (string) Use 'Ajax.Autocompleter' class. Value is the AJAX
     *          function name.
     * 'browser' - (string) Use 'Autocompleter.Local' class. Value is the
     *             javascript list of items to search.
     * 'pretty' - (string) Use 'PrettyAutocompleter' class. Value is the AJAX
     *            function name.
     *
     * Additional Options:
     * 'params' - (array) The list of javascript parameters to pass to the
     *            autocomplete libraries.
     * 'raw_params' - (array) Adds raw javascript to the 'params' array.
     * 'var' - (string) If set, the autocomplete object will be assigned to
     *         this variable.
     * </pre>
     */
    abstract protected function _attach($js_params);

}
