<?php
/**
 * Gollem application API.
 *
 * This file defines Gollem's external API interface. Other
 * applications can interact with Gollem through this API.
 *
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
 * @package Gollem
 */
class Gollem_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsHandle($item, $updated)
    {
        switch ($item) {
        case 'columnselect':
            $columns = Horde_Util::getFormData('columns');
            if (!empty($columns)) {
                $GLOBALS['prefs']->setValue('columns', $columns);
                return true;
            }
            break;
        }

        return $updated;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Gollem::getMenu();
    }

}
