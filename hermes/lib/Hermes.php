<?php
/**
 * Hermes Base Class.
 *
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class Hermes
{
    /**
     * Get a list of available clients
     *
     * @param string $name  The string to search for in the client name
     *
     * @staticvar array $clients
     * @return array  A hash of client_id => client_name
     */
    public static function listClients($name = '')
    {
        static $clients;

        if (is_null($clients) || empty($clients[$name])) {
            try {
                $result = $GLOBALS['registry']->clients->searchClients(array($name), array('name'), true);
            } catch (Horde_Exception $e) {
                // No client backend
            }
            $client_name_field = $GLOBALS['conf']['client']['field'];
            $clients = is_null($clients) ?  array() : $clients;
            if (!empty($result)) {
                $result = $result[$name];
                foreach ($result as $client) {
                    $clients[$name][$client['id']] = $client[$client_name_field];
                }
            }
            if (!empty($clients[$name])) {
                uasort($clients[$name], 'strcoll');
            } else {
                $clients[$name] = array();
            }
        }

        return $clients[$name];
    }

    public static function getClientSelect($id)
    {
        $clients = self::listClients();
        $select = '<select name="client" id="' . $id . '">';
        $select .= '<option value="">' . _("--- Select A Client ---") . '</option>';
        foreach ($clients as $cid => $client) {
            $select .= '<option value="' . $cid . '">' . $client . '</option>';
        }

        return $select . '</select>';
    }

    /**
     * @TODO: Build these via ajax once we have UI support for editing jobtypes
     * @return <type>
     */
    public static function getJobTypeSelect($id)
    {
        $types = $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes(array('enabled' => true));
        $select = '<select name="type" id="' . $id . '">';
        foreach ($types as $tid => $type) {
            $select .= '<option value="' . $tid . '">' . $type['name'] . '</option>';
        }

        return $select . '</select>';
    }

    /**
     * Determines if the current user can edit a specific timeslice according to
     * the following rules: 'hermes:review' perms may edit any slice, the
     * current user can edit his/her own slice prior to submitting it. Otherwise
     * no editing allowed.
     *
     * @param <type> $id
     * @return <type>
     */
    public static function canEditTimeslice($id)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            return true;
        }

        $hours = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $id));
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
     * @param array $hours  This is an array of the results from
     *                      $driver->getHours().
     *
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
                $ident = $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($emp);
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
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        if (!$auth->hasCapability('list')) {
            return array('text', array());
        }
        try {
            $users = $auth->listUsers();
        } catch (Exception $e) {
            return array('invalid',
                         array(sprintf(_("An error occurred listing users: %s"), $e->getMessage())));
        }

        $employees = array();
        foreach ($users as $user) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
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

        throw new Horde_Exception_NotFound();
    }

    /**
     */
    public static function getCostObjectType($clientID = null)
    {
        global $registry;

        /* Check to see if any other active applications are exporting cost
         * objects to which we might want to bill our time. */
        $criteria = array('user'   => $GLOBALS['registry']->getAuth(),
                          'active' => true);
        if (!empty($clientID)) {
            $criteria['client_id'] = $clientID;
        }

        $costobjects = array();
        foreach ($registry->listApps() as $app) {
            if (!$registry->hasMethod('listCostObjects', $app)) {
                continue;
            }

            try {
                $result = $registry->callByPackage($app, 'listCostObjects', array($criteria));
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Error retrieving cost objects from \"%s\": %s"), $registry->get('name', $app), $e->getMessage()), 'horde.error');
                continue;
            }

            foreach (array_keys($result) as $catkey) {
                foreach (array_keys($result[$catkey]['objects']) as $okey){
                    $result[$catkey]['objects'][$okey]['id'] = $app . ':' .
                        $result[$catkey]['objects'][$okey]['id'];
                }
            }

            if ($app == $registry->getApp()) {
                $costobjects = array_merge($result, $costobjects);
            } else {
                $costobjects = array_merge($costobjects, $result);
            }
        }

        $elts = array('' => _("--- No Cost Object ---"));
        $counter = 0;
        foreach ($costobjects as $category) {
            Horde_Array::arraySort($category['objects'], 'name');
            $elts['category%' . $counter++] = sprintf('--- %s ---', $category['category']);
            foreach ($category['objects'] as $object) {
                $name = $object['name'];
                if (Horde_String::length($name) > 80) {
                    $name = Horde_String::substr($name, 0, 76) . ' ...';
                }

                $hours = 0.0;
                $filter = array('costobject' => $object['id']);
                if (!empty($GLOBALS['conf']['time']['sum_billable_only'])) {
                    $filter['billable'] = true;
                }
                $result = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($filter, array('hours'));
                foreach ($result as $entry) {
                    if (!empty($entry['hours'])) {
                        $hours += $entry['hours'];
                    }
                }

                /* Show summary of hours versus estimate for this
                 * deliverable. */
                if (empty($object['estimate'])) {
                    $name .= sprintf(_(" (%0.2f hours)"), $hours);
                } else {
                    $name .= sprintf(_(" (%d%%, %0.2f of %0.2f hours)"),
                                     (int)($hours / $object['estimate'] * 100),
                                     $hours, $object['estimate']);
                }

                $elts[$object['id']] = $name;
            }
        }

        return $elts;
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
            $GLOBALS['session']->set('hermes', 'search_mode', $mode);
        } elseif (!$GLOBALS['session']->exists('hermes', 'search_mode')) {
            $GLOBALS['session']->set('hermes', 'search_mode', 'summary');
        }
        return $tabs->render($GLOBALS['session']->get('hermes', 'search_mode'));
    }

    /**
     * Output everything for the AJAX interface up to but not including the
     * <body> tag.
     */
    public static function header()
    {
        // Need to include script files before we start output
        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('horde.js', 'horde');
        Horde::addScriptFile('growler.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');
        Horde::addScriptFile('tooltips.js', 'horde');
        Horde::addScriptFile('date/' . $datejs, 'horde');
        Horde::addScriptFile('date/date.js', 'horde');
        Horde::addScriptFile('quickfinder.js', 'horde');
        Horde::addScriptFile('hermes.js', 'hermes');
        Horde_Core_Ui_JsCalendar::init(array('short_weekdays' => true));

        if (isset($GLOBALS['language'])) {
            header('Content-type: text/html; charset=UTF-8');
            header('Vary: Accept-Language');
        }

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">' . "\n" .
             (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '"' : '<html') . ">\n".
             "<head>\n" .
             '<title>' . htmlspecialchars($GLOBALS['registry']->get('name')) . "</title>\n";

        Horde::includeFavicon();
        echo Horde::wrapInlineScript(self::includeJSVars());
        Horde::includeStylesheetFiles();

        echo "</head>\n";

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        echo Horde::endBuffer();
        flush();
    }

    public static function includeJSVars()
    {
        global $prefs, $registry;

        $hermes_webroot = $registry->get('webroot');
        $horde_webroot = $registry->get('webroot', 'horde');
        $has_tasks = $registry->hasInterface('tasks');
        $app_urls = array();
        if (isset($GLOBALS['conf']['menu']['apps']) &&
            is_array($GLOBALS['conf']['menu']['apps'])) {
            foreach ($GLOBALS['conf']['menu']['apps'] as $app) {
                $app_urls[$app] = (string)Horde::url($registry->getInitialPage($app), true)->add('ajaxui', 1);
            }
        }

        /* Variables used in core javascript files. */
        $code['conf'] = array(
            'URI_AJAX' => (string)Horde::getServiceLink('ajax', 'hermes'),
            'SESSION_ID' => defined('SID') ? SID : '',
            'images' => array(
                'timerlog' => (string)Horde_Themes::img('log.png'),
                'timerplay' => (string)Horde_Themes::img('play.png'),
                'timerpause' => (string)Horde_Themes::img('pause.png')
            ),
            'user' => $GLOBALS['registry']->convertUsername($GLOBALS['registry']->getAuth(), false),
            'prefs_url' => (string)Horde::getServiceLink('prefs', 'hermes')->setRaw(true)->add('ajaxui', 1),
            'app_urls' => $app_urls,
            'name' => $registry->get('name'),
            'login_view' => 'time',
            'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                             array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                             Horde_Nls::getLangInfo(D_FMT)),
            'client_name_field' => $GLOBALS['conf']['client']['field']
        );
        if (!empty($GLOBALS['conf']['logo']['link'])) {
            $code['conf']['URI_HOME'] = $GLOBALS['conf']['logo']['link'];
        }

        /* Gettext strings used in core javascript files. */
        $code['text'] = array(
            'ajax_error' => _("Error when communicating with the server."),
            'ajax_timeout' => _("There has been no contact with the server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
            'ajax_recover' => _("The connection to the server has been restored."),
            'noalerts' => _("No Notifications"),
            'alerts' => sprintf(_("%s notifications"), '#{count}'),
            'hidelog' => _("Hide Notifications"),
            'growlerinfo' => _("This is the notification backlog"),
            'more' => _("more..."),
            'prefs' => _("Preferences"),
            'wrong_auth' => _("The authentication information you specified wasn't accepted."),
            'fix_form_values' => _("Please enter correct values in the form first."),
        );

        return Horde::addInlineJsVars(array(
            'var Hermes' => $code
        ), array('ret_vars' => true));
    }

    /**
     * Returns whether to display the ajax view.
     *
     * return boolean  True if the ajax view should be displayed.
     */
    public static function showAjaxView()
    {
        global $prefs, $session;

        $mode = $session->get('horde', 'mode');
        return ($mode == 'dynamic' || ($prefs->getValue('dynamic_view') && $mode == 'auto')) && Horde::ajaxAvailable();
    }

    /**
     * Create a new timer and save it to storage.
     *
     * @param string $description  The timer description.
     *
     * @return integer  The timer id.
     */
    public static function newTimer($description)
    {
        $now = time();
        $timer = array(
            'name' => $description,
            'time' => $now,
            'paused' => false,
            'elapsed' => 0);

        self::updateTimer($now, $timer);

        return $now;
    }

    public static function getTimer($id)
    {
        global $prefs;

        $timers = $prefs->getValue('running_timers');
        if (!empty($timers)) {
            $timers = @unserialize($timers);
        } else {
            $timers = array();
        }

        if (empty($timers[$id])) {
            return false;
        }

        return $timers[$id];
    }

    public static function clearTimer($id)
    {
        global $prefs;

        $timers = @unserialize($prefs->getValue('running_timers'));
         if (!is_array($timers)) {
            $timers = array();
         } else {
            unset($timers[$id]);
        }
        $prefs->setValue('running_timers', serialize($timers));
    }

    public static function updateTimer($id, $timer)
    {
         global $prefs;

         $timers = @unserialize($prefs->getValue('running_timers'));
         if (!is_array($timers)) {
            $timers = array();
         }
         $timers[$id] = $timer;
         $prefs->setValue('running_timers', serialize($timers));
    }

}
