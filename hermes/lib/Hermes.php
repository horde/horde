<?php
/**
 * Hermes Base Class.
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
     * List of available clients.
     *
     * @var array
     */
    protected static $_clients = array();

    /**
     * Returns a list of available clients.
     *
     * @param string $name  The string to search for in the client name.
     *
     * @return array  A hash of client_id => client_name.
     */
    public static function listClients($name = '')
    {
        if (isset(self::$_clients['name'])) {
            return self::$_clients['name'];
        }

        self::$_clients[$name] = array();

        try {
            $result = $GLOBALS['registry']->clients->searchClients(array($name), array('name'), true);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'WARN');
            return self::$_clients[$name];
        }

        if (!empty($result)) {
            $result = $result[$name];
            foreach ($result as $client) {
                self::$_clients[$name][$client['id']] =
                    $client[$GLOBALS['conf']['client']['field']];
            }
        }
        uasort(self::$_clients[$name], 'strcoll');

        return self::$_clients[$name];
    }

    /**
     * Return the HTML needed to build an enum or multienum for selecting
     * clients.
     *
     * @param string $id           The DOM id to identify the select list.
     * @param boolean $multi       Allow multi select?
     * @param boolean $use_cotext  Instead of 'Select A Client', use 'General
     *                             Cost Objects' for the top choice.
     *
     * @return string  The HTML to render the select element.
     */
    public static function getClientSelect($id, $multi = false, $use_cotext = false)
    {
        $clients = self::listClients();
        $select = '<select name="'
            . ($multi ? 'client[]' : 'client')
            . '" id="' . $id . '" '
            . ($multi ? 'multiple = "multiple"' : '') . '>';
        $select .= '<option value="">';
        if ($use_cotext) {
            $select .= _("--- General Cost Objects ---");
        } else {
            $select .= _("--- Select A Client ---");
        }
        $select .= '</option>';

        foreach ($clients as $cid => $client) {
            $select .= '<option value="' . $cid . '">' . htmlspecialchars($client) . '</option>';
        }

        return $select . '</select>';
    }

    /**
     * Return HTML needed to build an enum or multienum for jobtype selection.
     *
     * @param string $id      The DOM id to identify the select list.
     * @param boolean $multi  Allow multi select?
     *
     * @return string  The HTML needed to render the select element.
     */
    public static function getJobTypeSelect($id, $multi = false, $show_disabled = false)
    {
        if ($show_disabled) {
            $params = array();
        } else {
            $params = array('enabled' => true);
        }
        $types = self::getJobTypeData($params);
        $select = '<select name="'
            . ($multi ? 'type[]' : 'type')
            . '" id="' . $id . '" '
            . ($multi ? 'multiple="multiple"' : '') . '>';
        $select .= '<option value="">' . _("--- Select a Job Type ---") . '</option>';
        foreach ($types as $tid => $type) {
            $select .= '<option value="' . $tid . '">' . htmlspecialchars($type['name']) . '</option>';
        }

        return $select . '</select>';
    }

    public static function getJobTypeData($params = array())
    {
        return $GLOBALS['injector']->getInstance('Hermes_Driver')
            ->listJobTypes($params);
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
        $results = array();
        for ($i = 0; $i < count($hours); $i++) {
            $timeentry = $hours[$i]->toArray();
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
            $results[] = $timeentry;
        }

        return $results;
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
                         sprintf(_("An error occurred listing users: %s"), $e->getMessage()));
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

        return array($enumtype, $employees);
    }

    /**
     * Return a cost object hash.
     *
     * @param string $id  The cost object id.
     *
     * @return array  The cost object hash. Keys differ depending on the
     *                API queried, but should always contain:
     *                  - id:
     *                  - name:
     * @throws Horde_ExceptionNotFound
     */
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
     * Return a list of cost objects exported by available APIs, optionally
     * filtered by client_ids.
     *
     */
    public static function getCostObjects($client_ids = null, $external_only = false)
    {
       global $registry;

        $criteria = array(
            'user'   => $registry->getAuth(),
            'active' => true
        );
        if (empty($client_ids)) {
            $client_ids = array('');
        } elseif (!is_array($client_ids)) {
            $client_ids = array($client_ids);
        }

        $costobjects = array();
        foreach ($client_ids as $client_id) {
            $criteria['client_id'] = $client_id;
            foreach ($registry->listApps() as $app) {
                if (($external_only && $app == 'hermes') ||
                    !$registry->hasMethod('listCostObjects', $app)) {
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
        }

        return $costobjects;
    }

    /**
     * Return data for costobjects, optionally filtered by client_ids.
     *
     * @param mixed $client_ids  A client id or an array of client ids to
     *                           filter cost obejcts by.
     *
     * @return array  An array of cost objects data.
     */
    public static function getCostObjectType($client_ids = null)
    {
        $elts = array('' => _("--- No Cost Object ---"));
        $counter = 0;
        foreach (self::getCostObjects($client_ids) as $category) {
            Horde_Array::arraySort($category['objects'], 'name');
            $elts['category%' . $counter++] = sprintf('--- %s ---', $category['category']);
            foreach ($category['objects'] as $object) {
                $name = Horde_String::truncate($object['name'], 80);
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
     * Return list of current timers.
     *
     * @param boolean $running_only  Only return running timers if true.
     *
     * @return array  An array of timer hashes.
     */
    public static function listTimers($running_only = false)
    {
        $timers = $GLOBALS['prefs']->getValue('running_timers');
        if (!empty($timers)) {
            $timers = @unserialize($timers);
        } else {
            $timers = array();
        }
        $return = array();
        foreach ($timers as $id => $timer) {
            if ($running_only && $timer['paused']) {
                continue;
            }
            $elapsed = (!$timer['paused'] ? time() - $timer['time'] : 0 ) + $timer['elapsed'];
            $timer['elapsed'] = round((float)$elapsed / 3600, 2);
            $timer['id'] = $id;
            try {
                $text = Hermes::getCostObjectByID($timer['deliverable_id']);
                $timer['deliverable_text'] = $text['name'];
            } catch (Horde_Exception_NotFound $e) {}
            $return[] = $timer;
        }

        return $return;
    }

    /**
     * Create a new timer and save it to storage. Timers contain the following
     * values:
     *  - name: (string) The descriptive name of the timer.
     *  - client_id:
     *  - deliverable_id:
     *  - jobtype_id:
     *  - time: (integer) Contains the timestamp of the last time this timer
     *          was started. Contains zero if paused.
     *  - paused: (boolean)  Flag to indicate the timer is paused.
     *  - elapsed: (integer) Total elapsed time since the timer was CREATED.
     *             Updated when timer is paused.
     *
     * @param string $description  The timer description.
     * @param stdClass $details    Additional, optional details for the ti.
     *
     * @return integer  The timer id.
     */
    public static function newTimer($description, stdClass $details = null)
    {
        $now = time();
        $timer = array(
            'name' => $description,
            'client_id' => empty($details->client_id) ? null : $details->client_id,
            'deliverable_id' => empty($details->deliverable_id) ? null : $details->deliverable_id,
            'jobtype_id' => empty($details->jobtype_id) ? null : $details->jobtype_id,
            'time' => $now,
            'paused' => false,
            'elapsed' => 0);

        self::updateTimer($now, $timer);

        return $now;
    }

    /**
     * Return a specific timer.
     *
     * @param integer  The timer id.
     *
     * @return array  The timer hash.
     * @throws Horde_Exception_NotFound
     */
    public static function getTimer($id)
    {
        $timers = $GLOBALS['prefs']->getValue('running_timers');
        if (!empty($timers)) {
            $timers = @unserialize($timers);
        }
        if (empty($timers[$id])) {
            throw new Horde_Exception_NotFound(_("The requested timer was not found."));
        }
        $timers[$id]['id'] = $id;

        try {
            $text = Hermes::getCostObjectByID($timers[$id]['deliverable_id']);
            $timers[$id]['deliverable_text'] = $text['name'];
        } catch (Horde_Exception_NotFound $e) {}

        return $timers[$id];
    }

    /**
     * Clear a timer
     *
     * @param integer $id  The timer id to clear/remove.
     */
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

    /**
     * Update an existing timer.
     *
     * @param integer $id   The timer id.
     * @param array $timer  The timer hash.
     */
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

    /**
     * Returns true if we are showing the Ajax view.
     *
     * @return boolean
     */
    public static function showAjaxView()
    {
        return $GLOBALS['registry']->getView() == Horde_Registry::VIEW_DYNAMIC && $GLOBALS['prefs']->getValue('dynamic_view');
    }

    /**
     * Return a URL to a specific view, taking self::showAjaxView() into account
     *
     * @param string $view   The view to link to.
     * @param array $params  Optional paramaters.
     *   - id: A slice id.
     *
     * @return Horde_Url  The Url
     */
    public static function url($view, array $params = array())
    {
        if (self::showAjaxView()) {
            // For ajax view 'entry' is done on the 'time' view.
            if ($view == 'entry') {
                $view = 'time';
            }
            $url = Horde::url('')->setAnchor($view . (!empty($params['id']) ? ':' . $params['id'] : ''));
        } else {
            $url = Horde::url($view . '.php');
            if (!empty($params['id'])) {
                $url->add('id', $params['id']);
            }
        }

        return $url;
    }

    /**
     * Parses a complete date-time string into a Horde_Date object.
     *
     * @param string $date       The date-time string to parse.
     *
     * @return Horde_Date  The parsed date.
     * @throws Horde_Date_Exception
     */
    static public function parseDate($date)
    {
        // strptime() is not available on Windows.
        if (!function_exists('strptime')) {
            return new Horde_Date($date);
        }

        // strptime() is locale dependent, i.e. %p is not always matching
        // AM/PM. Set the locale to C to workaround this, but grab the
        // locale's D_FMT before that.
        $format = Horde_Nls::getLangInfo(D_FMT);
        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        // Try exact format match first.
        $date_arr = strptime($date, $format);
        setlocale(LC_TIME, $old_locale);

        if (!$date_arr) {
            // Try with locale dependent parsing next.
            $date_arr = strptime($date, $format);
            if (!$date_arr) {
                // Try throwing at Horde_Date finally.
                return new Horde_Date($date);
            }
        }

        return new Horde_Date(
            array(
                'year'  => $date_arr['tm_year'] + 1900,
                'month' => $date_arr['tm_mon'] + 1,
                'mday'  => $date_arr['tm_mday'],
                'hour'  => $date_arr['tm_hour'],
                'min'   => $date_arr['tm_min'],
                'sec'   => $date_arr['tm_sec']
            )
        );
    }

}