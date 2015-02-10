<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
    define('INGO_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(INGO_BASE . '/config/horde.local.php')) {
        include INGO_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(INGO_BASE . '/..'));
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
    public $version = 'H5 (3.2.4)';

    /**
     * Cached list of all rulesets.
     *
     * @var array
     */
    protected $_rulesets;

    /**
     */
    protected function _bootstrap()
    {
        global $injector;

        $injector->bindFactory('Ingo_Shares', 'Ingo_Factory_Shares', 'create');
    }

    /**
     */
    protected function _init()
    {
        global $registry, $session;

        // Create the session.
        if (!$session->exists('ingo', 'script_categories')) {
            Ingo_Session::create();
        }

        if ($sig = $session->get('ingo', 'personal_share')) {
            $curr_share = $session->get('ingo', 'current_share');
            $ruleset = Horde_Util::getFormData('ruleset');

            /* Select current share. */
            if (is_null($curr_share) || ($ruleset != $curr_share)) {
                $session->set('ingo', 'current_share', $ruleset);
                $all_rulesets = $this->_listRulesets();

                if (is_null($curr_share) ||
                    empty($all_rulesets[$ruleset]) ||
                    !$all_rulesets[$ruleset]->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
                    $session->set('ingo', 'current_share', $sig);
                }
            }
        }
    }

    /**
     */
    public function getInitialPage()
    {
        return strval(Ingo::getInitialPage()->setRaw(true));
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $injector, $prefs, $registry, $session;

        $s_categories = $session->get('ingo', 'script_categories');
        $vars = $injector->getInstance('Horde_Variables');

        $menu->add(Ingo_Basic_Filters::url(), _("Filter _Rules"), 'ingo-rules', null, null, null, $vars->page == 'filters' ? 'current' : '__noselection');

        try {
            if (in_array(Ingo_Storage::ACTION_WHITELIST, $s_categories)) {
                $menu->add(Horde::url($registry->link('mail/showWhitelist')), _("_Whitelist"), 'ingo-whitelist', null, null, null, $vars->page == 'whitelist' ? 'current' : '__noselection');
            }
            if (in_array(Ingo_Storage::ACTION_BLACKLIST, $s_categories)) {
                $menu->add(Horde::url($registry->link('mail/showBlacklist')), _("_Blacklist"), 'ingo-blacklist', null, null, null, $vars->page == 'blacklist' ? 'current' : '__noselection');
            }
        } catch (Horde_Exception $e) {
            Horde::log($e, 'ERR');
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

        if ($injector->getInstance('Ingo_Shares') &&
            empty($conf['share']['no_sharing'])) {
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
        global $injector, $session;

        $actions = array();
        foreach ($injector->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            $actions = array_merge($actions, $script->availableActions());
        }
        $filters = $injector->getInstance('Ingo_Factory_Storage')
            ->create()
            ->retrieve(Ingo_Storage::ACTION_FILTERS)
            ->getFilterList();

        if (!empty($actions)) {
            $max = $injector->getInstance('Horde_Core_Perms')->hasAppPermission(Ingo_Perms::getPerm('max_rules'));
            if (($max === true) || ($max > count($filters))) {
                $sidebar->addNewButton(_("New Rule"), Ingo_Basic_Rule::url());
            }
        }

        if ($injector->getInstance('Ingo_Shares') &&
            (count($all_rulesets = $this->_listRulesets()) > 1)) {
            $url = Ingo_Basic_Filters::url();
            $current = $session->get('ingo', 'current_share');

            $sidebar->containers['rulesets'] = array(
                'header' => array(
                    'id' => 'ingo-toggle-rules',
                    'label' => _("Ruleset"),
                    'collapsed' => false,
                ),
            );
            foreach ($all_rulesets as $id => $ruleset) {
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
    public function perms()
    {
        return $GLOBALS['injector']->getInstance('Ingo_Perms')->perms();
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        return $GLOBALS['injector']->getInstance('Ingo_Perms')->hasPermission($permission, $allowed, $opts);
    }

    /**
     */
    public function removeUserData($user)
    {
        global $injector;

        /* Remove all filters/rules owned by the user. */
        try {
            $injector->getInstance('Ingo_Factory_Storage')->create()->removeUserData($user);
        } catch (Ingo_Exception $e) {
            Horde::log($e, 'ERR');
            throw $e;
        }

        /* Now remove all shares owned by the user. */
        if ($ingo_shares = $injector->getInstance('Ingo_Shares')) {
            /* Get the user's default share. */
            try {
                $ingo_shares->removeShare($ingo_shares->getShare($user));
            } catch (Horde_Share_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Ingo_Exception($e);
            }

            /* Get a list of all shares this user has perms to and remove the
             * perms. */
            try {
                $shares = $ingo_shares->listShares($user);
                foreach ($shares as $share) {
                    $share->removeUser($user);
                }
            } catch (Horde_Share_Exception $e) {
                Horde::log($e, 'ERR');
            }

            /* Get a list of all shares this user owns and has perms to delete
             * and remove them. */
            try {
                $shares = $ingo_shares->listShares($user, array(
                    'attributes' => $user,
                    'perm' => Horde_Perms::DELETE
                ));
            } catch (Horde_Share_Exception $e) {
                Horde::log($e, 'ERR');
                throw new Ingo_Exception($e);
            }

            foreach ($shares as $share) {
                $ingo_shares->removeShare($share);
            }
        }
    }

    /**
     * Returns all rulesets a user has access to.
     *
     * @return array  The ruleset list.
     */
    protected function _listRulesets()
    {
        global $injector, $registry;

        if (isset($this->_rulesets)) {
            return $this->_rulesets;
        }

        $this->_rulesets = array();

        try {
            if (!($share = $injector->getInstance('Ingo_Shares'))) {
                return $this->_rulesets;
            }

            $tmp = $share->listShares(
                $registry->getAuth(),
                array('perm' => Horde_Perms::SHOW)
            );
        } catch (Horde_Share_Exception $e) {
            Horde::log($e, 'ERR');
            return $this->_rulesets;
        }

        /* Check if filter backend of the share still exists. */
        $backends = Ingo::loadBackends();

        foreach ($tmp as $id => $ruleset) {
            list($backend) = explode(':', $id);
            if (isset($backends[$backend])) {
                $this->_rulesets[$id] = $ruleset;
            }
        }

        return $this->_rulesets;
    }

}
