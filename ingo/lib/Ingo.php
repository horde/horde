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

    /* getMenu() cache. */
    static private $_menuCache = null;

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
                $text .= ">\n";
                $text .= '<option value="">' . _("Select target folder:") . "</option>\n";

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
                                     Horde_Text_Filter::filter($label, 'space2html', array('charset' => Horde_Nls::getCharset(), 'encode' => true)), "\n");
                }

                $text .= '</select>';
                return $text;
            } catch (Horde_Exception $e) {}
        }

        return '<input id="' . $tagname . '" name="' . $tagname . '" size="40" value="' . $value . '" />';
    }

    /**
     * Creates a new IMAP folder via an api call.
     *
     * @param string $folder  The name of the folder to create.
     *
     * @return boolean  True on success, false if not created. PEAR_Error on
     * @throws Horde_Exception
     */
    static public function createFolder($folder)
    {
        return $GLOBALS['registry']->hasMethod('mail/createFolder')
            ? $GLOBALS['registry']->call('mail/createFolder', array('folder' => Horde_String::convertCharset($folder, Horde_Nls::getCharset(), 'UTF7-IMAP')))
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
            $user = ($full ||
                     (isset($_SESSION['ingo']['backend']['hordeauth']) &&
                      $_SESSION['ingo']['backend']['hordeauth'] === 'full')) ?
                Horde_Auth::getAuth() :
                Horde_Auth::getBareAuth();
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
        global $notification;

        $driver = self::getDriver();
        $res = $driver->setScriptActive($script);
        if (is_a($res, 'PEAR_Error')) {
            $msg = ($deactivate)
              ? _("There was an error deactivating the script.")
              : _("There was an error activating the script.");
            $notification->push($msg . ' ' . _("The driver said: ") . $res->getMessage(), 'horde.error');
            return false;
        } elseif ($res === true) {
            $msg = ($deactivate)
              ? _("Script successfully deactivated.")
              : _("Script successfully activated.");
            $notification->push($msg, 'horde.success');
            return true;
        }

        return false;
    }

    /**
     * Connects to the backend and returns the currently active script.
     *
     * @return string  The currently active script.
     */
    static public function getScript()
    {
        $driver = self::getDriver();
        return $driver->getScript();
    }

    /**
     * Does all the work in updating the script on the server.
     */
    static public function updateScript()
    {
        global $notification;

        if ($_SESSION['ingo']['script_generate']) {
            $ingo_script = self::loadIngoScript();
            if (!$ingo_script) {
                $notification->push(_("Script not updated."), 'horde.error');
            } else {
                /* Generate and activate the script. */
                $script = $ingo_script->generate();
                self::activateScript($script);
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
     * @throws Horde_Exception
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
        if (!isset($backend)) {
            throw new Horde_Exception(_("No backend configured for this host"));
        }

        $backends[$backend]['id'] = $name;
        $backend = $backends[$backend];

        if (empty($backend['script'])) {
            throw new Horde_Exception(sprintf(_("No \"%s\" element found in backend configuration."), 'script'));
        } elseif (empty($backend['driver'])) {
            throw new Horde_Exception(sprintf(_("No \"%s\" element found in backend configuration."), 'driver'));
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
     * @throws Horde_Exception
     */
    static public function loadIngoScript()
    {
        $ingo_script = Ingo_Script::factory($_SESSION['ingo']['backend']['script'],
                                            isset($_SESSION['ingo']['backend']['scriptparams']) ? $_SESSION['ingo']['backend']['scriptparams'] : array());
        if (is_a($ingo_script, 'PEAR_Error')) {
            throw new Horde_Exception($ingo_script);
        }

        return $ingo_script;
    }

    /**
     * Returns an instance of the configured driver.
     *
     * @return Ingo_Driver  The configured driver.
     */
    static public function getDriver()
    {
        $params = $_SESSION['ingo']['backend']['params'];

        // Set authentication parameters.
        if (!empty($_SESSION['ingo']['backend']['hordeauth'])) {
            $params['username'] = ($_SESSION['ingo']['backend']['hordeauth'] === 'full')
                        ? Horde_Auth::getAuth() : Horde_Auth::getBareAuth();
            $params['password'] = Horde_Auth::getCredential('password');
        } elseif (isset($_SESSION['ingo']['backend']['params']['username']) &&
                  isset($_SESSION['ingo']['backend']['params']['password'])) {
            $params['username'] = $_SESSION['ingo']['backend']['params']['username'];
            $params['password'] = $_SESSION['ingo']['backend']['params']['password'];
        } else {
            $params['username'] = Horde_Auth::getBareAuth();
            $params['password'] = Horde_Auth::getCredential('password');
        }

        return Ingo_Driver::factory($_SESSION['ingo']['backend']['driver'], $params);
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
                                        $permission = PERMS_SHOW)
    {
        $rulesets = $GLOBALS['ingo_shares']->listShares(Horde_Auth::getAuth(), $permission, $owneronly ? Horde_Auth::getAuth() : null);
        if (is_a($rulesets, 'PEAR_Error')) {
            Horde::logMessage($rulesets, __FILE__, __LINE__, PEAR_LOG_ERR);
            return array();
        }

        return $rulesets;
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission, either 'allow_rules' or
     *                            'max_rules'.
     *
     * @return mixed  The value of the specified permission.
     */
    static public function hasPermission($permission, $mask = null)
    {
        if ($permission == 'shares') {
            if (!isset($GLOBALS['ingo_shares'])) {
                return true;
            }
            static $all_perms;
            if (!isset($all_perms)) {
                $all_perms = $GLOBALS['ingo_shares']->getPermissions($_SESSION['ingo']['current_share'], Horde_Auth::getAuth());
            }
            return $all_perms & $mask;
        }

        global $perms;

        if (!$perms->exists('ingo:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('ingo:' . $permission);
        if (is_array($allowed)) {
            switch ($permission) {
            case 'allow_rules':
                $allowed = (bool)count(array_filter($allowed));
                break;

            case 'max_rules':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
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
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu();
        $menu->add(Horde::applicationUrl('filters.php'), _("Filter _Rules"), 'ingo.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        if (!is_a($whitelist_url = $GLOBALS['registry']->link('mail/showWhitelist'), 'PEAR_Error')) {
            $menu->add(Horde::url($whitelist_url), _("_Whitelist"), 'whitelist.png');
        }
        if (!is_a($blacklist_url = $GLOBALS['registry']->link('mail/showBlacklist'), 'PEAR_Error')) {
            $menu->add(Horde::url($blacklist_url), _("_Blacklist"), 'blacklist.png');
        }
        if (in_array(Ingo_Storage::ACTION_VACATION, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::applicationUrl('vacation.php'), _("_Vacation"), 'vacation.png');
        }
        if (in_array(Ingo_Storage::ACTION_FORWARD, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::applicationUrl('forward.php'), _("_Forward"), 'forward.png');
        }
        if (in_array(Ingo_Storage::ACTION_SPAM, $_SESSION['ingo']['script_categories'])) {
            $menu->add(Horde::applicationUrl('spam.php'), _("S_pam"), 'spam.png');
        }
        if ($_SESSION['ingo']['script_generate'] &&
            (!$GLOBALS['prefs']->isLocked('auto_update') ||
             !$GLOBALS['prefs']->getValue('auto_update'))) {
            $menu->add(Horde::applicationUrl('script.php'), _("_Script"), 'script.png');
        }
        if (!empty($GLOBALS['ingo_shares']) && empty($GLOBALS['conf']['share']['no_sharing'])) {
            $menu->add('#', _("_Permissions"), 'perms.png', $GLOBALS['registry']->getImageDir('horde'), '', Horde::popupJs(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php', true), array('params' => array('app' => 'ingo', 'share' => $_SESSION['ingo']['backend']['id'] . ':' . Horde_Auth::getAuth()), 'urlencode' => true)) . 'return false;');
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
        if (!self::$_menuCache) {
            self::$_menuCache = self::getMenu()->render();
        }

        return self::$_menuCache;
    }

    /**
     * Add new_folder.js to the list of output javascript files.
     */
    static public function addNewFolderJs()
    {
        if ($GLOBALS['registry']->hasMethod('mail/createFolder')) {
            Horde::addScriptFile('new_folder.js', 'ingo');
            Horde::addInlineScript(array(
                'IngoNewFolder.folderprompt = ' . Horde_Serialize::serialize(_("Please enter the name of the new folder:"), Horde_Serialize::JSON, Horde_Nls::getCharset())
            ));
        }
    }

}
