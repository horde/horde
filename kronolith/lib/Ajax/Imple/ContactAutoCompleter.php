<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Kronolith
 * @license  http://www.horde.org/licenses/gpl GPL
 */
class Kronolith_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_ContactAutoCompleter
{
    /**
     */
    protected function _attach($js_params)
    {
        $ret = parent::_attach($js_params);

        if (isset($this->_params['onAdd'])) {
            $ret['raw_params']['onAdd'] = $this->_params['onAdd'];
            $ret['raw_params']['onRemove'] = $this->_params['onRemove'];
        }

        if (!empty($this->_params['pretty'])) {
            unset($ret['ajax']);
            $ret['pretty'] = 'ContactAutoCompleter';
        }

        if (!empty($this->_params['var'])) {
            $ret['var'] = $this->_params['var'];
        }

        return $ret;
    }

    /**
     */
    protected function _getAddressbookSearchParams()
    {
        $params = Kronolith::getAddressbookSearchParams();

        $ob = new stdClass;
        $ob->fields = $params['fields'];
        $ob->sources = $params['sources'];

        return $ob;
    }

}
