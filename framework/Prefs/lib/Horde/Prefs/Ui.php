<?php
/**
 * Class for auto-generating the preferences user interface and processing
 * the forms.
 *
 * Set $_SESSION['horde_prefs']['nomenu'] to true to suppress output of the
 * Horde_Menu on the options pages.
 *
 * For 'special' group types, set 'prefsui_no_save' to suppress printing of
 * the "Save Changes" and "Undo Changes" buttons.
 *
 * The following Application API callbacks are available:
 * prefsCallback($group) - TODO
 * prefsInit($group = '') - TODO
 * prefsMenu() - TODO
 * prefsSpecial($pref, $updated) - TODO
 * prefsSpecialGenerate($pref) - TODO
 * prefsStatus() - TODO
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Horde_Prefs
 */
class Horde_Prefs_Ui
{
    /**
     * Cache for groupIsEditable().
     *
     * @var array
     */
    static protected $_results = array();

    /**
     * Determine whether or not a preferences group is editable.
     *
     * @param string $group      The preferences group to check.
     * @param array $prefGroups  TODO
     *
     * @return boolean  Whether or not the group is editable.
     */
    static public function groupIsEditable($group, $prefGroups)
    {
        if (!isset(self::$_results[$group])) {
            if (!empty($prefGroups[$group]['url'])) {
                self::$_results[$group] = true;
            } else {
                self::$_results[$group] = false;
                if (isset($prefGroups[$group]['members'])) {
                    foreach ($prefGroups[$group]['members'] as $pref) {
                        if (!$GLOBALS['prefs']->isLocked($pref)) {
                            self::$_results[$group] = true;
                            break;
                        }
                    }
                }
            }
        }

        return self::$_results[$group];
    }

    /**
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     *
     * @param string $group      The preferences group that was edited.
     * @param object $save       The object where the changed values are
     *                           saved. Must implement setValue(string,
     *                           string).
     * @param string $app        The application name.
     * @param array $prefGroups  TODO
     * @param array $_prefs      TODO
     *
     * @return boolean  Whether preferences have been updated.
     */
    static public function handleForm($group, $save, $app, $prefGroups,
                                      $_prefs)
    {
        global $prefs;

        $notification = Horde_Notification::singleton();
        $registry = Horde_Registry::singleton();

        $updated = false;

        /* Run through the action handlers */
        if (self::groupIsEditable($group, $prefGroups)) {
            foreach ($prefGroups[$group]['members'] as $pref) {
                if (!$prefs->isLocked($pref) ||
                    ($_prefs[$pref]['type'] == 'special')) {
                    switch ($_prefs[$pref]['type']) {
                    case 'implicit':
                    case 'link':
                        /* These either aren't set or are set in other
                         * parts of the UI. */
                        break;

                    case 'password':
                    case 'select':
                    case 'text':
                    case 'textarea':
                        $updated = $updated | $save->setValue($pref, Horde_Util::getPost($pref));
                        break;

                    case 'enum':
                        $val = Horde_Util::getPost($pref);
                        if (isset($_prefs[$pref]['enum'][$val])) {
                            $updated = $updated | $save->setValue($pref, $val);
                        } else {
                            $notification->push(_("An illegal value was specified."), 'horde.error');
                        }
                        break;

                    case 'multienum':
                        $vals = Horde_Util::getPost($pref);
                        $set = array();
                        if (is_array($vals)) {
                            foreach ($vals as $val) {
                                if (isset($_prefs[$pref]['enum'][$val])) {
                                    $set[] = $val;
                                } else {
                                    $notification->push(_("An illegal value was specified."), 'horde.error');
                                    break 2;
                                }
                            }
                        }

                        $updated = $updated | $save->setValue($pref, @serialize($set));
                        break;

                    case 'number':
                        $num = Horde_Util::getPost($pref);
                        if ((string)(double)$num !== $num) {
                            $notification->push(_("This value must be a number."), 'horde.error');
                        } elseif (empty($num)) {
                            $notification->push(_("This number must be at least one."), 'horde.error');
                        } else {
                            $updated = $updated | $save->setValue($pref, $num);
                        }
                        break;

                    case 'checkbox':
                        $val = Horde_Util::getPost($pref);
                        $updated = $updated | $save->setValue($pref, isset($val) ? 1 : 0);
                        break;

                    case 'alarm':
                        $methods = Horde_Alarm::notificationMethods();
                        $value = array();
                        foreach (Horde_Util::getPost($pref, array()) as $method) {
                            $value[$method] = array();
                            if (!empty($methods[$method])) {
                                foreach (array_keys($methods[$method]) as $param) {
                                    $value[$method][$param] = Horde_Util::getPost($pref . '_' . $param, '');
                                    if (is_array($methods[$method][$param]) &&
                                        $methods[$method][$param]['required'] &&
                                        $value[$method][$param] === '') {
                                        $notification->push(sprintf(_("You must provide a setting for \"%s\"."), $methods[$method][$param]['desc']), 'horde.error');
                                        $updated = false;
                                        break 3;
                                    }
                                }
                            }
                        }
                        $updated = $updated | $save->setValue($pref, serialize($value));
                        break;

                    case 'special':
                        /* Code for special elements written specifically for
                         * each application. */
                        if ($registry->hasAppMethod($app, 'prefsSpecial')) {
                            $updated = $updated | $registry->callAppMethod($app, 'prefsSpecial', array('args' => array($pref, $updated)));
                        }
                        break;
                    }
                }
            }

            if (is_callable(array($save, 'verify'))) {
                $result = $save->verify();
                if ($result instanceof PEAR_Error) {
                    $notification->push($result, 'horde.error');
                    $updated = false;
                }
            }
        }

        if ($updated) {
            if ($registry->hasAppMethod($app, 'prefsCallback')) {
                $registry->callAppMethod($app, 'prefsCallback', array('args' => array($group)));
            }

            if ($prefs instanceof Horde_Prefs_Session) {
                $notification->push(_("Your options have been updated for the duration of this session."), 'horde.success');
            } else {
                $notification->push(_("Your options have been updated."), 'horde.success');
            }
        }

        return $updated;
    }

