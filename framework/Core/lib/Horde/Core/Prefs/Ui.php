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
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * List of update errors.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Constructor.
     *
     * @param Horde_Variables $vars  Form variables.
     */
    public function __construct($vars)
    {
        global $registry;

        $this->app = isset($vars->app)
            ? $vars->app
            : $this->getDefaultApp();
        $this->group = $vars->group;
        $this->vars = $vars;

        /* Load the application's base environment. */
        $registry->pushApp($this->app);

        /* Load preferences. */
        $this->_loadPrefs($this->app);

        /* Suppress prefs groups, as needed. */
        foreach ($this->_getPrefGroups() as $key => $val) {
            if (!empty($val['suppress']) &&
                (!is_callable($val['suppress']) || $val['suppress']())) {
                $this->suppressGroups[] = $key;
            }
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
    public function getChangeablePrefs($group = null)
    {
        global $prefs;

        if (is_null($group)) {
            if (!$this->group) {
                return array();
            }

            $group = $this->group;
        }

        if (empty($this->prefGroups[$group]['members']) ||
            in_array($group, $this->suppressGroups)) {
            return array();
        }

        $cprefs = array();

        foreach ($this->prefGroups[$group]['members'] as $pref) {
            $p = $this->prefs[$pref];

            /* Changeable pref if:
             *   1. Not locked
             *   2. Not in suppressed array ($this->suppress) or supressed
             *      variable is empty
             *   3. Not an advanced pref -or- in advanced view mode
             *   4. Not an implicit pref
             *   5. All required prefs are non-zero
             *   6. All required_nolock prefs are not locked */
            if (!$GLOBALS['prefs']->isLocked($pref) &&
                !in_array($pref, $this->suppress) &&
                (empty($p['advanced']) ||
                 $GLOBALS['session']->get('horde', 'prefs_advanced')) &&
                ((!empty($p['type']) && ($p['type'] != 'implicit')))) {
                if (!empty($p['suppress']) &&
                    (!is_callable($p['suppress']) || $p['suppress']())) {
                    continue;
                }

                if ($p['type'] == 'container') {
                    if (isset($p['value']) && is_array($p['value'])) {
                        $cprefs = array_merge($cprefs, $p['value']);
                    }
                } else {
                    if (isset($p['requires'])) {
                        foreach ($p['requires'] as $val) {
                            if (!$prefs->getValue($val)) {
                                continue 2;
                            }
                        }
                    }

                    if (isset($p['requires_nolock'])) {
                        foreach ($p['requires_nolock'] as $val) {
                            if ($prefs->isLocked($val)) {
                                continue 2;
                            }
                        }
                    }

                    $cprefs[] = $pref;
                }
            }
        }

        return $cprefs;
    }

    /**
     * Returns whether advanced preferences exist in the current application.
     *
     * @return boolean  True if at least one of the preferences is an advanced
     *                  preference.
     */
    public function hasAdvancedPrefs()
    {
        foreach ($this->_getPrefGroups() as $group) {
            if (empty($group['members'])) {
                continue;
            }
            foreach ($group['members'] as $pref) {
                if (!empty($this->prefs[$pref]['advanced'])) {
                    return true;
                }
            }
        }
        return false;
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
            $GLOBALS['session']->set('horde', 'prefs_advanced', !empty($this->vars->show_advanced));
        } elseif (!$this->vars->actionID ||
                  !$this->group ||
                  !$this->groupIsEditable($this->group)) {
            return;
        } elseif (isset($this->vars->prefs_return)) {
            $this->group = $this->vars->actionID = '';
            return;
        } else {
            try {
                $GLOBALS['injector']->getInstance('Horde_Token')->validate($this->vars->horde_prefs_token, 'horde.prefs');
            } catch (Horde_Token_Exception $e) {
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
                $this->_handleForm($this->getChangeablePrefs($this->group), $GLOBALS['prefs']);
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

        $this->nobuttons = false;
        $this->suppress = array();
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
        global $injector, $notification, $prefs;

        $updated = false;

        /* Run through the action handlers */
        foreach ($preflist as $pref) {
            $pref_updated = false;

            if (isset($this->prefs[$pref]['on_init']) &&
                is_callable($this->prefs[$pref]['on_init'])) {

                $this->prefs[$pref]['on_init']($this);
            }

            switch ($this->prefs[$pref]['type']) {
            case 'checkbox':
                $pref_updated = $save->setValue($pref, intval(isset($this->vars->$pref)));
                break;

            case 'enum':
                $enum = $this->prefs[$pref]['enum'];
                if (isset($enum[$this->vars->$pref])) {
                    $pref_updated = $save->setValue($pref, $this->vars->$pref);
                } else {
                    $this->_errors[$pref] = Horde_Core_Translation::t("An illegal value was specified.");
                }
                break;

            case 'multienum':
                $set = array();

                if (is_array($this->vars->$pref)) {
                    $enum = $this->prefs[$pref]['enum'];
                    foreach ($this->vars->$pref as $val) {
                        if (isset($enum[$val])) {
                            $set[] = $val;
                        } else {
                            $this->_errors[$pref] = Horde_Core_Translation::t("An illegal value was specified.");
                            break 2;
                        }
                    }
                }

                $pref_updated = $save->setValue($pref, @serialize($set));
                break;

            case 'number':
                $num = $this->vars->$pref;
                if ((string)(double)$num !== $num) {
                    $this->_errors[$pref] = Horde_Core_Translation::t("This value must be a number.");
                } elseif (empty($num) && empty($this->prefs[$pref]['zero'])) {
                    $this->_errors[$pref] = Horde_Core_Translation::t("This value must be non-zero.");
                } else {
                    $pref_updated = $save->setValue($pref, $num);
                }
                break;

            case 'password':
            case 'text':
            case 'textarea':
                $pref_updated = $save->setValue($pref, $this->vars->$pref);
                break;


            case 'special':
                /* Code for special elements written specifically for each
                 * application. */
                if (isset($this->prefs[$pref]['handler']) &&
                    ($ob = $injector->getInstance($this->prefs[$pref]['handler']))) {
                    $pref_updated = $ob->update($this);
                }
                break;
            }

            if ($pref_updated) {
                $updated = true;

                if (isset($this->prefs[$pref]['on_change']) &&
                    is_callable($this->prefs[$pref]['on_change'])) {
                    $this->prefs[$pref]['on_change']();
                }
            }
        }

        if (count($this->_errors)) {
            $notification->push(Horde_Core_Translation::t("There were errors encountered while updating your preferences."), 'horde.error');
        }

        if ($updated) {
            if ($save instanceof Horde_Prefs_Identity) {
                // Throws Exception caught in _identitiesUpdate().
                $save->verify();
            }

            if ($prefs instanceof Horde_Prefs_Session) {
                $notification->push(Horde_Core_Translation::t("Your preferences have been updated for the duration of this session."), 'horde.success');
            } else {
                $notification->push(Horde_Core_Translation::t("Your preferences have been updated."), 'horde.success');
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
     * 'token' - (boolean) If set, includes the form token in the URL.
     * </pre>
     *
     * @return Horde_Url  The URL object.
     */
    public function selfUrl($options = array())
    {
        $url = $GLOBALS['registry']->getServiceLink('prefs', $this->app);
        if ($this->group) {
            $url->add('group', $this->group);
        }
        if (!empty($options['special'])) {
            $url->add('actionID', 'update_special');
        }
        if (!empty($options['token'])) {
            $url->add('horde_prefs_token', $GLOBALS['injector']->getInstance('Horde_Token')->get('horde.prefs'));
        }
        return $url;
    }

    /**
     * Generate the UI for the preferences interface, either for a
     * specific group, or the group selection interface.
     */
    public function generateUI()
    {
        global $notification, $page_output, $prefs, $registry;

        $columns = $pref_list = array();
        $identities = false;

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
                $page_output->addScriptFile('identityselect.js', 'horde');
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
            $notification->push(Horde_Core_Translation::t("There are no preferences available for this application."), 'horde.message');
            $this->nobuttons = true;
        }

        $options_link = $registry->getServiceLink('prefs');
        $h_templates = $registry->get('templates', 'horde');

        $base = $GLOBALS['injector']->createInstance('Horde_Template');
        $base->setOption('gettext', true);

        /* Need to buffer output - it is possible that 'special' types can
         * do things like add javascript to the page output. This should all
         * be combined and served in the page HEAD. */
        Horde::startBuffer();
        $page_output->addScriptFile('prefs.js', 'horde');

        if ($this->group) {
            if ($identities) {
                echo $this->_identityHeader($pref_list);
            }

            foreach ($pref_list as $pref) {
                if (isset($this->prefs[$pref]['on_init']) &&
                    is_callable($this->prefs[$pref]['on_init'])) {
                    $this->prefs[$pref]['on_init']($this);
                }

                if (($this->prefs[$pref]['type'] == 'special') &&
                    isset($this->prefs[$pref]['handler']) &&
                    ($ob = $GLOBALS['injector']->getInstance($this->prefs[$pref]['handler']))) {
                    echo $ob->display($this);
                    continue;
                }

                $t = clone $base;

                if (isset($this->_errors[$pref])) {
                    echo $t->fetch(HORDE_TEMPLATES . '/prefs/error_start.html');
                }

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
                    $enum = $this->prefs[$pref]['enum'];
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
                    $enum = $this->prefs[$pref]['enum'];
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
                    $t->set('html', $prefs->getValue($pref));
                    break;
                }

                echo $t->fetch(HORDE_TEMPLATES . '/prefs/' . $type . '.html');

                if (isset($this->_errors[$pref])) {
                    $t->set('error', htmlspecialchars($this->_errors[$pref]));
                    echo $t->fetch(HORDE_TEMPLATES . '/prefs/error_end.html');
                }
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
                            'link' => Horde::widget(array('url' => $options_link->copy()->add(array('app' => $this->app, 'group' => $group)), 'title' => $gvals['label']))
                        );
                    }
                }
                $cols[] = $tmp;
            }
            $t->set('columns', $cols);

            echo $t->fetch($h_templates . '/prefs/overview.html');
        }

        $content = Horde::endBuffer();

        /* Get the menu output before we start to output the page.
         * Again, this will catch any javascript inserted into the page. */
        $GLOBALS['page_output']->sidebar = false;

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
        $page_output->header(array(
            'body_id' => 'services_prefs',
            'title' => Horde_Core_Translation::t("User Preferences"),
            // For now, force to Basic view for preferences.
            'view' => $registry::VIEW_BASIC
        ));

        $notification->notify(array('listeners' => 'status'));

        $base_ui = clone $base;
        $base_ui->set('action', $options_link);
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
        $t->set('header', htmlspecialchars(($this->app == 'horde') ? Horde_Core_Translation::t("Global Preferences") : sprintf(Horde_Core_Translation::t("Preferences for %s"), $registry->get('name', $this->app))));

        $t->set('has_advanced', $this->hasAdvancedPrefs());
        if ($GLOBALS['session']->get('horde', 'prefs_advanced')) {
            $t->set('basic', $this->selfUrl()->add('show_basic', 1));
        } else {
            $t->set('advanced', $this->selfUrl()->add('show_advanced', 1));
        }

        echo $t->fetch($h_templates . '/prefs/app.html');

        /* Generate navigation header. */
        if ($this->group) {
            $t = clone $base_ui;
            $t->set('app', htmlspecialchars($this->app));
            $t->set('group', htmlspecialchars($this->group));
            $t->set('label', htmlspecialchars($this->prefGroups[$this->group]['label']));
            $t->set('token', $GLOBALS['injector']->getInstance('Horde_Token')->get('horde.prefs'));

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
                $t->set('previcon', Horde::img('nav/left.png'));
                $t->set('next', $prefs_url->copy()->add('group', $next));
                $t->set('nextlabel', htmlspecialchars($this->prefGroups[$next]['label']));
                $t->set('nexticon', Horde::img('nav/right.png'));
            }

            echo $t->fetch($h_templates . '/prefs/begin.html');
        }

        echo $content;

        $GLOBALS['page_output']->footer();
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
            $t->set('defaultid', Horde_Core_Translation::t("Your default identity:"));
            $t->set('label', Horde::label('identity', Horde_Core_Translation::t("Select the identity you want to change:")));
            $identities = $identity->getAll('id');
        }

        $entry = $js = array();

        $tmp = array();
        foreach ($members as $member) {
            $tmp[] = $this->_generateEntry(
                $member,
                $GLOBALS['prefs']->getDefault($member));
        }
        $js[-1] = $tmp;

        foreach ($identities as $key => $val) {
            $entry[] = array(
                'i' => $key,
                'label' => htmlspecialchars($val),
                'sel' => ($key == $default_identity)
            );

            $tmp = array();
            foreach ($members as $member) {
                $tmp[] = $this->_generateEntry(
                    $member,
                    $identity->getValue($member, $key));
            }
            $js[] = $tmp;
        }
        $t->set('entry', $entry);

        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineScript(array(
            'HordeIdentitySelect.identities = ' . Horde_Serialize::serialize($js, Horde_Serialize::JSON)
        ));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/identityselect.html');
    }

    /**
     * Generates an entry hash for an identity's preference value.
     *
     * @param string $member  A preference name.
     * @param mixed $val      A preference value.
     *
     * @return array  An array with preference name, type, and value.
     */
    protected function _generateEntry($member, $val)
    {
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
        return array(
            $member,
            $this->prefs[$member]['type'],
            $val2
        );
    }

    /**
     * Update identities prefs.
     */
    protected function _identitiesUpdate()
    {
        global $conf, $notification, $prefs;

        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create(null, $this->app);

        if ($this->vars->delete_identity) {
            $id = intval($this->vars->identity);
            $deleted_identity = $identity->delete($id);
            $this->_loadPrefs($this->app);
            $notification->push(sprintf(Horde_Core_Translation::t("The identity \"%s\" has been deleted."), $deleted_identity[0]['id']), 'horde.success');
            return;
        }

        $old_default = $identity->getDefault();
        $from_addresses = $identity->getAll('from_addr');
        $current_from = $identity->getValue('from_addr');
        $id = intval($this->vars->identity);

        if ($prefs->isLocked('default_identity')) {
            $id = $old_default;
        } else {
            $new_default = intval($this->vars->default_identity);
            if ($new_default != $old_default) {
                $identity->setDefault($new_default);
                $old_default = $new_default;
                $notification->push(Horde_Core_Translation::t("Your default identity has been changed."), 'horde.success');

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
            $this->_handleForm($this->_addHordeIdentitiesPrefs($this->getChangeablePrefs($this->group)), $identity);
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
                $notification->push(Horde_Core_Translation::t("The new from address can't be verified, try again later: ") . $e->getMessage(), 'horde.error');
                Horde::logMessage($e, 'ERR');
            }
        } else {
            $identity->setDefault($old_default);
            $identity->save();
        }
    }

}
