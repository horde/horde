<?php
/**
 * Ingo application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Ingo through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
    public $version = 'H5 (3.0.0beta2)';

    /**
     */
    protected function _bootstrap()
    {
        /* Add Ingo-specific factories. */
        $factories = array(
            'Ingo_Script' => 'Ingo_Factory_Script',
            'Ingo_Transport' => 'Ingo_Factory_Transport'
        );

        foreach ($factories as $key => $val) {
            $GLOBALS['injector']->bindFactory($key, $val, 'create');
        }
    }

    /**
     * Global variables defined:
     *   - all_rulesets
     *   - ingo_shares
     */
    protected function _init()
    {
        // Create the session.
        $this->_createSession();

        // Create shares if necessary.
        if ($GLOBALS['injector']->getInstance('Ingo_Transport')->supportShares()) {
            $GLOBALS['ingo_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
            $GLOBALS['all_rulesets'] = Ingo::listRulesets();

            /* If personal share doesn't exist then create it. */
            $signature = $GLOBALS['session']->get('ingo', 'backend/id') . ':' . $GLOBALS['registry']->getAuth();
            if (!$GLOBALS['ingo_shares']->exists($signature)) {
                $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
                $name = $identity->getValue('fullname');
                if (trim($name) == '') {
                    $name = $GLOBALS['registry']->getAuth('original');
                }
                $share = $GLOBALS['ingo_shares']->newShare($GLOBALS['registry']->getAuth(), $signature, $name);
                $GLOBALS['ingo_shares']->addShare($share);
                $GLOBALS['all_rulesets'][$signature] = $share;
            }

            /* Select current share. */
            $GLOBALS['session']->set('ingo', 'current_share', Horde_Util::getFormData('ruleset', $GLOBALS['session']->get('ingo', 'current_share')));
            if (!$GLOBALS['session']->get('ingo', 'current_share') ||
                empty($GLOBALS['all_rulesets'][$GLOBALS['session']->get('ingo', 'current_share')]) ||
                !$GLOBALS['all_rulesets'][$GLOBALS['session']->get('ingo', 'current_share')]->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
                $GLOBALS['session']->set('ingo', 'current_share', $signature);
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
     *   - storage: (array) Used by Ingo_Storage:: for caching data.
     *   - script_categories: (array) The list of available categories for the
     *                                Ingo_Script driver in use.
     *   - script_generate: (boolean) Is the Ingo_Script::generate() call
     *                                available?
     *
     * @throws Ingo_Exception
     */
    protected function _createSession()
    {
        global $injector, $prefs, $session;

        if ($session->exists('ingo', 'script_generate')) {
            return;
        }

        /* getBackend() and loadIngoScript() will both throw Exceptions, so
         * do these first as errors are fatal. */
        foreach (Ingo::getBackend() as $key => $val) {
            if ($val) {
                $session->set('ingo', 'backend/' . $key, $val);
            }
        }

        $ingo_script = $injector->getInstance('Ingo_Script');
        $session->set('ingo', 'script_generate', $ingo_script->generateAvailable());

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
        $session->set('ingo', 'script_categories', array_diff(array_merge($ingo_script->availableActions(), $ingo_script->availableCategories()), $locked));
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
        try {
            $menu->add(Horde::url('filters.php'), _("Filter _Rules"), 'ingo-rules', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showWhitelist')), _("_Whitelist"), 'ingo-whitelist');
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showBlacklist')), _("_Blacklist"), 'ingo-blacklist');
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        $s_categories = $GLOBALS['session']->get('ingo', 'script_categories');

        if (in_array(Ingo_Storage::ACTION_VACATION, $s_categories)) {
            $menu->add(Horde::url('vacation.php'), _("_Vacation"), 'ingo-vacation');
        }

        if (in_array(Ingo_Storage::ACTION_FORWARD, $s_categories)) {
            $menu->add(Horde::url('forward.php'), _("_Forward"), 'ingo-forward');
        }

        if (in_array(Ingo_Storage::ACTION_SPAM, $s_categories)) {
            $menu->add(Horde::url('spam.php'), _("S_pam"), 'ingo-spam');
        }

        if ($GLOBALS['session']->get('ingo', 'script_generate') &&
            (!$GLOBALS['prefs']->isLocked('auto_update') ||
             !$GLOBALS['prefs']->getValue('auto_update'))) {
            $menu->add(Horde::url('script.php'), _("_Script"), 'ingo-script');
        }

        if (!empty($GLOBALS['ingo_shares']) &&
            empty($GLOBALS['conf']['share']['no_sharing'])) {
            $menu->add('#', _("_Permissions"), 'horde-perms', null, '', Horde::popupJs(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php', true), array('params' => array('app' => 'ingo', 'share' => $GLOBALS['session']->get('ingo', 'backend/id') . ':' . $GLOBALS['registry']->getAuth()), 'urlencode' => true)) . 'return false;');
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
        $actions = $injector->getInstance('Ingo_Script')->availableActions();
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
