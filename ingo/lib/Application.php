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
 * @package Ingo
 */

/* Determine the base directories. */
if (!defined('INGO_BASE')) {
    define('INGO_BASE', dirname(__FILE__) . '/..');
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
 */
class Ingo_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (2.0.8-git)';

    /**
     * Global variables defined:
     *   $all_rulesets - TODO
     *   $ingo_shares - TODO
     *   $ingo_storage - TODO
     */
    protected function _init()
    {
        // Load the Ingo_Storage driver.
        $GLOBALS['ingo_storage'] = Ingo_Storage::factory();

        // Create the ingo session.
        Ingo::createSession();

        // Create shares if necessary.
        $transport = Ingo::getTransport();
        if ($transport->supportShares()) {
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
            $menu->add(Horde::url('filters.php'), _("Filter _Rules"), 'ingo.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showWhitelist')), _("_Whitelist"), 'whitelist.png');
            $menu->add(Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->link('mail/showBlacklist')), _("_Blacklist"), 'blacklist.png');
        } catch (Horde_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
        }

        $s_categories = $GLOBALS['session']->get('ingo', 'script_categories');

        if (in_array(Ingo_Storage::ACTION_VACATION, $s_categories)) {
            $menu->add(Horde::url('vacation.php'), _("_Vacation"), 'vacation.png');
        }

        if (in_array(Ingo_Storage::ACTION_FORWARD, $s_categories)) {
            $menu->add(Horde::url('forward.php'), _("_Forward"), 'forward.png');
        }

        if (in_array(Ingo_Storage::ACTION_SPAM, $s_categories)) {
            $menu->add(Horde::url('spam.php'), _("S_pam"), 'spam.png');
        }

        if ($GLOBALS['session']->get('ingo', 'script_generate') &&
            (!$GLOBALS['prefs']->isLocked('auto_update') ||
             !$GLOBALS['prefs']->getValue('auto_update'))) {
            $menu->add(Horde::url('script.php'), _("_Script"), 'script.png');
        }

        if (!empty($GLOBALS['ingo_shares']) && empty($GLOBALS['conf']['share']['no_sharing'])) {
            $menu->add('#', _("_Permissions"), 'perms.png', null, '', Horde::popupJs(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php', true), array('params' => array('app' => 'ingo', 'share' => $GLOBALS['session']->get('ingo', 'backend/id') . ':' . $GLOBALS['registry']->getAuth()), 'urlencode' => true)) . 'return false;');
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
            $GLOBALS['ingo_storage']->removeUserData($user);
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
                $shares = $GLOBALS['ingo_shares']->listShares($user, array('perm' => Horde_Perms::DELETE,
                                                                           'attributes' => $user));
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Ingo_Exception($e);
            }

            foreach ($shares as $share) {
                $GLOBALS['ingo_shares']->removeShare($share);
            }
        }
    }

    /**
     */
    public function prefsInit($ui)
    {
        if (!$GLOBALS['session']->get('ingo', 'script_generate')) {
            $ui->suppressGroups[] = 'script';
        }
    }
}
