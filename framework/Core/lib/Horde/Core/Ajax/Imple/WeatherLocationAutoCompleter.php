<?php
/**
 * Imple to provide weather/location autocompletion.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
     */
    protected function _getAutoCompleter()
    {
        $url = $GLOBALS['registry']->getServiceLink('ajax')->setRaw(true);
        $url->url .= 'blockRefresh';
        $url->add('blockid', 'horde_block_weather');
        $indicator = $this->_params['id'] . '_loading_img';

        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineScript(
            array(
                'window.weatherupdate = window.weatherupdate || {}',
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
                        $("' . $indicator . '").toggle();
                        new Ajax.Updater(
                            "weathercontent' . $this->_params['instance'] . '",
                            "' . $url . '",
                            {
                                evalScripts: true,
                                parameters: { location: v },
                                onComplete: function() { $("' . $indicator . '").toggle(); }
                            }
                        );

                        this.value = false;
                    }
                }',
                '$("button' . $this->_params['instance'] . '").observe("click", function(e) {
                    window.weatherupdate["' . $this->_params['instance'] . '"].update();
                    e.stop();
                })'
            ),
            true
        );

        return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax(array(
            'minChars' => 5,
            'tokens' => array(),

            'filterCallback' => 'function(c) {
                    if (c) {
                        window.weatherupdate["' . $this->_params['instance'] . '"].choices = c;
                        var r = [];
                        c.each(function(i) {
                            r.push(i.name);
                        });
                        return r;
                    } else {
                        return [];
                    }
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
        ));
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        return $GLOBALS['injector']->getInstance('Horde_Weather')->autocompleteLocation($input);
    }

}
