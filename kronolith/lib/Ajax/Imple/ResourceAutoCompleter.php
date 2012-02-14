<?php
/**
 * Attach the resource auto completer to a javascript element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Ajax_Imple_ResourceAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     * Attach the Imple object to a javascript event.
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
            'params' => $js_params,
            'raw_params' => array(
                'filterCallback' => 'function(c) {
                    if (c) {
                        KronolithCore.resourceACCache.choices = c;
                        var r = [];
                        c.each(function(i) {
                            r.push(i.name);
                        });
                        return r;
                    } else {
                        return [];
                    }
                }'
        ));

        if (isset($this->_params['onAdd'])) {
            $ret['raw_params']['onAdd'] = $this->_params['onAdd'];
            $ret['raw_params']['onRemove'] = $this->_params['onRemove'];
        }
        $ret['raw_params']['requireSelection'] = true;
        if (empty($this->_params['pretty'])) {
            $ret['ajax'] = 'ResourceAutoCompleter';
        } else {
            $ret['pretty'] = 'ResourceAutoCompleter';
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
        $return = array();
        // For now, return all resources.
        $resources = Kronolith::getDriver('Resource')
            ->listResources(Horde_Perms::READ, array(), 'name');
        foreach ($resources as $r) {
            if (strpos(Horde_String::lower($r->get('name')), Horde_String::lower($input)) !== false) {
                $return[] = array(
                    'name' => $r->get('name'),
                    'code' => $r->getId());
            }
        }

        return $return;
    }

}
