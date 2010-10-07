<?php
/**
 * Class for generating/processing the preferences UI.
 *
 * See Horde_Registry_Application:: for a summary of the API callbacks that
 * are available.
 *
 * Session variables set (stored in 'horde_prefs'):
 * 'advanced' - (boolean) If true, display advanced prefs.
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
     * Suppressed preference entries to automatically update.
     *
     * @var array
     */
    public $suppressUpdate = array();

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
        try {
            $GLOBALS['registry']->pushApp($this->app);
        } catch (Horde_Exception $e) {
            if ($e->getCode() == Horde_Registry::AUTH_FAILURE) {
                $GLOBALS['registry']->authenticateFailure($this->app, $e);
            }
            throw $e;
        }

        /* Load preferences. */
        $this->_loadPrefs($this->app);

        /* Populate enums. */
        if ($this->group &&
            $GLOBALS['registry']->hasAppMethod($this->app, 'prefsEnum') &&
            $this->groupIsEditable($this->group)) {
            $GLOBALS['registry']->callAppMethod($this->app, 'prefsEnum', array('args' => array($this)));
        }
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
     * @param string $group  The preferences group to check.
     *
     * @return array  The list of changeable prefs.
     */
    public function getChangeablePrefs($group)
    {
        $prefs = array();

        if (!empty($this->prefGroups[$group]['members'])) {
            foreach ($this->prefGroups[$group]['members'] as $pref) {
                /* Changeable pref if:
                 *   1. Not locked
                 *   2. Not in suppressed array ($this->suppress)
                 *   3. Not an advanced pref -or- in advanced view mode
                 *   4. Not an implicit pref */
                if (!$GLOBALS['prefs']->isLocked($pref) &&
                    !in_array($pref, $this->suppress) &&
                    (empty($this->prefs[$pref]['advanced']) ||
                     !empty($_SESSION['horde_prefs']['advanced'])) &&
                    ((!empty($this->prefs[$pref]['type']) &&
                     ($this->prefs[$pref]['type'] != 'implicit')))) {
                    $prefs[] = $pref;
                }
            }
        }

        return $prefs;
    }

    /**
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     */
    public function handleForm()
    {
        /* Toggle Advanced/Basic mode. */
        if (!empty($this->vars->show_advanced) ||
            !empty($this->vars->show_basic)) {
            $_SESSION['horde_prefs']['advanced'] = !empty($this->vars->show_advanced);
        }

        if (!$this->group || !$this->groupIsEditable($this->group)) {
            return;
        }

        if (isset($this->vars->prefs_return)) {
            $this->group = $this->vars->actionID = '';
            return;
        }

        if ($this->vars->actionID) {
            try {
                Horde::checkRequestToken('horde.prefs', $this->vars->horde_prefs_token);
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push($e);
                return;
            }
        }

        switch ($this->vars->actionID) {
        case 'update_prefs':
            if (isset($this->prefGroups[$this->group]['type']) &&
                ($this->prefGroups[$this->group]['type'] == 'identities')) {
                $this->_identitiesUpdate();
            } else {
                $this->_handleForm(array_diff($this->getChangeablePrefs($this->group), $this->suppressUpdate), $GLOBALS['prefs']);
            }
            break;

        case 'update_special':
            $special = array();
            foreach ($this->getChangeablePrefs($this->group) as $pref) {
                if ($this->prefs[$pref]['type'] == 'special') {
                    $special[] = $pref;
                }
            }
            $this->_handleForm($special, $GLOBALS['prefs']);
            break;
        }
    }

    /*
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     *
     * @param array $preflist  The list of preferences to process.
     * @param mixed $save      The object to save preferences values to.
     */
    protected function _handleForm($preflist, $save)
    {
        global $notification, $prefs, $registry;

        $updated = false;

        /* Run through the action handlers */
        foreach ($preflist as $pref) {
            switch ($this->prefs[$pref]['type']) {
            case 'checkbox':
                $updated |= $save->setValue($pref, intval(isset($this->vars->$pref)));
                break;

            case 'enum':
                $enum = isset($this->override[$pref])
                    ? $this->override[$pref]
                    : $this->prefs[$pref]['enum'];
                if (isset($enum[$this->vars->$pref])) {
                    $updated |= $save->setValue($pref, $this->vars->$pref);
                } else {
                    $notification->push(_("An illegal value was specified."), 'horde.error');
                }
                break;

            case 'multienum':
                $set = array();

                if (is_array($this->vars->$pref)) {
                    $enum = isset($this->override[$pref])
                        ? $this->override[$pref]
                        : $this->prefs[$pref]['enum'];

                    foreach ($this->vars->$pref as $val) {
                        if (isset($enum[$val])) {
                            $set[] = $val;
                        } else {
                            $notification->push(_("An illegal value was specified."), 'horde.error');
                            break 2;
                        }
                    }
                }

                $updated |= $save->setValue($pref, @serialize($set));
                break;

            case 'number':
                $num = $this->vars->$pref;
                if ((string)(double)$num !== $num) {
                    $notification->push(_("This value must be a number."), 'horde.error');
                } elseif (empty($num)) {
                    $notification->push(_("This number must be non-zero."), 'horde.error');
                } else {
                    $updated |= $save->setValue($pref, $num);
                }
                break;

            case 'password':
            case 'text':
            case 'textarea':
                $updated |= $save->setValue($pref, $this->vars->$pref);
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
            if ($save instanceof Horde_Prefs_Identity) {
                // Throws Exception caught in _identitiesUpdate().
                $save->verify();
            }

            if ($registry->hasAppMethod($this->app, 'prefsCallback')) {
                $registry->callAppMethod($this->app, 'prefsCallback', array('args' => array($this)));
            }

            if ($prefs instanceof Horde_Prefs_Session) {
                $notification->push(_("Your preferences have been updated for the duration of this session."), 'horde.success');
            } else {
                $notification->push(_("Your preferences have been updated."), 'horde.success');
            }

            $this->_loadPrefs($this->app);
        }
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
        $url = Horde::getServiceLink('prefs', $this->app);
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
        $identities = false;

        /* Run app-specific init code. */
        if ($registry->hasAppMethod($this->app, 'prefsInit')) {
            $registry->callAppMethod($this->app, 'prefsInit', array('args' => array($this)));
        }

        $prefgroups = $this->_getPrefGroups();

        if ($this->group) {
            $pref_list = $this->getChangeablePrefs($this->group);
            if (empty($pref_list)) {
                $this->group = '';
                $this->generateUI();
                return;
            }

            /* Add necessary init stuff for identities pages. */
            if (isset($prefgroups[$this->group]['type']) &&
                ($prefgroups[$this->group]['type'] == 'identities')) {
                Horde::addScriptFile('identityselect.js', 'horde');
                $identities = true;

                /* If this is an identities group, need to grab the base
                 * identity fields from Horde, if current app is NOT Horde. */
                $pref_list = $this->_addHordeIdentitiesPrefs($pref_list);
            }
        } else {
            foreach ($prefgroups as $key => $val) {
                $columns[$val['column']][$key] = $val;
            }
        }

        if (empty($columns) && empty($pref_list)) {
            $notification->push(_("There are no preferences available for this application."), 'horde.message');
            $this->nobuttons = true;
        }

        $options_link = Horde::getServiceLink('prefs');
        $h_templates = $registry->get('templates', 'horde');

        $base = $GLOBALS['injector']->createInstance('Horde_Template');
        $base->setOption('gettext', true);

        /* Need to buffer output - it is possible that 'special' types can
         * do things like add javascript to the page output. This should all
         * be combined and served in the page HEAD. */
        Horde::startBuffer();
        Horde::addScriptFile('prefs.js', 'horde');

        if ($this->group) {
            if ($identities) {
                echo $this->_identityHeader($pref_list);
            }

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
                    $t->set('url', isset($this->prefs[$pref]['url']) ? Horde::url($this->prefs[$pref]['url']) : $this->prefs[$pref]['xurl']);
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

        $content = Horde::endBuffer();

        $title = _("User Preferences");

        /* Get the menu output before we start to output the page.
         * Again, this will catch any javascript inserted into the page. */
        $menu_out = $this->vars->ajaxui
            ? ''
            : Horde::menu(array(
                  'app' => $this->app,
                  'mask' => Horde_Menu::MASK_HELP | Horde_Menu::MASK_LOGIN | Horde_Menu::MASK_PROBLEM
              ));

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
        echo $menu_out;

        $notification->notify(array('listeners' => 'status'));

        $base_ui = clone $base;
        $base_ui->set('action', $options_link);
        $base_ui->set('ajaxui', intval($this->vars->ajaxui));
        $base_ui->set('forminput', Horde_Util::formInput());

        /* Show the current application and a form for switching
         * applications. */
        $t = clone $base_ui;
        $t->set('horde', !empty($apps['horde']) && ($this->app != 'horde'));
        unset($apps['horde'], $apps[$this->app]);
        $tmp = array();
        foreach ($apps as $key => $val) {
            $tmp[] = array(
                'l' => htmlspecialchars($val),
                'v' => htmlspecialchars($key)
            );
        }
        $t->set('apps', $tmp);
        $t->set('header', htmlspecialchars(($this->app == 'horde') ? _("Global Preferences") : sprintf(_("Preferences for %s"), $registry->get('name', $this->app))));

        if (empty($_SESSION['horde_prefs']['advanced'])) {
            $t->set('advanced', $this->selfUrl()->add('show_advanced', 1));
        } else {
            $t->set('basic', $this->selfUrl()->add('show_basic', 1));
        }

        echo $t->fetch($h_templates . '/prefs/app.html');

        /* Generate navigation header. */
        if ($this->group) {
            $t = clone $base_ui;
            $t->set('app', htmlspecialchars($this->app));
            $t->set('group', htmlspecialchars($this->group));
            $t->set('label', htmlspecialchars($this->prefGroups[$this->group]['label']));
            $t->set('token', Horde::getRequestToken('horde_prefs'));

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
     * Parses/loads preferences configuration.
     *
     * @param string $app    The application.
     * @param boolean $data  Return the data instead of loading into the
     *                       current object?
     */
    protected function _loadPrefs($app, $data = false)
    {
        try {
            $res = Horde::loadConfiguration('prefs.php', array('prefGroups', '_prefs'), $app);
        } catch (Horde_Exception $e) {
            $res = array('prefGroups' => array(), '_prefs' => array());
        }

        if ($data) {
            return $res;
        }

        $this->prefGroups = isset($res['prefGroups'])
            ? $res['prefGroups']
            : array();
        $this->prefs = $res['_prefs'];

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

    /**
     * Adds Horde base identities prefs to preference list.
     *
     * @param array $pref_list  Preference list.
     *
     * @return array  The preference list with the Horde preferences added, if
     *                needed. These prefs are also added to $this->prefs.
     */
    protected function _addHordeIdentitiesPrefs($pref_list)
    {
        if ($this->app != 'horde') {
            try {
                $res = $this->_loadPrefs('horde', true);
                foreach ($res['prefGroups'] as $pgroup) {
                    if (isset($pgroup['type']) &&
                        ($pgroup['type'] == 'identities')) {
                        foreach ($pgroup['members'] as $key => $member) {
                            if (!$GLOBALS['prefs']->isLocked($member)) {
                                $this->prefs[$member] = $res['_prefs'][$member];
                            } else {
                                unset($pgroup['members'][$key]);
                            }
                        }
                        $pref_list = array_merge($pgroup['members'], $pref_list);
                    }
                }
            } catch (Horde_Exception $e) {}
        }

        return $pref_list;
    }

    /**
     * Output the identities page header entries (default identity,
     * identity selection, and identity deletion).
     *
     * @param array $members  The list of prefs to display on this page.
     *
     * @return string  HTML output.
     */
    protected function _identityHeader($members)
    {
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create(null, $this->app);
        $default_identity = $identity->getDefault();

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if ($GLOBALS['prefs']->isLocked('default_identity')) {
            $t->set('default_identity', intval($default_identity));
            $identities = array($default_identity);
        } else {
            $t->set('defaultid', _("Your default identity:"));
            $t->set('label', Horde::label('identity', _("Select the identity you want to change:")));
            $identities = $identity->getAll('id');
        }

        $entry = $js = array();
        foreach ($identities as $key => $val) {
            $entry[] = array(
                'i' => $key,
                'label' => htmlspecialchars($val),
                'sel' => ($key == $default_identity)
            );

            $tmp = array();
            foreach ($members as $member) {
                $val = $identity->getValue($member, $key);
                switch ($this->prefs[$member]['type']) {
                case 'checkbox':
                case 'number':
                    $val2 = intval($val);
                    break;

                case 'textarea':
                    if (is_array($val)) {
                        $val = implode("\n", $val);
                    }
                    // Fall-through

                default:
                    $val2 = $val;
                }

                // [0] = pref name
                // [1] = pref type
                // [2] = pref value
                $tmp[] = array(
                    $member,
                    $this->prefs[$member]['type'],
                    $val2
                );
            }
            $js[] = $tmp;
        }
        $t->set('entry', $entry);

        Horde::addInlineScript(array(
            'HordeIdentitySelect.newChoice()'
        ), 'dom');
        Horde::addInlineScript(array(
            'HordeIdentitySelect.identities = ' . Horde_Serialize::serialize($js, Horde_Serialize::JSON)
        ));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/identityselect.html');
    }

    /**
     * Update identities prefs.
     */
    protected function _identitiesUpdate()
    {
        global $conf, $notification, $prefs;

        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create(null, $this->app);

        if ($this->vars->delete_identity) {
            $id = intval($this->vars->id);
            $deleted_identity = $identity->delete($id);
            $this->_loadPrefs($this->app);
            $notification->push(sprintf(_("The identity \"%s\" has been deleted."), $deleted_identity[0]['id']), 'horde.success');
            return;
        }

        $old_default = $identity->getDefault();
        $from_addresses = $identity->getAll('from_addr');
        $current_from = $identity->getValue('from_addr');
        $id = intval($this->vars->identity);

        if (!$prefs->isLocked('default_identity')) {
            $new_default = intval($this->vars->default_identity);
            if ($new_default != $old_default) {
                $identity->setDefault($new_default);
                $old_default = $new_default;
                $notification->push(_("Your default identity has been changed."), 'horde.success');

                /* Need to immediately save, since we may short-circuit
                 * saving the identities below. */
                $identity->save();
            }
        }

        if ($id == -2) {
            return;
        }

        if ($id == -1) {
            $id = $identity->add();
        }

        $identity->setDefault($id);

        try {
            $this->_handleForm(array_diff($this->_addHordeIdentitiesPrefs($this->getChangeablePrefs($this->group)), $this->suppressUpdate), $identity);
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            return;
        }

        $new_from = $identity->getValue('from_addr');
        if (!empty($conf['user']['verify_from_addr']) &&
            ($current_from != $new_from) &&
            !in_array($new_from, $from_addresses)) {
            try {
                $identity->verifyIdentity($id, empty($current_from) ? $new_from : $current_from);
            } catch (Horde_Exception $e) {
                $notification->push(_("The new from address can't be verified, try again later: ") . $e->getMessage(), 'horde.error');
                Horde::logMessage($e, 'ERR');
            }
        } else {
            $identity->setDefault($old_default);
            $identity->save();
        }
    }

}
