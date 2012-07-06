<?php
/**
 * Ingo base class.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
     * Generates a folder widget.
     *
     * If an application is available that provides a mailboxList method
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
        global $conf, $page_output, $registry;

        if ($registry->hasMethod('mail/mailboxList')) {
            try {
                $mailboxes = $registry->call('mail/mailboxList');

                $text = '<select class="flistSelect" id="' . $tagname . '" name="' . $tagname . '">' .
                    '<option value="">' . _("Select target folder:") . '</option>' .
                    '<option disabled="disabled">- - - - - - - - - -</option>';

                if ($registry->hasMethod('mail/createMailbox')) {
                    $text .= '<option class="flistCreate" value="">' . _("Create new folder") . '</option>' .
                        '<option disabled="disabled">- - - - - - - - - -</option>';
                }

                foreach ($mailboxes as $val) {
                    $text .= sprintf(
                        "<option value=\"%s\"%s>%s</option>\n",
                        htmlspecialchars($val['ob']->utf7imap),
                        ($key === $value) ? ' selected="selected"' : '',
                        str_repeat('&nbsp;', $val['level'] * 2) . htmlspecialchars($val['label'])
                    );
                }

                $page_output->addScriptFile('new_folder.js');
                $page_output->addInlineJsVars(array(
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
            if ($GLOBALS['registry']->hasMethod('mail/createMailbox') &&
                $GLOBALS['registry']->call('mail/createMailbox', $vars->$new_id)) {
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
            return $GLOBALS['registry']->getAuth($baseuser ? null : 'bare');
        }

        list(, $user) = explode(':', $GLOBALS['session']->get('ingo', 'current_share'), 2);
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
     * @throws Ingo_Exception
     */
    static public function activateScript($script, $deactivate = false,
                                          $additional = array())
    {
        try {
            $GLOBALS['injector']
                ->getInstance('Ingo_Transport')
                ->setScriptActive($script, $additional);
        } catch (Ingo_Exception $e) {
            $msg = $deactivate
              ? _("There was an error deactivating the script.")
              : _("There was an error activating the script.");
            throw new Ingo_Exception(sprintf(_("%s The driver said: %s"), $msg, $e->getMessage()));
        }

        $msg = ($deactivate)
            ? _("Script successfully deactivated.")
            : _("Script successfully activated.");
        $GLOBALS['notification']->push($msg, 'horde.success');
    }

    /**
     * Does all the work in updating the script on the server.
     *
     * @throws Ingo_Exception
     */
    static public function updateScript()
    {
        if ($GLOBALS['session']->get('ingo', 'script_generate')) {
            try {
                $ingo_script = $GLOBALS['injector']->getInstance('Ingo_Script');

                /* Generate and activate the script. */
                self::activateScript(
                    $ingo_script->generate(),
                    false,
                    $ingo_script->additionalScripts());
            } catch (Ingo_Exception $e) {
                throw new Ingo_Exception(sprintf(_("Script not updated: %s"), $e->getMessage()));
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
     * Returns the vacation reason with all placeholder replaced.
     *
     * @param string $reason  The vacation reason including placeholders.
     * @param integer $start  The vacation start timestamp.
     * @param integer $end    The vacation end timestamp.
     *
     * @return string  The vacation reason suitable for usage in the filter
     *                 scripts.
     */
    static public function getReason($reason, $start, $end)
    {
        $identity = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create(Ingo::getUser());
        $format = $GLOBALS['prefs']->getValue('date_format');

        return str_replace(array('%NAME%',
                                 '%EMAIL%',
                                 '%SIGNATURE%',
                                 '%STARTDATE%',
                                 '%ENDDATE%'),
                           array($identity->getName(),
                                 $identity->getDefaultFromAddress(),
                                 $identity->getValue('signature'),
                                 $start ? strftime($format, $start) : '',
                                 $end ? strftime($format, $end) : ''),
                           $reason);
    }

    /**
     * Create ingo's menu.
     *
     * @return string  The menu text.
     */
    static public function menu()
    {
        global $injector;

        $sidebar = Horde::menu(array('menu_ob' => true))->render();
        $perms = $injector->getInstance('Horde_Core_Perms');
        $actions = $injector->getInstance('Ingo_Script') ->availableActions();
        $filters = $injector->getInstance('Ingo_Factory_Storage')
            ->create()
            ->retrieve(Ingo_Storage::ACTION_FILTERS)
            ->getFilterList();

        if (!empty($actions) &&
            ($perms->hasAppPermission('allow_rules') &&
             ($perms->hasAppPermission('max_rules') === true ||
              $perms->hasAppPermission('max_rules') > count($filters)))) {
            $sidebar->addNewButton(_("New Rule"), Horde::url('rule.php'));
        }

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

        $t->set('menu_string', $sidebar->render());

        $menu = $t->fetch(INGO_TEMPLATES . '/menu/menu.html');

        return $GLOBALS['injector']
            ->getInstance('Horde_View_Topbar')
            ->render()
            . $menu;
    }

    /**
     * Outputs Ingo's status/notification bar.
     */
    static public function status()
    {
        $GLOBALS['notification']->notify(array(
            'listeners' => array('status', 'audio')
        ));
    }

    /**
     * Updates a list (blacklist/whitelist) filter.
     *
     * @param mixed $addresses  Addresses of the filter.
     * @param integer $type     Type of filter.
     *
     * @return Horde_Storage_Rule  The filter object.
     */
    static public function updateListFilter($addresses, $type)
    {
        global $injector;

        $storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $rule = $storage->retrieve($type);

        switch ($type) {
        case $storage::ACTION_BLACKLIST:
            $rule->setBlacklist($addresses);
            $addr = $rule->getBlacklist();

            $rule2 = $storage->retrieve($storage::ACTION_WHITELIST);
            $addr2 = $rule2->getWhitelist();
            break;

        case $storage::ACTION_WHITELIST:
            $rule->setWhitelist($addresses);
            $addr = $rule->getWhitelist();

            $rule2 = $storage->retrieve($storage::ACTION_BLACKLIST);
            $addr2 = $rule2->getBlacklist();
            break;
        }

        /* Filter out the rule's addresses in the opposite filter. */
        $ob = new Horde_Mail_Rfc822_List($addr2);
        $ob->setIteratorFilter(0, $addr);

        switch ($type) {
        case $storage::ACTION_BLACKLIST:
            $rule2->setWhitelist($ob->bare_addresses);
            break;

        case $storage::ACTION_WHITELIST:
            $rule2->setBlacklist($ob->bare_addresses);
            break;
        }

        $storage->store($rule);
        $storage->store($rule2);

        return $rule;
    }

}