    /**
     * Generate the UI for the preferences interface, either for a
     * specific group, or the group selection interface.
     *
     * @param string $app        The application name.
     * @param array $prefGroups  TODO
     * @param array $_prefs      TODO
     * @param string $group      The group to generate the UI for.
     * @param boolean $chunk     Whether to only return the body part.
     */
    static public function generateUI($app, $prefGroups, $_prefs,
                                      $group = null, $chunk = false)
    {
        global $conf, $prefs;

        $browser = Horde_Browser::singleton();
        $notification = Horde_Notification::singleton();
        $registry = Horde_Registry::singleton();

        /* Check if any options are actually available. */
        if (is_null($prefGroups)) {
            $notification->push(_("There are no options available."), 'horde.message');
        }

        /* Assign variables to hold select lists. */
        if (!$prefs->isLocked('language')) {
            $GLOBALS['language_options'] = Horde_Nls::$config['languages'];
            array_unshift($GLOBALS['language_options'], _("Default"));
        }

        $columns = array();
        $in_group = (!empty($group) && self::groupIsEditable($group, $prefGroups) && !empty($prefGroups[$group]['members']));

        /* We need to do this check up here because it is possible that
         * we will generate a notification object, which is handled by
         * generateHeader. */
        if (!$in_group && is_array($prefGroups)) {
            foreach ($prefGroups as $key => $val) {
                if (self::groupIsEditable($key, $prefGroups)) {
                    $col = $val['column'];
                    unset($val['column']);
                    $columns[$col][$key] = $val;
                }
            }
            if (!count($columns)) {
                $notification->push(_("There are no options available."), 'horde.message');
            }
        }

        self::generateHeader($app, $prefGroups, $group, $chunk);

        if ($in_group) {
            foreach ($prefGroups[$group]['members'] as $pref) {
                if (!$prefs->isLocked($pref)) {
                    /* Get the help link. */
                    $helplink = empty($_prefs[$pref]['help'])
                        ? null
                        : Horde_Help::link(!empty($_prefs[$pref]['shared']) ? 'horde' : $registry->getApp(), $_prefs[$pref]['help']);

                    switch ($_prefs[$pref]['type']) {
                    case 'implicit':
                        break;

                    case 'special':
                        if (!$registry->hasAppMethod($app, 'prefsSpecialGenerate') ||
                            $registry->callAppMethod($app, 'prefsSpecialGenerate', array('args' => array($pref)))) {
                            require $registry->get('templates', empty($_prefs[$pref]['shared']) ? $registry->getApp() : 'horde') . '/prefs/' . $pref . '.inc';
                        }
                        break;

                    default:
                        require $registry->get('templates', 'horde') . '/prefs/' . $_prefs[$pref]['type'] . '.inc';
                        break;
                    }
                }
            }
            require $registry->get('templates', 'horde') . '/prefs/end.inc';
        } elseif (count($columns)) {
            $span = round(100 / count($columns));
            require $registry->get('templates', 'horde') . '/prefs/overview.inc';
        }
    }

