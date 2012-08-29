<?php
/**
 * Imple to attach the resource autocompleter to a HTML element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Ajax_Imple_ResourceAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    /**
     */
    protected function _getAutoCompleter()
    {
        $opts = array();

        foreach (array('box', 'onAdd', 'onRemove', 'triggerContainer') as $val) {
            if (isset($this->_params[$val])) {
                $opts[$val] = $this->_params[$val];
            }
        }

        if (empty($this->_params['pretty'])) {
            return new Horde_Core_Ajax_Imple_AutoCompleter_Ajax($opts);
        }

        $opts['filterCallback'] = <<<EOT
function(c) {
   if (!c) {
       return [];
   }

   var r = [];
   KronolithCore.resourceACCache.choices = c;
   c.each(function(i) {
       r.push(i.name);
   });
   return r;
}
EOT;
        $opts['requireSelection'] = true;

        return new Horde_Core_Ajax_Imple_AutoCompleter_Pretty($opts);
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        $ret = array();

        // For now, return all resources.
        $resources = Kronolith::getDriver('Resource')->listResources(Horde_Perms::READ, array(), 'name');
        foreach ($resources as $r) {
            if (strpos(Horde_String::lower($r->get('name')), Horde_String::lower($input)) !== false) {
                $ret[] = array(
                    'name' => $r->get('name'),
                    'code' => $r->getId());
            }
        }

        return $ret;
    }

}
