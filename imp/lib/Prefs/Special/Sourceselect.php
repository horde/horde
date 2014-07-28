<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'sourceselect' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_Sourceselect implements Horde_Core_Prefs_Ui_Special
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
        $contacts = $GLOBALS['injector']->getInstance('IMP_Contacts');
        return Horde_Core_Prefs_Ui_Widgets::addressbooks(array(
            'fields' => $contacts->fields,
            'sources' => $contacts->sources
        ));
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $prefs;

        $data = Horde_Core_Prefs_Ui_Widgets::addressbooksUpdate($ui);
        $updated = false;

        if (isset($data['sources'])) {
            $prefs->setValue('search_sources', $data['sources']);
            $updated = true;
        }

        if (isset($data['fields'])) {
            $prefs->setValue('search_fields', $data['fields']);
            $updated = true;
        }

        return $updated;
    }

}
