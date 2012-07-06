<?php
/**
 * Special prefs handling for the 'addressbook' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Prefs_Special_Addressbook implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $cfgSources;

        $order = Turba::getAddressBookOrder();
        $selected = $sorted = $unselected = array();

        foreach (array_keys($cfgSources) as $val) {
            if (isset($order[$val])) {
                $sorted[intval($order[$val])] = $val;
            } else {
                $unselected[$val] = $cfgSources[$val]['title'];
            }
        }
        ksort($sorted);

        foreach ($sorted as $val) {
            $selected[$val] = $cfgSources[$val]['title'];
        }

        return Horde_Core_Prefs_Ui_Widgets::source(array(
            'mainlabel' => _("Choose which address books to display, and in what order:"),
            'selectlabel' => _("These address books will display in this order:"),
            'sources' => array(array(
                'selected' => $selected,
                'unselected' => $unselected
            )),
            'unselectlabel' => _("Address books that will not be displayed:")
        ));
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $prefs;

        $data = Horde_Core_Prefs_Ui_Widgets::sourceUpdate($ui);
        if (!isset($data['sources'])) {
            return false;
        }

        $prefs->setValue('addressbooks', $data['sources']);
        return true;
    }

}