    /**
     * Generates the the full header of a preference screen including
     * menu and navigation bars.
     *
     * @param string $app        The application name.
     * @param array $prefGroups  TODO
     * @param string $group      The group to generate the header for.
     * @param boolean $chunk     Whether to only return the body part.
     */
    static public function generateHeader($app, $prefGroups = null,
                                          $group = null, $chunk = false)
    {
        global $perms, $prefs;

        $notification = Horde_Notification::singleton();
        $registry = Horde_Registry::singleton();

        $title = _("User Options");
        if ($group == 'identities' && !$prefs->isLocked('default_identity')) {
            $notification->push('newChoice()', 'javascript');
        }
        $GLOBALS['bodyId'] = 'services_prefs';
        if (!$chunk) {
            require $registry->get('templates', $app) . '/common-header.inc';

            if (empty($_SESSION['horde_prefs']['nomenu'])) {
                if ($registry->hasAppMethod($app, 'prefsMenu')) {
                    $menu = $registry->callAppMethod($app, 'prefsMenu');
                }
                require $registry->get('templates', 'horde') . '/menu/menu.inc';
            }

            $notification->notify(array('listeners' => 'status'));
        }

        /* Get list of accessible applications. */
        $apps = array();
        foreach ($registry->applications as $application => $params) {
            // Make sure the app is installed and has a prefs file.
            if (!file_exists($registry->get('fileroot', $application) . '/config/prefs.php')) {
                continue;
            }

            if ($params['status'] == 'heading' ||
                $params['status'] == 'block') {
                continue;
            }

            /* Check if the current user has permisson to see this
             * application, and if the application is active.
             * Administrators always see all applications. */
            if ((Horde_Auth::isAdmin() && $params['status'] != 'inactive') ||
                ($registry->hasPermission($application) &&
                 ($params['status'] == 'active' || $params['status'] == 'notoolbar'))) {
                $apps[$application] = _($params['name']);
            }
        }
        asort($apps);

        /* Show the current application and a form for switching
         * applications. */
        require $registry->get('templates', 'horde') . '/prefs/app.inc';

        if (is_null($prefGroups)) {
            extract(Horde::loadConfiguration('prefs.php', array('prefGroups'), $app));
        }

        /* If there's only one prefGroup, just show it. */
        if (empty($group) && count($prefGroups) == 1) {
            $group = array_keys($prefGroups);
            $group = array_pop($group);
        }

        if (!empty($group) && self::groupIsEditable($group, $prefGroups)) {
            require $registry->get('templates', 'horde') . '/prefs/begin.inc';
        }
    }

    /**
     * Generate the content of the title bar navigation cell (previous | next
     * option group).
     *
     * @param string $group  Current option group.
     */
    static public function generateNavigationCell($app, $group)
    {
        $registry = Horde_Registry::singleton();

        // Search for previous and next groups.
        $first = $last = $next = $previous = null;
        $finish = $found = false;

        extract(Horde::loadConfiguration('prefs.php', array('prefGroups'), $app));

        foreach ($prefGroups as $pgroup => $gval) {
            if (self::groupIsEditable($pgroup, $prefGroups)) {
                if (!$first) {
                    $first = $pgroup;
                }
                if (!$found) {
                    if ($pgroup == $group) {
                        $previous = $last;
                        $found = true;
                    }
                } elseif (!$finish) {
                    $finish = true;
                    $next = $pgroup;
                }
                $last = $pgroup;
            }
        }

        if (!$previous) {
            $previous = $last;
        }

        if (!$next) {
            $next = $first;
        }

        /* Don't loop if there's only one group. */
        if ($next == $previous) {
            return;
        }

        echo '<ul><li>' .
             Horde::link(Horde_Util::addParameter(Horde::url($registry->get('webroot', 'horde') . '/services/prefs.php'), array('app' => $app, 'group' => $previous), _("Previous options"))) .
             '&lt;&lt; ' . $prefGroups[$previous]['label'] .
             '</a>&nbsp;|&nbsp;' .
             Horde::link(Horde_Util::addParameter(Horde::url($registry->get('webroot', 'horde') . '/services/prefs.php'), array('app' => $app, 'group' => $next), _("Next options"))) .
             $prefGroups[$next]['label'] . ' &gt;&gt;' .
             '</a></li></ul>';
    }

    /**
     * Get the default application to show preferences for. Defaults
     * to 'horde'.
     */
    static public function getDefaultApp()
    {
        $applications = $GLOBALS['registry']->listApps(null, true, Horde_Perms::READ);
        return isset($applications['horde'])
            ? 'horde'
            : array_shift($applications);
    }

}
