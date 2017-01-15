<?php
/**
 * Base class Imple to provide weather/location autocompletion.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 */
abstract class Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter_Base
  extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     *
     * @param  string $block  The block type containing this autocompleter.
     *
     * @return  Horde_Core_Ajax_Imple_AutoCompleter_Ajax
     */
    protected function _getAutoCompleterForBlock($block)
    {
        global $injector;

        $indicator = $this->_params['id'] . '_loading_img';
        $injector->getInstance('Horde_PageOutput')->addInlineScript(
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
                        HordeCore.doAction("blockRefresh",
                            { blockid: "' . $block . '", location: v },
                            { callback: function(r) {
                                var point = v.split(",");
                                var p = { lat: point[0], lon: point[1] };
                                $("weathercontent' . $this->_params['instance'] . '").update(r);
                                $("' . $indicator . '").toggle();
                                WeatherBlockMap.maps["' . $this->_params['instance'] . '"].setCenter(p, 7);
                                }
                            }
                        );
                        this.value = false;
                    }
                }',
                '$("button' . $this->_params['instance'] . '").observe("click", function(e) {
                    window.weatherupdate["' . $this->_params['instance'] . '"].update();
                    e.stop();
                })'
            )
        );

        return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax(array(
            'minChars' => 3,
            'tokens' => array(),
            'domParent' => 'horde-content',
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
}
