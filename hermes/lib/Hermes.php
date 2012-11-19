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
     * @param string $id      The DOM id to identify the select list.
     * @param boolean $multi  Allow multi select?
     *
     * @return string  The HTML to render the select element.
     */
    public static function getClientSelect($id, $multi = false)
    {
        $clients = self::listClients();
        $select = '<select name="'
            . ($multi ? 'client[]' : 'client')
            . '" id="' . $id . '" '
            . ($multi ? 'multiple = "multiple"' : '') . '>';
        $select .= '<option value="">' . _("--- Select A Client ---") . '</option>';
        foreach ($clients as $cid => $client) {
            $select .= '<option value="' . $cid . '">' . htmlspecialchars($client) . '</option>';
        }

        return $select . '</select>';
    }

    /**
     * Return HTML needed to build an enum or multienum for jobtype selection.
     * @TODO: Build these via ajax once we have UI support for editing jobtypes
     *
     * @param string $id      The DOM id to identify the select list.
     * @param boolean $multi  Allow multi select?
     *
     * @return string  The HTML needed to render the select element.
     */
    public static function getJobTypeSelect($id, $multi = false)
    {
        $types = $GLOBALS['injector']->getInstance('Hermes_Driver')
            ->listJobTypes(array('enabled' => true));
        $select = '<select name="'
            . ($multi ? 'type[]' : 'type')
            . '" id="' . $id . '" '
            . ($multi ? 'multiple="multiple"' : '') . '>';

        foreach ($types as $tid => $type) {
            $select .= '<option value="' . $tid . '">' . htmlspecialchars($type['name']) . '</option>';
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
     * Return data for costobjects, optionally filtered by client_ids.
     *
     * @param mixed $client_ids  A client id or an array of client ids to
     *                           filter cost obejcts by.
     *
     * @return array  An array of cost objects data.
     */
    public static function getCostObjectType($client_ids = null)
    {
        global $registry;

        // Check to see if any other active applications are exporting cost
        // objects to which we might want to bill our time.
        $criteria = array(
            'user'   => $GLOBALS['registry']->getAuth(),
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
            $timer['e'] = round((float)$elapsed / 3600, 2);
            $timer['id'] = $id;
            unset($timer['elapsed']);
            $return[] = $timer;
        }

        return $return;
    }

    /**
     * Create a new timer and save it to storage. Timers contain the following
     * values:
     *  - name: (string) The descriptive name of the timer.
     *  - time: (integer) Contains the timestamp of the last time this timer
     *          was started. Contains zero if paused.
     *  - paused: (boolean)  Flag to indicate the timer is paused.
     *  - elapsed: (integer) Total elapsed time since the timer was CREATED.
     *             Updated when timer is paused.
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
        } else {
            $timers = array();
        }

        if (empty($timers[$id])) {
            throw new Horde_Exception_NotFound(_("The requested timer was not found."));
        }

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

}
