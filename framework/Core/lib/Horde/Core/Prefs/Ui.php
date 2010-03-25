<?php
/**
 * Class for generating/processing the preferences UI.
 *
 * See Horde_Registry_Application:: for a summary of the API callbacks that
 * are available.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Prefs_Ui
{
    /**
     * Preferences groups.
     *
     * @var array
     */
    public $prefGroups = array();

    /**
     * Preferences.
     *
     * @var array
     */
    public $prefs = array();

    /**
     * Data overrides (for 'enum' and 'multienum' types).
     *
     * @var array
     */
    public $override = array();

    /**
     * Suppressed preference entries.
     *
     * @var array
     */
    public $suppress = array();

    /**
     * Suppressed prefGroup entries.
     *
     * @var array
     */
    public $suppressGroups = array();

    /**
     * Current application.
     *
     * @var string
     */
    public $app;

    /**
     * Current preferences group.
     *
     * @var string
     */
    public $group;

    /**
     * Form variables for this page load.
     *
     * @var Horde_Variables
     */
    public $vars;

    /**
     * If set, suppresses display of the buttons.
     *
     * @var boolean
     */
    public $nobuttons = false;

    /**
     * Constructor.
     *
     * @param Horde_Variables $vars  Form variables.
     */
    public function __construct($vars)
    {
        $this->app = isset($vars->app)
            ? $vars->app
            : $this->getDefaultApp();
        $this->group = $vars->group;
        $this->vars = $vars;

        /* Load the application's base environment. */
        $GLOBALS['registry']->pushApp($this->app);

        /* Load preferences. */
        $this->_loadPrefs($this->app);
    }

    /**
     * Hide the menu display for prefs UI pages during this session?
     *
     * @param boolean $hide  If true, hides the menu.
     */
    static public function hideMenu($hide)
    {
        $_SESSION['horde_prefs']['nomenu'] = $hide;
    }

    /**
     * Determine whether or not a preferences group is editable.
     *
     * @param string $group  The preferences group to check.
     *
     * @return boolean  Whether or not the group is editable.
     */
    public function groupIsEditable($group)
    {
        return (bool)count($this->getChangeablePrefs($group));
    }

    /**
     * Returns the list of changeable prefs for a group.
     *
     * @param string $group      The preferences group to check.
     * @param boolean $implicit  Don't add to list if marked as implict?
     *
     * @return array  The list of changeable prefs.
     */
    public function getChangeablePrefs($group, $implicit = false)
    {
        $prefs = array();

        foreach ($this->prefGroups[$group]['members'] as $pref) {
            if (!$GLOBALS['prefs']->isLocked($pref) &&
                !in_array($pref, $this->suppress) &&
                (!$implicit ||
                 (!empty($this->prefs[$pref]['type']) &&
                  ($this->prefs[$pref]['type'] != 'implicit')))) {
                $prefs[] = $pref;
            }
        }

        return $prefs;
    }

    /**
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     *
     * @return boolean  Whether preferences have been updated.
     */
    public function handleForm()
    {
        if (!$this->group || !$this->groupIsEditable($this->group)) {
            return false;
        }

        if (isset($this->vars->prefs_return)) {
            $this->group = $this->vars->actionID = '';
            return false;
        }

        switch ($this->vars->actionID) {
        case 'update_prefs':
            return $this->_handleForm($this->getChangeablePrefs($this->group));

        case 'update_special':
            $special = array();
            foreach ($this->getChangeablePrefs($this->group, true) as $pref) {
                if ($this->prefs[$pref]['type'] == 'special') {
                    $special[] = $pref;
                }
            }
            return $this->_handleForm($special);
        }

        return false;
    }

    /*
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     *
     * @param array $preflist  The list of preferences to process.
     *
     * @return boolean  Whether preferences have been updated.
     */
    protected function _handleForm($preflist)
    {
        global $notification, $prefs, $registry;

        $updated = false;

        /* Run through the action handlers */
        foreach ($preflist as $pref) {
            switch ($this->prefs[$pref]['type']) {
            case 'alarm':
                $methods = Horde_Alarm::notificationMethods();
                $val = (isset($this->vars->$pref) && is_array($this->vars->$pref))
                    ? $this->vars->$pref
                    : array();
                $value = array();

                foreach ($val as $method) {
                    $value[$method] = array();
                    if (!empty($methods[$method])) {
                        foreach (array_keys($methods[$method]) as $param) {
                            $value[$method][$param] = $this->vars->get($pref . '_' . $param, '');
                            if (is_array($methods[$method][$param]) &&
                                $methods[$method][$param]['required'] &&
                                ($value[$method][$param] === '')) {
                                $notification->push(sprintf(_("You must provide a setting for \"%s\"."), $methods[$method][$param]['desc']), 'horde.error');
                                break 3;
                            }
                        }
                    }
                }

                $updated |= $prefs->setValue($pref, serialize($value));
                break;

            case 'checkbox':
                $updated |= $prefs->setValue($pref, intval(isset($this->vars->$pref)));
                break;

            case 'enum':
                if (isset($this->prefs[$pref]['enum'][$this->vars->$pref])) {
                    $updated |= $prefs->setValue($pref, $this->vars->$pref);
                } else {
                    $notification->push(_("An illegal value was specified."), 'horde.error');
                }
                break;

            case 'multienum':
                $set = array();

                if (is_array($this->vars->$pref)) {
                    foreach ($this->vars->$pref as $val) {
                        if (isset($this->prefs[$pref]['enum'][$val])) {
                            $set[] = $val;
                        } else {
                            $notification->push(_("An illegal value was specified."), 'horde.error');
                            break 2;
                        }
                    }
                }

                $updated |= $prefs->setValue($pref, @serialize($set));
                break;

            case 'number':
                $num = $this->vars->$pref;
                if ((string)(double)$num !== $num) {
                    $notification->push(_("This value must be a number."), 'horde.error');
                } elseif (empty($num)) {
                    $notification->push(_("This number must be non-zero."), 'horde.error');
                } else {
                    $updated |= $prefs->setValue($pref, $num);
                }
                break;

            case 'password':
            case 'text':
            case 'textarea':
                $updated |= $prefs->setValue($pref, $this->vars->$pref);
                break;


            case 'special':
                /* Code for special elements written specifically for each
                 * application. */
                if ($registry->hasAppMethod($this->app, 'prefsSpecialUpdate')) {
                    $updated = $updated | (bool)$registry->callAppMethod($this->app, 'prefsSpecialUpdate', array('args' => array($this, $pref)));
                }
                break;
            }
        }

        if ($updated) {
            if ($registry->hasAppMethod($this->app, 'prefsCallback')) {
                $registry->callAppMethod($this->app, 'prefsCallback', array('args' => array($this)));
            }

            if ($prefs instanceof Horde_Prefs_Session) {
                $notification->push(_("Your options have been updated for the duration of this session."), 'horde.success');
            } else {
                $notification->push(_("Your options have been updated."), 'horde.success');
            }

            $this->_loadPrefs($this->app);
        }

        return $updated;
    }

    /**
     * Returns a self URL to the current page.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'special' - (boolean) If set, will trigger special action update
     *             processing when the URL is loaded.
     * </pre>
     *
     * @return Horde_Url  The URL object.
     */
    public function selfUrl($options = array())
    {
        $url = Horde::getServiceLink('options', $this->app);
        if ($this->group) {
            $url->add('group', $this->group);
        }
        if (!empty($options['special'])) {
            $url->add('actionID', 'update_special');
        }
        return $url;
    }

    /**
     * Generate the UI for the preferences interface, either for a
     * specific group, or the group selection interface.
     */
    public function generateUI()
    {
        global $notification, $prefs, $registry;

        $columns = $pref_list = array();

        /* Run app-specific init code. */
        if ($registry->hasAppMethod($this->app, 'prefsInit')) {
            $registry->callAppMethod($this->app, 'prefsInit', array('args' => array($this)));
        }

        $prefgroups = $this->_getPrefGroups();

        if ($this->group) {
            $pref_list = $this->getChangeablePrefs($this->group, true);
        } else {
            foreach ($prefgroups as $key => $val) {
                $columns[$val['column']][$key] = $val;
            }
        }

        if (empty($columns) && empty($pref_list)) {
            $notification->push(_("There are no options available."), 'horde.message');
            $this->nobuttons = true;
        }

        $options_link = Horde::getServiceLink('options');
        $h_templates = $registry->get('templates', 'horde');

        $base = $GLOBALS['injector']->createInstance('Horde_Template');
        $base->setOption('gettext', true);

        /* Need to buffer output - it is possible that 'special' types can
         * do things like add javascript to the page output. This should all
         * be combined and served in the page HEAD. */
        ob_start();

        if ($this->group) {
            foreach ($pref_list as $pref) {
                if ($this->prefs[$pref]['type'] == 'special') {
                    if ($registry->hasAppMethod($this->app, 'prefsSpecial')) {
                        echo $registry->callAppMethod($this->app, 'prefsSpecial', array('args' => array($this, $pref)));
                    }

                    continue;
                }

                $t = clone $base;
                if (isset($this->prefs[$pref]['desc'])) {
                    $t->set('desc', Horde::label($pref, $this->prefs[$pref]['desc']));
                }
                $t->set('helplink', empty($this->prefs[$pref]['help']) ? null : Horde_Help::link(empty($this->prefs[$pref]['shared']) ? $this->app : 'horde', $this->prefs[$pref]['help']));
                $t->set('pref', htmlspecialchars($pref));

                $type = $this->prefs[$pref]['type'];
                switch ($type) {
                case 'alarm':
                    Horde::addScriptFile('alarmprefs.js', 'horde');
                    Horde::addInlineScript(array(
                        'HordeAlarmPrefs.pref = ' . Horde_Serialize::serialize($pref, Horde_Serialize::JSON)
                    ));

                    $alarm_pref = unserialize($prefs->getValue($pref));
                    $selected = array_keys($alarm_pref);

                    $param_list = $select_list = array();
                    foreach (Horde_Alarm::notificationMethods() as $method => $params) {
                        $select_list[] = array(
                            'l' => $params['__desc'],
                            's' => in_array($method, $selected),
                            'v' => $method
                        );

                        if (count($params > 1)) {
                            $tmp = array(
                                'method' => $method,
                                'param' => array()
                            );

                            foreach ($params as $name => $param) {
                                if (substr($name, 0, 2) == '__') {
                                    continue;
                                }

                                switch ($param['type']) {
                                case 'text':
                                    $tmp['param'][] = array(
                                        'label' => Horde::label($pref . '_' . $name, $param['desc']),
                                        'name' => $pref . '_' . $name,
                                        'text' => true,
                                        'value' => empty($alarm_pref[$method][$name]) ? '' : htmlspecialchars($alarm_pref[$method][$name])
                                    );
                                    break;

                                case 'bool':
                                    $tmp['param'][] = array(
                                        'bool' => true,
                                        'checked' => !empty($alarm_pref[$method][$name]),
                                        'label' => Horde::label($pref . '_' . $name, $param['desc']),
                                        'name' => $pref . '_' . $name
                                    );
                                    break;

                                case 'sound':
                                    $current_sound = empty($alarm_pref[$method][$name])
                                        ? ''
                                        : $alarm_pref[$method][$name];
                                    $sounds = array();
                                    foreach (Horde_Themes::soundList() as $key => $val) {
                                        $sounds[] = array(
                                            'c' => ($current_sound == $key),
                                            'uri' => htmlspecialchars($val->uri),
                                            'val' => htmlspecialchars($key)
                                        );
                                    }

                                    $tmp['param'][] = array(
                                        'sound' => true,
                                        'checked' => !$current_sound,
                                        'name' => $pref . '_' . $name
                                    );
                                    break;
                                }
                            }

                            $param_list[] = $tmp;
                        }
                    }
                    $t->set('param_list', $param_list);
                    $t->set('select_list', $select_list);
                    break;

                case 'checkbox':
                    $t->set('checked', $prefs->getValue($pref));
                    break;

                case 'enum':
                    $enum = isset($this->override[$pref])
                        ? $this->override[$pref]
                        : $this->prefs[$pref]['enum'];
                    $esc = !empty($this->prefs[$pref]['escaped']);
                    $curval = $prefs->getValue($pref);

                    $tmp = array();
                    foreach ($enum as $key => $val) {
                        $tmp[] = array(
                            'l' => $esc ? $val : htmlspecialchars($val),
                            's' => ($curval == $key),
                            'v' => $esc ? $key : htmlspecialchars($key)
                        );
                    }
                    $t->set('enum', $tmp);
                    break;

                case 'prefslink':
                    $url = $this->selfUrl()->add('group', $this->prefs[$pref]['group']);
                    if (!empty($this->prefs[$pref]['app'])) {
                        $url->add('app', $this->prefs[$pref]['app']);
                    }
                    $this->prefs[$pref]['url'] = $url;
                    $type = 'link';
                    // Fall through to 'link'

                case 'link':
                    if (isset($this->prefs[$pref]['img'])) {
                        $t->set('img', Horde::img($this->prefs[$pref]['img'], $this->prefs[$pref]['desc'], array('class' => 'prefsLinkImg')));
                    }
                    $t->set('url', isset($this->prefs[$pref]['url']) ? Horde::applicationUrl($this->prefs[$pref]['url']) : $this->prefs[$pref]['xurl']);
                    if (isset($this->prefs[$pref]['target'])) {
                        $t->set('target', htmlspecialchars($this->prefs[$pref]['target']));
                    }
                    break;

                case 'multienum':
                    $enum = isset($this->override[$pref])
                        ? $this->override[$pref]
                        : $this->prefs[$pref]['enum'];
                    $esc = !empty($this->prefs[$pref]['escaped']);
                    if (!$selected = @unserialize($prefs->getValue($pref))) {
                        $selected = array();
                    }

                    $tmp = array();
                    foreach ($enum as $key => $val) {
                        $tmp[] = array(
                            'l' => $esc ? $val : htmlspecialchars($val),
                            's' => in_array($key, $selected),
                            'v' => $esc ? $key : htmlspecialchars($key)
                        );
                    }
                    $t->set('enum', $tmp);

                    $t->set('size', min(4, count($enum)));
                    break;

                case 'number':
                    $t->set('val', htmlspecialchars(intval($prefs->getValue($pref))));
                    break;

                case 'password':
                case 'text':
                case 'textarea':
                    $t->set('val', htmlspecialchars($prefs->getValue($pref)));
                    break;

                case 'rawhtml':
                    $t->set('html', $this->prefs[$pref]['value']);
                    break;
                }

                echo $t->fetch(HORDE_TEMPLATES . '/prefs/' . $type . '.html');
            }

            $t = clone $base;
            $t->set('buttons', !$this->nobuttons);
            $t->set('prefgroups', count($prefgroups) > 1);
            echo $t->fetch($h_templates . '/prefs/end.html');
        } elseif (!empty($columns)) {
            $t = clone $base;
            $span = round(100 / count($columns));

            $cols = array();
            foreach ($columns as $key => $column) {
                $tmp = array(
                    'groups' => array(),
                    'hdr' => htmlspecialchars($key),
                    'width' => $span - 1
                );

                foreach ($column as $group => $gvals) {
                    if ($this->groupIsEditable($group)) {
                        $tmp['groups'][] = array(
                            'desc' => htmlspecialchars($gvals['desc']),
                            'link' => Horde::widget($options_link->copy()->add(array('app' => $this->app, 'group' => $group)), $gvals['label'], '', '', '', $gvals['label'])
                        );
                    }
                }
                $cols[] = $tmp;
            }
            $t->set('columns', $cols);

            echo $t->fetch($h_templates . '/prefs/overview.html');
        }

        $content = ob_get_clean();

        $title = _("User Options");

        /* Get the menu output before we start to output the page.
         * Again, this will catch any javascript inserted into the page. */
        if (empty($_SESSION['horde_prefs']['nomenu'])) {
            if ($registry->hasAppMethod($this->app, 'prefsMenu')) {
                $menu = $registry->callAppMethod($this->app, 'prefsMenu', array('args' => array($this)));
            }
        }

        /* Get list of accessible applications. */
        $apps = array();
        foreach ($registry->listApps() as $app) {
            // Make sure the app is installed and has a prefs file.
            if (file_exists($registry->get('fileroot', $app) . '/config/prefs.php')) {
                $apps[$app] = $registry->get('name', $app);
            }
        }
        asort($apps);

        /* Ouptut screen. */
        $GLOBALS['bodyId'] = 'services_prefs';
        require $h_templates . '/common-header.inc';

        if (empty($_SESSION['horde_prefs']['nomenu'])) {
            require $h_templates . '/menu/menu.inc';
        }

        $notification->notify(array('listeners' => 'status'));

        $base_ui = clone $base;
        $base_ui->set('action', $options_link);
        $base_ui->set('forminput', Horde_Util::formInput());

        /* Show the current application and a form for switching
         * applications. */
        $t = clone $base_ui;
        $t->set('horde', !empty($apps['horde']));
        unset($apps['horde'], $apps[$this->app]);
        $tmp = array();
        foreach ($apps as $key => $val) {
            $tmp[] = array(
                'l' => htmlspecialchars($val),
                'v' => htmlspecialchars($key)
            );
        }
        $t->set('apps', $tmp);
        $t->set('header', htmlspecialchars(($this->app == 'horde') ? _("Global Options") : sprintf(_("Options for %s"), $registry->get('name', $this->app))));
        echo $t->fetch($h_templates . '/prefs/app.html');

        /* Generate navigation header. */
        if ($this->group) {
            $t = clone $base_ui;
            $t->set('app', htmlspecialchars($this->app));
            $t->set('group', htmlspecialchars($this->group));
            $t->set('label', htmlspecialchars($this->prefGroups[$this->group]['label']));

            // Search for previous and next groups.
            if (count($prefgroups) > 1) {
                $prefgroups = array_keys($prefgroups);
                $key = array_search($this->group, $prefgroups);
                $previous = isset($prefgroups[$key - 1])
                    ? $prefgroups[$key - 1]
                    : end($prefgroups);
                $next = isset($prefgroups[$key + 1])
                    ? $prefgroups[$key + 1]
                    : reset($prefgroups);
                $prefs_url = $this->selfUrl();

                $t->set('prev', $prefs_url->copy()->add('group', $previous));
                $t->set('prevlabel', htmlspecialchars($this->prefGroups[$previous]['label']));
                $t->set('next', $prefs_url->copy()->add('group', $next));
                $t->set('nextlabel', htmlspecialchars($this->prefGroups[$next]['label']));
            }

            echo $t->fetch($h_templates . '/prefs/begin.html');
        }

        echo $content;

        require $h_templates . '/common-footer.inc';
    }

    /**
     * Get the default application to show preferences for. Defaults
     * to 'horde'.
     *
     * @return string  The default application.
     */
    public function getDefaultApp()
    {
        $applications = $GLOBALS['registry']->listApps(null, true, Horde_Perms::READ);
        return isset($applications['horde'])
            ? 'horde'
            : array_shift($applications);
    }

    /**
     * Loads preferences configuration into the current object.
     *
     * @param string $app  The application.
     */
    protected function _loadPrefs($app, $merge = false)
    {
        try {
            $res = Horde::loadConfiguration('prefs.php', array('prefGroups', '_prefs'), $app);
            $this->prefGroups = $res['prefGroups'];
            $this->prefs = $res['_prefs'];
        } catch (Horde_Exception $e) {
            $this->prefGroups = $this->prefs = array();
        }

        /* If there's only one prefGroup, just show it. */
        if (!$this->group && (count($this->prefGroups) == 1)) {
            reset($this->prefGroups);
            $this->group = key($this->prefGroups);
        }
    }

    /**
     * Get the list of viewable preference groups, filtering out suppressed
     * groups and groups with no settable prefs.
     *
     * @return array  The filtered prefGroups array.
     */
    protected function _getPrefGroups()
    {
        $out = array();

        foreach (array_diff(array_keys($this->prefGroups), $this->suppressGroups) as $val) {
            if ($this->groupIsEditable($val)) {
                $out[$val] = $this->prefGroups[$val];
            }
        }

        return $out;
    }

}
