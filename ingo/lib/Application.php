<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/* Determine the base directories. */
if (!defined('INGO_BASE')) {
    define('INGO_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(INGO_BASE . '/config/horde.local.php')) {
        include INGO_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', INGO_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

/**
 * Ingo application API.
 *
 * This class defines Horde's core API interface. Other core Horde libraries
 * can interact with Ingo through this API.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H5 (3.1.2)';

    /**
     * Global variables defined:
     *   - all_rulesets
     *   - ingo_shares
     */
    protected function _init()
    {
        global $injector, $registry, $session;

        // Create the session.
        $this->_createSession();

        if ($sig = $session->get('ingo', 'personal_share')) {
            $GLOBALS['ingo_shares'] = $injector->getInstance('Horde_Core_Factory_Share')->create();
            $GLOBALS['all_rulesets'] = Ingo::listRulesets();

            $curr_share = $session->get('ingo', 'current_share');
            $ruleset = Horde_Util::getFormData('ruleset');

            /* Select current share. */
            if (is_null($curr_share) || ($ruleset != $curr_share)) {
                $session->set('ingo', 'current_share', $ruleset);
                if (is_null($curr_share) ||
                    empty($GLOBALS['all_rulesets'][$ruleset]) ||
                    !$GLOBALS['all_rulesets'][$ruleset]->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
                    $session->set('ingo', 'current_share', $sig);
                }
            }
        } else {
            $GLOBALS['ingo_shares'] = null;
        }
    }

    /**
     * Create an ingo session.
     *
     * Session entries:
     *   - backend: (array) The backend configuration to use.
     *   - change: (integer) The timestamp of the last time the rules were
     *                       altered.
     *   - personal_share: (string) Personal share signature.
     *   - storage: (array) Used by Ingo_Storage:: for caching data.
     *   - script_categories: (array) The list of available categories for the
     *                                Ingo_Script driver in use.
     *
     * @throws Ingo_Exception
     */
    protected function _createSession()
    {
        global $injector, $prefs, $registry, $session;

        if ($session->exists('ingo', 'script_categories')) {
            return;
        }

        /* getBackend() and loadIngoScript() will both throw Exceptions, so
         * do these first as errors are fatal. */
        foreach (array_filter(Ingo::getBackend()) as $key => $val) {
            $session->set('ingo', 'backend/' . $key, $val);
        }

        /* Disable categories as specified in preferences */
        $locked_prefs = array(
            'blacklist' => Ingo_Storage::ACTION_BLACKLIST,
            'forward' => Ingo_Storage::ACTION_FORWARD,
            'spam' => Ingo_Storage::ACTION_SPAM,
            'vacation' => Ingo_Storage::ACTION_VACATION,
            'whitelist' => Ingo_Storage::ACTION_WHITELIST
        );
        $locked = array();
        foreach ($locked_prefs as $key => $val) {
            if ($prefs->isLocked($key)) {
                $locked[] = $val;
            }
        }

        /* Set the list of categories this driver supports. */
        $ingo_scripts = $injector->getInstance('Ingo_Factory_Script')
            ->createAll();
        $categories = array();
        foreach ($ingo_scripts as $ingo_script) {
            $categories = array_merge($categories,
                                      $ingo_script->availableActions(),
                                      $ingo_script->availableCategories());
        }
        $session->set('ingo', 'script_categories',
                      array_diff($categories, $locked));

        /* Create shares if necessary. */
        $factory = $injector->getInstance('Ingo_Factory_Transport');
        foreach ($session->get('ingo', 'backend/transport', Horde_Session::TYPE_ARRAY) as $transport) {
            if ($factory->create($transport)->supportShares()) {
                $shares = $injector->getInstance('Horde_Core_Factory_Share')->create();

                /* If personal share doesn't exist then create it. */
                $sig = $session->get('ingo', 'backend/id') . ':' . $registry->getAuth();
                if (!$shares->exists($sig)) {
                    $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
                    $name = $identity->getValue('fullname');
                    if (trim($name) == '') {
                        $name = $registry->getAuth('original');
                    }
                    $share = $shares->newShare($registry->getAuth(), $sig, $name);
                    $shares->addShare($share);
                }

                $session->set('ingo', 'personal_share', $sig);
                break;
            }
        }
    }

    /**
     */
    public function perms()
    {
        return array(
            'allow_rules' => array(
                'title' => _("Allow Rules"),
                'type' => 'boolean'
            ),
            'max_rules' => array(
                'title' => _("Maximum Number of Rules"),
                'type' => 'int'
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $ingo_shares, $injector, $prefs, $registry, $session;

        $s_categories = $session->get('ingo', 'script_categories');
        $vars = $injector->getInstance('Horde_Variables');

        $menu->add(Ingo_Basic_Filters::url(), _("Filter _Rules"), 'ingo-rules', null, null, null, $vars->page == 'filters' ? 'current' : '__noselection');

        try {
            if (in_array(Ingo_Storage::ACTION_WHITELIST, $s_categories)) {
                $menu->add(Horde::url($injector->getInstance('Horde_Registry')->link('mail/showWhitelist')), _("_Whitelist"), 'ingo-whitelist', null, null, null, $vars->page == 'whitelist' ? 'current' : '__noselection');
            }
            if (in_array(Ingo_Storage::ACTION_BLACKLIST, $s_categories)) {
                $menu->add(Horde::url($injector->getInstance('Horde_Registry')->link('mail/showBlacklist')), _("_Blacklist"), 'ingo-blacklist', null, null, null, $vars->page == 'blacklist' ? 'current' : '__noselection');
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        if (in_array(Ingo_Storage::ACTION_VACATION, $s_categories)) {
            $menu->add(Ingo_Basic_Vacation::url(), _("_Vacation"), 'ingo-vacation', null, null, null, $vars->page == 'vacation' ? 'current' : '__noselection');
        }

        if (in_array(Ingo_Storage::ACTION_FORWARD, $s_categories)) {
            $menu->add(Ingo_Basic_Forward::url(), _("_Forward"), 'ingo-forward', null, null, null, $vars->page == 'forward' ? 'current' : '__noselection');
        }

        if (in_array(Ingo_Storage::ACTION_SPAM, $s_categories)) {
            $menu->add(Ingo_Basic_Spam::url(), _("S_pam"), 'ingo-spam', null, null, null, $vars->page == 'spam' ? 'current' : '__noselection');
        }

        if ((!$prefs->isLocked('auto_update') ||
             !$prefs->getValue('auto_update')) &&
            $injector->getInstance('Ingo_Factory_Script')->hasFeature('script_file')) {
            $menu->add(Ingo_Basic_Script::url(), _("_Script"), 'ingo-script', null, null, null, $vars->page == 'script' ? 'current' : '__noselection');
        }

        if (!empty($ingo_shares) && empty($conf['share']['no_sharing'])) {
            $menu->add(
                '#',
                _("_Permissions"),
                'horde-perms',
                null,
                '',
                Horde::popupJs(
                    Horde::url(
                        $registry->get('webroot', 'horde')
                            . '/services/shares/edit.php',
                        true
                    ),
                    array(
                        'params' => array(
                            'app' => 'ingo',
                            'share' => $session->get('ingo', 'backend/id')
                                . ':' . $registry->getAuth()
                        ),
                        'urlencode' => true
                    )
                ) . 'return false;'
            );
        }
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        global $injector;

        $perms = $injector->getInstance('Horde_Core_Perms');
        $actions = array();
        foreach ($injector->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            $actions = array_merge($actions, $script->availableActions());
        }
        $filters = $injector->getInstance('Ingo_Factory_Storage')
            ->create()
            ->retrieve(Ingo_Storage::ACTION_FILTERS)
            ->getFilterList();

        if (!empty($actions) &&
            ($perms->hasAppPermission('allow_rules') &&
             ($perms->hasAppPermission('max_rules') === true ||
              $perms->hasAppPermission('max_rules') > count($filters)))) {
            $sidebar->addNewButton(_("New Rule"), Ingo_Basic_Rule::url());
        }

        if (!empty($GLOBALS['ingo_shares']) &&
            (count($GLOBALS['all_rulesets']) > 1)) {
            $url = Ingo_Basic_Filters::url();
            $current = $GLOBALS['session']->get('ingo', 'current_share');

            $sidebar->containers['rulesets'] = array(
                'header' => array(
                    'id' => 'ingo-toggle-rules',
                    'label' => _("Ruleset"),
                    'collapsed' => false,
                ),
            );
            foreach ($GLOBALS['all_rulesets'] as $id => $ruleset) {
                $row = array(
                    'selected' => ($current == $id),
                    'url' => $url->add('ruleset', $id),
                    'label' => $ruleset->get('name'),
                    'type' => 'radiobox',
                );
                $sidebar->addRow($row, 'rulesets');
            }
        }
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
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
     */
    public function removeUserData($user)
    {
        /* Remove all filters/rules owned by the user. */
        try {
            $GLOBALS['injector']->getInstance('Ingo_Factory_Storage')->create()->removeUserData($user);
        } catch (Ingo_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw $e;
        }

        /* Now remove all shares owned by the user. */
        if (!empty($GLOBALS['ingo_shares'])) {
            /* Get the user's default share. */
            try {
                $share = $GLOBALS['ingo_shares']->getShare($user);
                $GLOBALS['ingo_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Ingo_Exception($e);
            }

            /* Get a list of all shares this user has perms to and remove the
             * perms. */
            try {
                $shares = $GLOBALS['ingo_shares']->listShares($user);
                foreach ($shares as $share) {
                    $share->removeUser($user);
                }
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }

            /* Get a list of all shares this user owns and has perms to delete
             * and remove them. */
            try {
                $shares = $GLOBALS['ingo_shares']->listShares($user, array(
                    'attributes' => $user,
                    'perm' => Horde_Perms::DELETE
                ));
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Ingo_Exception($e);
            }

            foreach ($shares as $share) {
                $GLOBALS['ingo_shares']->removeShare($share);
            }
        }
    }

}
