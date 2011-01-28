<?php
/**
 * Attach an auto completer to a javascript element.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple_AutoCompleter extends Horde_Core_Ajax_Imple
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => (string) [optional] TODO
     * 'no_onload' => (boolean) [optional] Don't wait for dom:onload to attach
     * </pre>
     */
    public function __construct($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }

        if (empty($params['triggerContainer'])) {
            $params['triggerContainer'] = $this->_randomid();
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
        Horde::addScriptFile('keynavlist.js', 'horde');
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
                'triggerContainer' => $this->_params['triggerContainer'],
                'uri' => (string)$this->_getUrl($config['pretty'], $GLOBALS['registry']->getApp()),
                'deleteIcon' => (string)Horde_Themes::img('delete-small.png'),
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

        Horde::addInlineScript((isset($config['var']) ? $config['var'] . ' = ' : '') . 'new ' . $func . '(' . implode(',', $params) . ',' . $js_params . ')', empty($this->_params['no_onload']) ? 'dom' : null);
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
