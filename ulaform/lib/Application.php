<?php
/**
 * Ulaform internal API interface.
 *
 * This file defines Ulaform's internal API interface. Other applications can
 * interact with Ulaform through this API.
 *
 * $Horde: ulaform/lib/Application.php,v 1.1 2009-10-01 09:32:22 jan Exp $
 *
 * @package Ulaform
 */
class Ulaform_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-cvs)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/base.php';

        $perms['tree']['ulaform']['form'] = false;
        $perms['title']['ulaform:form'] = _("Form");

        foreach ($GLOBALS['ulaform_driver']->getAvailableForms() as $form) {
            $perms['tree']['ulaform']['form'][$form['form_id']] = false;
            $perms['title']['ulaform:form:' . $form['form_id']] = $form['form_name'];
        }

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ulaform::getMenu();
    }

}
