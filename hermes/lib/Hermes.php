<?php
/**
 * Hermes Base Class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class Hermes {

    public static function &getDriver()
    {
        global $conf;

        require_once HERMES_BASE . '/lib/Driver.php';
        return Hermes_Driver::singleton();
    }

    public static function listClients()
    {
        static $clients;

        if (is_null($clients)) {
            $result = $GLOBALS['registry']->call('clients/searchClients', array(array('')));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }

            $client_name_field = $GLOBALS['conf']['client']['field'];
            $clients = array();
            if (!empty($result)) {
                $result = $result[''];
                foreach ($result as $client) {
                    $clients[$client['id']] = $client[$client_name_field];
                }
            }

            uasort($clients, 'strcoll');
        }

        return $clients;
    }

    public static function getDayOfWeek($timestamp)
    {
        // Return 0-6, indicating the position of $timestamp in the
        // period.
        $dow = 7 + date('w', $timestamp) - $GLOBALS['prefs']->getValue('start_of_week');
        return ($dow % 7);
    }

    /**
     * Build Hermes' list of menu items.
     */
    public static function getMenu($returnType = 'object')
    {
        global $registry, $conf, $print_link;

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        $menu = new Horde_Menu();
        $menu->add(Horde::url('time.php'), _("My _Time"), 'hermes.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::url('entry.php'), _("_New Time"), 'hermes.png', null, null, null, Horde_Util::getFormData('id') ? '__noselection' : null);
        $menu->add(Horde::url('search.php'), _("_Search"), Horde_Themes::img('search.png'));

        if ($conf['time']['deliverables'] && $registry->isAdmin('hermes:deliverables')) {
            $menu->add(Horde::url('deliverables.php'), _("_Deliverables"), 'hermes.png');
        }

        if ($conf['invoices']['driver'] && $registry->isAdmin('hermes:invoicing')) {
            $menu->add(Horde::url('invoicing.php'), _("_Invoicing"), 'invoices.png');
        }

        /* Print. */
        if ($conf['menu']['print'] && isset($print_link)) {
            $menu->add($print_link, _("_Print"), Horde_Themes::img('print.png'), '_blank', 'popup(this.href); return false;', '__noselection');
        }

        /* Administration. */
        if ($registry->isAdmin()) {
            $menu->add(Horde::url('admin.php'), _("_Admin"), 'hermes.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    public static function canEditTimeslice($id)
    {
        $hermes = $GLOBALS['registry']->getApiInstance('hermes', 'application');
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            return true;
        }

        $hours = $hermes->driver->getHours(array('id' => $id));
        if (!is_array($hours) || count($hours) != 1) {
            return false;
        }
        $slice = $hours[0];

        // We can edit our own time if it hasn't been submitted.
        if ($slice['employee'] == $GLOBALS['registry']->getAuth() && !$slice['submitted']) {
            return true;
        }

        return false;
    }

    /**
     * Rewrite an hours array into a format useable by Horde_Data::
     *
     * @param array $hours          This is an array of the results from
     *                              $hermes->driver->getHours().
     * @return array an array suitable for Horde_Data::
     */
    public static function makeExportHours($hours)
    {
        if (is_null($hours)) {
            return null;
        }

        $clients = Hermes::listClients();
        $namecache = array();
        for ($i = 0; $i < count($hours); $i++) {
            $timeentry = &$hours[$i];

            $timeentry['item'] = $timeentry['_type_name'];
            if (isset($clients[$timeentry['client']])) {
                $timeentry['client'] = $clients[$timeentry['client']];
            }

            $emp = &$timeentry['employee'];
            if (isset($namecache[$emp])) {
                $emp = $namecache[$emp];
            } else {
                $ident = $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($emp);
                $fullname = $ident->getValue('fullname');
                if ($fullname) {
                    $namecache[$emp] = $emp = $fullname;
                } else {
                    $namecache[$emp] = $emp;
                }
            }
        }

        return $hours;
    }

    /**
     * Get form control type for users.
     *
     * What type of control we use depends on whether the Auth driver has list
     * capability.
     *
     * @param string $enumtype  The type to return if we have list capability
     *                          (should be either 'enum' or 'multienum').
     *
     * @return array A two-element array of the type and the type's parameters.
     */
    public static function getEmployeesType($enumtype = 'multienum')
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth();
        if (!$auth->hasCapability('list')) {
            return array('text', array());
        }
        $users = $auth->listUsers();
        if (is_a($users, 'PEAR_Error')) {
            return array('invalid',
                         array(sprintf(_("An error occurred listing users: %s"),
                                       $users->getMessage())));
        }

        $employees = array();
        foreach ($users as $user) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($user);
            $label = $identity->getValue('fullname');
            if (empty($label)) {
                $label = $user;
            }
            $employees[$user] = $label;
        }

        return array($enumtype, array($employees));
    }

    public static function getCostObjectByID($id)
    {
        static $cost_objects;

        if (strpos($id, ':') !== false) {
            list($app, $app_id) = explode(':', $id, 2);

            if (!isset($cost_objects[$app])) {
                $results = $GLOBALS['registry']->callByPackage($app, 'listCostObjects', array(array()));
                if (is_a($results, 'PEAR_Error')) {
                    return $results;
                }
                $cost_objects[$app] = $results;
            }

            foreach (array_keys($cost_objects[$app]) as $catkey) {
                foreach (array_keys($cost_objects[$app][$catkey]['objects']) as $objkey) {
                    if ($cost_objects[$app][$catkey]['objects'][$objkey]['id'] == $app_id) {
                        return $cost_objects[$app][$catkey]['objects'][$objkey];
                    }
                }
            }
        }

        return PEAR::raiseError(_("Not found."));
    }

    public static function tabs()
    {
        /* Build search mode tabs. */
        $sUrl = Horde::selfUrl();
        $tabs = new Horde_Core_Ui_Tabs('search_mode', Horde_Variables::getDefaultVariables());
        $tabs->addTab(_("Summary"), $sUrl, 'summary');
        $tabs->addTab(_("By Date"), $sUrl, 'date');
        $tabs->addTab(_("By Employee"), $sUrl, 'employee');
        $tabs->addTab(_("By Client"), $sUrl, 'client');
        $tabs->addTab(_("By Job Type"), $sUrl, 'jobtype');
        $tabs->addTab(_("By Cost Object"), $sUrl, 'costobject');
        if ($mode = Horde_Util::getFormData('search_mode')) {
            $_SESSION['hermes_search_mode'] = $mode;
        }
        if (!isset($_SESSION['hermes_search_mode'])) {
            $_SESSION['hermes_search_mode'] = 'summary';
        }
        return $tabs->render($_SESSION['hermes_search_mode']);
    }

}
