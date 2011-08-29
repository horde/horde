<?php
/**
 * Sam base class.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam
{
    /**
     * Determine the backend to use.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' either field
     * in the backend's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @return array  The backend entry. Calls Horde::fatal() on error.
     */
    static public function getBackend()
    {
        include SAM_BASE . '/config/backends.php';
        if (!isset($backends) || !is_array($backends)) {
            Horde::fatal(PEAR::raiseError(_("No backends configured in backends.php")), __FILE__, __LINE__);
        }

        foreach ($backends as $temp) {
            if (!isset($backend)) {
                $backend = $temp;
            } elseif (!empty($temp['preferred'])) {
                if (is_array($temp['preferred'])) {
                    foreach ($temp['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backend = $temp;
                        }
                    }
                } elseif (($temp['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($temp['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backend = $temp;
                }
            }
        }

        /* Check for valid backend configuration. */
        if (!isset($backend)) {
            Horde::fatal(PEAR::raiseError(_("No backend configured for this host")), __FILE__, __LINE__);
        } elseif (empty($backend['driver'])) {
            Horde::fatal(PEAR::raiseError(sprintf(_("No \"%s\" element found in backend configuration."), 'driver')), __FILE__, __LINE__);
        }

        /* Make sure the 'params' entry exists. */
        if (!isset($backend['params'])) {
            $backend['params'] = array();
        }

        return $backend;
    }

    /**
     * Find a list of configured attributes.
     *
     * Load the attributes configuration file or uses an already-loaded
     * cached copy. If loading for the first time, cache it for later use.
     *
     * @return array    The attributes list.
     */
    static public function getAttributes()
    {
        static $_attributes;

        if (!isset($_attributes)) {
            $_attributes = array();
            require_once SAM_BASE . '/config/attributes.php';
        }

        return $_attributes;
    }

    /**
     * Find out whether the given attribute type is informational only.
     *
     * @param string $attribute           The attribute type to check.
     *
     * @return boolean  Returns true if the given type is known to be
     *                  informational only.
     */
    static public function infoAttribute($type = '')
    {
        return in_array($type, array('description', 'spacer', 'html',
                                     'header', 'link'));
    }

    /**
     * Map the given Horde username to the value used after applying any
     * configured hooks.
     *
     * @param mixed $hordeauth            Defines how to use the authenticated
     *                                    Horde username. If set to 'full',
     *                                    will initialize the username to
     *                                    contain the @realm part. Otherwise,
     *                                    the username will initialize as a
     *                                    simple login.
     *
     * @return string   The converted username.
     */
    static public function mapUser($hordeauth)
    {
        $uid = $hordeauth === 'full' ? Horde_Auth::getAuth() : Horde_Auth::getBareAuth();
        if (!empty($GLOBALS['conf']['hooks']['username'])) {
            return Horde::callHook('_sam_hook_username', array($uid), 'sam');
        }

        return $uid;
    }

    /**
     * Build Sam's list of menu items.
     */
    static public function getMenu($returnType = 'object')
    {
        $menu = new Horde_Menu(Horde_Menu::MASK_ALL & ~Horde_Menu::MASK_PREFS);
        if ($GLOBALS['conf']['enable']['rules']) {
            $menu->add(Horde::applicationUrl('spam.php'), _("Spam Options"), 'sam.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        }
        if (!is_a($whitelist_url = $GLOBALS['registry']->link('mail/showWhitelist'), 'PEAR_Error')) {
            $menu->add(Horde::url($whitelist_url), _("Whitelist"), 'whitelist.png');
        }
        if (!is_a($blacklist_url = $GLOBALS['registry']->link('mail/showBlacklist'), 'PEAR_Error')) {
            $menu->add(Horde::url($blacklist_url), _("Blacklist"), 'blacklist.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }
}
