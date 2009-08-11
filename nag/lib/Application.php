<?php
/**
 * Nag application API.
 *
 * @package Nag
 */
class Nag_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();
        $perms['tree']['nag']['max_tasks'] = false;
        $perms['title']['nag:max_tasks'] = _("Maximum Number of Tasks");
        $perms['type']['nag:max_tasks'] = 'int';

        return $perms;
    }

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
        case 'tasklistselect':
            $default_tasklist = Horde_Util::getFormData('default_tasklist');
            if (!is_null($default_tasklist)) {
                $tasklists = Nag::listTasklists();
                if (is_array($tasklists) &&
                    isset($tasklists[$default_tasklist])) {
                    $GLOBALS['prefs']->setValue('default_tasklist', $default_tasklist);
                    return true;
                }
            }
            break;

        case 'showsummaryselect':
            $GLOBALS['prefs']->setValue('summary_categories', Horde_Util::getFormData('summary_categories'));
            return true;

        case 'defaultduetimeselect':
            $GLOBALS['prefs']->setValue('default_due_time', Horde_Util::getFormData('default_due_time'));
            return true;
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
        return Nag::getMenu();
    }

}
