<?php
/**
 * Attach the auto completer to a javascript element.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 */
class Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     * Attach the object to a javascript event.
     */
    protected function _attach($js_params)
    {
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';
        $updateurl = Horde::getServiceLink('ajax', 'horde')->setRaw(true);
        $updateurl->pathInfo = 'blockRefresh';
        $updateurl->add('app', 'horde')
                  ->add('blockid', 'horde_block_weather');
        Horde::addInlineScript(
            array(
                'if (!window.weatherupdate) { window.weatherupdate = {}; }',
                'window.weatherupdate["' . $this->_params['instance'] . '"] = {
                    value: false,
                    choices: {},
                    update: function() {
                        var v;
                        if (this.value) {
                            v = this.value;
                        } else {
                            v = $F("location' . $this->_params['instance'] . '");
                        }
                        new Ajax.Updater(
                            "weathercontent' . $this->_params['instance'] . '",
                            "' . strval($updateurl) . '",
                            { evalScripts: true, parameters: { location: v } }
                        );

                        this.value = false;
                    }
                }',
                '$("button' . $this->_params['instance'] . '").observe("click", function(e) {
                    window.weatherupdate["' . $this->_params['instance'] . '"].update();
                });'
            ),
            'dom'
        );


        $ret = array(
            'params' => $js_params,
            'raw_params' => array(
                'filterCallback' => 'function(c) {
                    window.weatherupdate["' . $this->_params['instance'] . '"].choices = c;
                    var r = [];
                    c.each(function(i) {
                        r.push(i.name);
                    });
                    return r;
                }',
                'onSelect' => 'function(c) {
                    window.weatherupdate["' . $this->_params['instance'] . '"].choices.each(function(i) {
                        if (i.name == c) {
                            window.weatherupdate["' . $this->_params['instance'] . '"].value = i.code.replace("/q/", "");
                            throw $break;
                        } else {
                            window.weatherupdate["' . $this->_params['instance'] . '"].value = false;
                        }
                    });
                    return c;
                }'
            )
        );
        $ret['params']['minChars'] = 5;
        $ret['ajax'] = 'WeatherLocationAutoCompleter';

        if (!empty($this->_params['var'])) {
            $ret['var'] = $this->_params['var'];
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
        $w = $GLOBALS['injector']
            ->getInstance('Horde_Weather');
        $r = $w->autocompleteLocation($input);

        return $r;
    }

}
