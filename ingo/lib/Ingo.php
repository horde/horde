<?php
/**
 * Ingo base class.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
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
     * hasSharePermission() cache.
     *
     * @var integer
     */
    static private $_shareCache = null;

    /**
     * Create an ingo session.
     *
     * Creates the $ingo session variable with the following entries:
     * <pre>
     * 'backend' (array) - The backend configuration to use.
     * 'change' (integer) - The timestamp of the last time the rules were
     *                      altered.
     * 'storage' (array) - Used by Ingo_Storage:: for caching data.
     * 'script_categories' (array) - The list of available categories for the
     *                               Ingo_Script driver in use.
     * 'script_generate' (boolean) - Is the Ingo_Script::generate() call
     *                               available?
     * </pre>
     *
     * @throws Ingo_Exception
     */
    static public function createSession()
    {
        global $prefs, $session;

        if ($session->exists('ingo', 'script_generate')) {
            return;
        }

        /* getBackend() and loadIngoScript() will both throw Exceptions, so
         * do these first as errors are fatal. */
        foreach (self::getBackend() as $key => $val) {
            if ($val) {
                $session->set('ingo', 'backend/' . $key, $val);
            }
        }

        $ingo_script = self::loadIngoScript();
        $session->set('ingo', 'script_generate', $ingo_script->generateAvailable());

        /* Disable categories as specified in preferences */
        $categories = array_flip(
            array_merge($ingo_script->availableActions(),
                        $ingo_script->availableCategories()));
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
        $categories = array_flip($categories);

        /* Set the list of categories this driver supports. */
        $session->set('ingo', 'script_categories', $categories);
    }

    /**
     * Generates a folder widget.
     * If an application is available that provides a folderlist method
     * then a &lt;select&gt; input is created. Otherwise a simple text field
     * is returned.
     *
     * @param string $value    The current value for the field.
     * @param string $form     The form name for the newFolderName() call.
     * @param string $tagname  The label for the select tag.
     *
     * @return string  The HTML to render the field.
     */
    static public function flistSelect($value = null, $form = null,
                                       $tagname = 'actionvalue')
    {
        global $conf, $registry;

        if ($registry->hasMethod('mail/folderlist')) {
            $createfolder = $registry->hasMethod('mail/createFolder');
            try {
                $mailboxes = $registry->call('mail/folderlist');

                $text = '<select class="flistSelect" id="' . $tagname . '" name="' . $tagname . '">' .
                    '<option value="">' . _("Select target folder:") . '</option>' .
                    '<option disabled="disabled">- - - - - - - - - -</option>';

                if ($createfolder) {
                    $text .= '<option class="flistCreate" value="">' . _("Create new folder") . '</option>' .
                        '<option disabled="disabled">- - - - - - - - - -</option>';
                }

                foreach ($mailboxes as $key => $val) {
                    $text .= sprintf(
                        "<option value=\"%s\"%s>%s</option>\n",
                        htmlspecialchars($key),
                        ($key === $value) ? ' selected="selected"' : '',
                        str_repeat('&nbsp;', $val['level'] * 2) . htmlspecialchars($val['label'])
                    );
                }

                Horde::addScriptFile('new_folder.js', 'ingo');
                Horde::addInlineJsVars(array(
                    'IngoNewFolder.folderprompt' => _("Please enter the name of the new folder:")
                ));

                return $text . '</select>';
            } catch (Horde_Exception $e) {}
        }

        return '<input id="' . $tagname . '" name="' . $tagname . '" size="40" value="' . $value . '" />';
    }

    /**
     * Validates an IMAP mailbox provided by user input.
     *
     * @param Horde_Variables $vars  An variables object.
     * @param string $name           The form name of the folder input.
     *
     * @return string  The IMAP mailbox name.
     * @throws Horde_Exception
     */
    static public function validateFolder(Horde_Variables $vars, $name)
    {
        $new_id = $name . '_new';
        if (isset($vars->$new_id)) {
            if ($GLOBALS['registry']->hasMethod('mail/createFolder') &&
                $GLOBALS['registry']->call('mail/createFolder', array(Horde_String::convertCharset($vars->$new_id, 'UTF-8', 'UTF7-IMAP')))) {
                return $vars->$new_id;
            }
        } elseif (isset($vars->$name) && strlen($vars->$name)) {
            return $vars->$name;
        }

        throw new Ingo_Exception(_("Could not validate IMAP mailbox."));
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
                        ($GLOBALS['session']->get('ingo', 'backend/hordeauth') === 'full'));
            $user = $GLOBALS['registry']->getAuth($baseuser ? null : 'bare');
        } else {
            list(, $user) = explode(':', $GLOBALS['session']->get('ingo', 'current_share'), 2);
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
     * @param array $additional    Any additional scripts that need to uploaded.
     *
     * @return boolean  True on success, false on failure.
     */
    static public function activateScript($script, $deactivate = false,
                                          $additional = array())
    {
        $transport = self::getTransport();

        try {
            $transport->setScriptActive($script, $additional);
        } catch (Ingo_Exception $e) {
            $msg = ($deactivate)
              ? _("There was an error deactivating the script.")
              : _("There was an error activating the script.");
            $GLOBALS['notification']->push($msg . ' ' . _("The driver said: ") . $e->getMessage(), 'horde.error');
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
        if ($GLOBALS['session']->get('ingo', 'script_generate')) {
            try {
                $ingo_script = self::loadIngoScript();

                /* Generate and activate the script. */
                self::activateScript($ingo_script->generate(),
                                     false,
                                     $ingo_script->additionalScripts());
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
        $backends = Horde::loadConfiguration('backends.php', 'backends', 'ingo');
        if (!isset($backends) || !is_array($backends)) {
            throw new Ingo_Exception(_("No backends configured in backends.php"));
        }

        $backend = null;
        foreach ($backends as $name => $temp) {
            if (!empty($temp['disabled'])) {
                continue;
            }
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

        foreach (array('script', 'transport') as $val) {
            if (empty($backend[$val])) {
                throw new Ingo_Exception(sprintf(_("No \"%s\" element found in backend configuration."), $val));
            }
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
        return Ingo_Script::factory(
            $GLOBALS['session']->get('ingo', 'backend/script'),
            $GLOBALS['session']->get('ingo', 'backend/scriptparams', Horde_Session::TYPE_ARRAY)
        );
    }

    /**
     * Returns an instance of the configured transport driver.
     *
     * @return Ingo_Transport  The configured driver.
     * @throws Ingo_Exception
     */
    static public function getTransport()
    {
        global $registry, $session;

        $params = $session->get('ingo', 'backend/params');

        // Set authentication parameters.
        if (($hordeauth = $session->get('ingo', 'backend/hordeauth')) ||
            !isset($params['username']) ||
            !isset($params['password'])) {
            $params['username'] = $registry->getAuth(($hordeauth === 'full') ? null : 'bare');
            $params['password'] = $registry->getAuthCredential('password');
        }

        return Ingo_Transport::factory($GLOBALS['session']->get('ingo', 'backend/transport'), $params);
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
            $rulesets = $GLOBALS['ingo_shares']->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => $permission,
                      'attributes' => $owneronly ? $GLOBALS['registry']->getAuth() : null));
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
            self::$_shareCache = $GLOBALS['ingo_shares']->getPermissions($GLOBALS['session']->get('ingo', 'current_share'), $GLOBALS['registry']->getAuth());
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
     * Create ingo's menu.
     *
     * @return string  The menu text.
     */
    static public function menu()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->set('form_url', Horde::url('filters.php'));
        $t->set('forminput', Horde_Util::formInput());

        if (!empty($GLOBALS['ingo_shares']) &&
            (count($GLOBALS['all_rulesets']) > 1)) {
            $options = array();
            foreach (array_keys($GLOBALS['all_rulesets']) as $id) {
                $options[] = array(
                    'name' => htmlspecialchars($GLOBALS['all_rulesets'][$id]->get('name')),
                    'selected' => ($GLOBALS['session']->get('ingo', 'current_share') == $id),
                    'val' => htmlspecialchars($id)
                );
            }
            $t->set('options', $options);
        }

        $t->set('menu_string', Horde::menu(array('menu_ob' => true))->render());

        $menu = $t->fetch(INGO_TEMPLATES . '/menu/menu.html');

        /* Need to buffer sidebar output here, because it may add things like
         * cookies which need to be sent before output begins. */
        Horde::startBuffer();
        require HORDE_BASE . '/services/sidebar.php';
        return $menu . Horde::endBuffer();
    }

    /**
     * Outputs Ingo's status/notification bar.
     */
    static public function status()
    {
        $GLOBALS['notification']->notify(array('listeners' => array('status', 'audio')));
    }

}
