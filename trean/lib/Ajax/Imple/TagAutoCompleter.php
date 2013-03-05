<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class Trean_Ajax_Imple_TagAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        $opts = array();

        foreach (array('box', 'triggerContainer', 'existing', 'boxClass') as $val) {
            if (isset($this->_params[$val])) {
                $opts[$val] = $this->_params[$val];
            }
        }
        return empty($this->_params['pretty'])
            ? new Horde_Core_Ajax_Imple_AutoCompleter_Ajax($opts)
            : new Horde_Core_Ajax_Imple_AutoCompleter_Pretty($opts);
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        $tagger = new Trean_Tagger();
        return array_values($tagger->listTags($input));
    }

}
