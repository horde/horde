<?php
/**
 * Ingo base class.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo
{
    /**
     * String that can't be a valid folder name used to mark blacklisted email
     * as deleted.
     */
    const BLACKLIST_MARKER = '++DELETE++';

    /**
     * Define the key to use to indicate a user-defined header is requested.
     */
    const USER_HEADER = '++USER_HEADER++';

    /**
     * prepareMenu() cache.
     *
     * @var Horde_Template
     */
    static private $_menuTemplate = null;

    /**
     * hasSharePermission() cache.
     *
     * @var integer
     */
    static private $_shareCache = null;

    /**
     * Create an ingo session.
     *
     * Creates the $ingo session variable with the following entries:
     * 'backend' (array) - The backend configuration to use.
     * 'change' (integer) - The timestamp of the last time the rules were
     *                      altered.
     * 'storage' (array) - Used by Ingo_Storage:: for caching data.
     * 'script_categories' (array) - The list of available categories for the
     *                               Ingo_Script driver in use.
     * 'script_generate' (boolean) - Is the Ingo_Script::generate() call
     *                               available?
     *
     * @throws Ingo_Exception
     */
    static public function createSession()
    {
        if (isset($_SESSION['ingo'])) {
            return;
        }

        global $prefs;

        $_SESSION['ingo'] = array(
            'backend' => Ingo::getBackend(),
            'change' => 0,
            'storage' => array()
        );

        $ingo_script = Ingo::loadIngoScript();
        $_SESSION['ingo']['script_generate'] = $ingo_script->generateAvailable();

        /* Disable categories as specified in preferences */
        $categories = array_merge($ingo_script->availableActions(), $ingo_script->availableCategories());
        if ($prefs->isLocked('blacklist')) {
            unset($categories[Ingo_Storage::ACTION_BLACKLIST]);
        }
        if ($prefs->isLocked('whitelist')) {
            unset($categories[Ingo_Storage::ACTION_WHITELIST]);
        }
        if ($prefs->isLocked('vacation')) {
            unset($categories[Ingo_Storage::ACTION_VACATION]);
        }
        if ($prefs->isLocked('forward')) {
            unset($categories[Ingo_Storage::ACTION_FORWARD]);
        }
        if ($prefs->isLocked('spam')) {
            unset($categories[Ingo_Storage::ACTION_SPAM]);
        }

        /* Set the list of categories this driver supports. */
        $_SESSION['ingo']['script_categories'] = $categories;
    }

    /**
     * Generates a folder widget.
     * If an application is available that provides a folderlist method
     * then a &lt;select&gt; input is created else a simple text field
     * is returned.
     *
     * @param string $value    The current value for the field.
     * @param string $form     The form name for the newFolderName() call.
     * @param string $tagname  The label for the select tag.
     * @param string $onchange Javascript code to execute onchange.
     *
     * @return string  The HTML to render the field.
     */
    static public function flistSelect($value = null, $form = null,
                                       $tagname = 'actionvalue',
                                       $onchange = null)
    {
        global $conf, $registry;

        if (!empty($conf['rules']['usefolderapi'])) {
            try {
                $mailboxes = $registry->call('mail/folderlist');
                $createfolder = $registry->hasMethod('mail/createFolder');

                $text = '<select id="' . $tagname . '" name="' . $tagname . '"';
                if ($createfolder || $onchange) {
                    $text .= ' onchange="';
                    if ($onchange) {
                        $text .= $onchange . ';';
                    }
                    if ($createfolder) {
                        $text .= 'IngoNewFolder.newFolderName(\'' . $form . '\', \'' .
                            $tagname . '\');';
                    }
                    $text .= '"';
                }
                $text .= "\n<option value=\"\">" . _("Select target folder:") . "</option>\n";

                if ($registry->hasMethod('mail/createFolder')) {
                    $text .= '<option value="">' . _("Create new folder") . "</option>\n";
                }

                foreach ($mailboxes as $mbox) {
                    $sel = ($mbox['val'] && ($mbox['val'] === $value)) ? ' selected="selected"' : '';
                    $disabled = empty($mbox['val']) ? ' disabled="disabled"' : '';
                    $val = htmlspecialchars($mbox['val']);
                    $label = $mbox['abbrev'];
                    $text .= sprintf('<option%s value="%s"%s>%s</option>%s',
                                     $disabled, $val, $sel,
                                     $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($label, 'space2html', array('encode' => true)), "\n");
                }

                return $text . '</select>';
            } catch (Horde_Exception $e) {}
        }

        return '<input id="' . $tagname . '" name="' . $tagname . '" size="40" value="' . $value . '" />';
    }

    /**
     * Creates a new IMAP folder via an api call.
     *
     * @param string $folder  The name of the folder to create.
     *
     * @return boolean  True on success, false if not created.
     * @throws Horde_Exception
     */
    static public function createFolder($folder)
    {
        return $GLOBALS['registry']->hasMethod('mail/createFolder')
            ? $GLOBALS['registry']->call('mail/createFolder', array('folder' => Horde_String::convertCharset($folder, $GLOBALS['registry']->getCharset(), 'UTF7-IMAP')))
            : false;
    }

    /**
     * Returns the user whose rules are currently being edited.
     *
     * @param boolean $full  Always return the full user name with realm?
     *
     * @return string  The current user.
     */
    static public function getUser($full = true)
    {
        if (empty($GLOBALS['ingo_shares'])) {
            $baseuser = ($full ||
                        (isset($_SESSION['ingo']['backend']['hordeauth']) &&
                         $_SESSION['ingo']['backend']['hordeauth'] === 'full'));
            $user = $GLOBALS['registry']->getAuth($baseuser ? null : 'bare');
        } else {
            list(, $user) = explode(':', $_SESSION['ingo']['current_share'], 2);
        }

        return $user;
    }

    /**
     * Returns the domain name, if any of the user whose rules are currently
     * being edited.
     *
     * @return string  The current user's domain name.
     */
    static public function getDomain()
    {
        $user = self::getUser(true);
        $pos = strpos($user, '@');

        return ($pos !== false)
            ? substr($user, $pos + 1)
            : false;
    }

    /**
     * Connects to the backend and uploads the script and sets it active.
     *
     * @param string $script       The script to set active.
     * @param boolean $deactivate  If true, notification will identify the
     *                             script as deactivated instead of activated.
     *
     * @return boolean  True on success, false on failure.
     */
    static public function activateScript($script, $deactivate = false)
    {
        $transport = self::getTransport();

        try {
            $res = $transport->setScriptActive($script);
        } catch (Ingo_Exception $e) {
            $msg = ($deactivate)
              ? _("There was an error deactivating the script.")
              : _("There was an error activating the script.");
            $GLOBALS['notification']->push($msg . ' ' . _("The driver said: ") . $e->getMessage(), 'horde.error');
            return false;
        }

        if ($res === false) {
            return false;
        }

        $msg = ($deactivate)
            ? _("Script successfully deactivated.")
            : _("Script successfully activated.");
        $GLOBALS['notification']->push($msg, 'horde.success');

        return true;
    }

    /**
     * Connects to the backend and returns the currently active script.
     *
     * @return string  The currently active script.
     */
    static public function getScript()
    {
        return self::getTransport()->getScript();
    }

    /**
     * Does all the work in updating the script on the server.
     */
    static public function updateScript()
    {
        if ($_SESSION['ingo']['script_generate']) {
            try {
                $ingo_script = self::loadIngoScript();

                /* Generate and activate the script. */
                $script = $ingo_script->generate();
                self::activateScript($script);
            } catch (Ingo_Exception $e) {
                $GLOBALS['notification']->push(_("Script not updated."), 'horde.error');
            }
        }
    }

    /**
     * Determine the backend to use.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' either field
     * in the backend's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @return array  The backend entry.
     * @throws Ingo_Exception
     */
    static public function getBackend()
    {
        include INGO_BASE . '/config/backends.php';
        if (!isset($backends) || !is_array($backends)) {
            throw new Horde_Exception(_("No backends configured in backends.php"));
        }

        $backend = null;
        foreach ($backends as $name => $temp) {
            if (!isset($backend)) {
                $backend = $name;
            } elseif (!empty($temp['preferred'])) {
                if (is_array($temp['preferred'])) {
                    foreach ($temp['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backend = $name;
                        }
                    }
                } elseif (($temp['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($temp['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backend = $name;
                }
            }
        }

        /* Check for valid backend configuration. */
        if (is_null($backend)) {
            throw new Ingo_Exception(_("No backend configured for this host"));
        }

        $backends[$backend]['id'] = $name;
        $backend = $backends[$backend];

        if (empty($backend['script'])) {
            throw new Ingo_Exception(sprintf(_("No \"%s\" element found in backend configuration."), 'script'));
        } elseif (empty($backend['transport'])) {
            throw new Ingo_Exception(sprintf(_("No \"%s\" element found in backend configuration."), 'transport'));
        }

        /* Make sure the 'params' entry exists. */
        if (!isset($backend['params'])) {
            $backend['params'] = array();
        }

        return $backend;
    }

    /**
     * Loads a Ingo_Script:: backend and checks for errors.
     *
     * @return Ingo_Script  Script object on success.
     * @throws Ingo_Exception
     */
    static public function loadIngoScript()
    {
        return Ingo_Script::factory($_SESSION['ingo']['backend']['script'],
                                    isset($_SESSION['ingo']['backend']['scriptparams']) ? $_SESSION['ingo']['backend']['scriptparams'] : array());
    }

    /**
     * Returns an instance of the configured transport driver.
     *
     * @return Ingo_Transport  The configured driver.
     * @throws Ingo_Exception
     */
    static public function getTransport()
    {
        $params = $_SESSION['ingo']['backend']['params'];

        // Set authentication parameters.
        if (!empty($_SESSION['ingo']['backend']['hordeauth'])) {
            $params['username'] = $GLOBALS['registry']->getAuth(($_SESSION['ingo']['backend']['hordeauth'] === 'full') ? null : 'bare');
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        } elseif (isset($_SESSION['ingo']['backend']['params']['username']) &&
                  isset($_SESSION['ingo']['backend']['params']['password'])) {
            $params['username'] = $_SESSION['ingo']['backend']['params']['username'];
            $params['password'] = $_SESSION['ingo']['backend']['params']['password'];
        } else {
            $params['username'] = $GLOBALS['registry']->getAuth('bare');
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        }

        return Ingo_Transport::factory($_SESSION['ingo']['backend']['transport'], $params);
    }

    /**
     * Returns all rulesets a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return rulesets that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter rulesets by.
     *
     * @return array  The ruleset list.
     */
    static public function listRulesets($owneronly = false,
                                        $permission = Horde_Perms::SHOW)
    {
        try {
            $rulesets = $GLOBALS['ingo_shares']->listShares($GLOBALS['registry']->getAuth(), $permission, $owneronly ? $GLOBALS['registry']->getAuth() : null);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        return $rulesets;
    }

    /**
     * TODO
     */
    static public function hasSharePermission($mask = null)
    {
        if (!isset($GLOBALS['ingo_shares'])) {
            return true;
        }

        if (is_null(self::$_shareCache)) {
            self::$_shareCache = $GLOBALS['ingo_shares']->getPermissions($_SESSION['ingo']['current_share'], $GLOBALS['registry']->getAuth());
        }

        return self::$_shareCache & $mask;
    }

    /**
     * Returns whether an address is empty or only contains a "@".
     * Helper function for array_filter().
     *
     * @param string $address  An email address to test.
     *
     * @return boolean  True if the address is not empty.
     */
    static public function filterEmptyAddress($address)
    {
        $address = trim($address);
        return !empty($address) && ($address != '@');
    }

    /**
     * Build Ingo's list of menu items.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu();
        try {
            $menu->add(Horde::url('filters.php'), _("Filter _Rules"), 'ingo.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showWhitelist')), _("_Whitelist"), 'whitelist.png');
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showBlacklist')), _("_Blacklist"), 'blacklist.png');
        } catch (Horde_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
        }
        if (in_array(Ingo_Storage::ACTION_VACATION, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::url('vacation.php'), _("_Vacation"), 'vacation.png');
        }
        if (in_array(Ingo_Storage::ACTION_FORWARD, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::url('forward.php'), _("_Forward"), 'forward.png');
        }
        if (in_array(Ingo_Storage::ACTION_SPAM, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::url('spam.php'), _("S_pam"), 'spam.png');
        }
        if ($_SESSION['ingo']['script_generate'] &&
            (!$GLOBALS['prefs']->isLocked('auto_update') ||
             !$GLOBALS['prefs']->getValue('auto_update'))) {
            $menu->add(Horde::url('script.php'), _("_Script"), 'script.png');
        }
        if (!empty($GLOBALS['ingo_shares']) && empty($GLOBALS['conf']['share']['no_sharing'])) {
            $menu->add('#', _("_Permissions"), 'perms.png', Horde_Themes::img(null, 'horde'), '', Horde::popupJs(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php', true), array('params' => array('app' => 'ingo', 'share' => $_SESSION['ingo']['backend']['id'] . ':' . $GLOBALS['registry']->getAuth()), 'urlencode' => true)) . 'return false;');
        }

        return $menu;
    }

    /**
     * Prepares and caches Ingo's list of menu items.
     *
     * @return string  The menu text.
     */
    static public function prepareMenu()
    {
        if (isset(self::$_menuTemplate)) {
            return;
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->set('forminput', Horde_Util::formInput());

        if (!empty($GLOBALS['ingo_shares']) &&
            (count($GLOBALS['all_rulesets']) > 1)) {
            $options = array();
            foreach (array_keys($GLOBALS['all_rulesets']) as $id) {
                $options[] = array(
                    'name' => htmlspecialchars($GLOBALS['all_rulesets'][$id]->get('name')),
                    'selected' => ($_SESSION['ingo']['current_share'] == $id),
                    'val' => htmlspecialchars($id)
                );
            }
            $t->set('options', $options);
        }

        $t->set('menu_string', self::getMenu()->render());

        self::$_menuTemplate = $t;
    }

    /**
     * Outputs IMP's menu to the current output stream.
     */
    static public function menu()
    {
        self::prepareMenu();
        echo self::$_menuTemplate->fetch(INGO_TEMPLATES . '/menu/menu.html');
        require HORDE_BASE . '/services/sidebar.php';
    }

    /**
     * Outputs IMP's status/notification bar.
     */
    static public function status()
    {
        $GLOBALS['notification']->notify(array('listeners' => array('status', 'audio')));
    }

    /**
     * Add new_folder.js to the list of output javascript files.
     */
    static public function addNewFolderJs()
    {
        if ($GLOBALS['registry']->hasMethod('mail/createFolder')) {
            Horde::addScriptFile('new_folder.js', 'ingo');
            Horde::addInlineScript(array(
                'IngoNewFolder.folderprompt = ' . Horde_Serialize::serialize(_("Please enter the name of the new folder:"), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
            ));
        }
    }

}
