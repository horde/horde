<?php
/**
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Trean
 */
class Trean_Ajax_Imple_TagAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     * Attach the Imple object to a javascript event.
     * If the 'pretty' parameter is empty then we want a
     * traditional autocompleter, otherwise we get a spiffy pretty one.
     *
     * @param array $js_params  See
     *                          Horde_Core_Ajax_Imple_AutoCompleter::_attach().
     *
     * @return array  See Horde_Core_Ajax_Imple_AutoCompleter::_attach().
     */
    protected function _attach($js_params)
    {
        $js_params['indicator'] = $this->_params['triggerId'] . '_loading_img';

        $ret = array(
            'params' => $js_params
        );

        if (empty($this->_params['pretty'])) {
            $ret['ajax'] = 'TagAutoCompleter';
        } else {
            $ret['pretty'] = 'TagAutoCompleter';
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

        $tagger = new Trean_Tagger();
        return array_values($tagger->listTags($input));
    }
}
