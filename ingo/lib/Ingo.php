<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Ingo base class.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo
{
    /**
     * Define the key to use to indicate a user-defined header is requested.
     */
    const USER_HEADER = '++USER_HEADER++';

    /**
     * Only filter unseen messages.
     */
    const FILTER_UNSEEN = 1;

    /**
     * Only filter seen messages.
     */
    const FILTER_SEEN = 2;

    /**
     * Constants for rule types.
     */
    const RULE_ALL = 0;
    const RULE_FILTER = 1;
    const RULE_BLACKLIST = 2;
    const RULE_WHITELIST = 3;
    const RULE_VACATION = 4;
    const RULE_FORWARD = 5;
    const RULE_SPAM = 6;

    /**
     * Returns the user whose rules are currently being edited.
     *
     * @param boolean $full  Always return the full user name with realm?
     *
     * @return string  The current user.
     */
    public static function getUser($full = true)
    {
        global $injector, $registry, $session;

        if (!$injector->getInstance('Ingo_Shares')) {
            return $registry->getAuth($full ? null : 'bare');
        }

        list(, $user) = explode(':', $session->get('ingo', 'current_share'), 2);
        return $user;
    }

    /**
     * Returns the domain name, if any of the user whose rules are currently
     * being edited.
     *
     * @return string  The current user's domain name.
     */
    public static function getDomain()
    {
        $user = self::getUser(true);
        $pos = strpos($user, '@');

        return ($pos === false)
            ? false
            : substr($user, $pos + 1);
    }

    /**
     * Check share permissions.
     *
     * @param $integer $mask  Permission mask.
     *
     * @return boolean  True if user has permission.
     */
    public static function hasSharePermission($mask = null)
    {
        global $injector, $registry, $session;

        return ($share = $injector->getInstance('Ingo_Shares'))
            ? ($share->getPermissions(
                  $session->get('ingo', 'current_share'),
                  $registry->getAuth()
              ) & $mask)
            : true;
    }

    /**
     * Loads the backends.php configuration file.
     *
     * @return array  Configuration data.
     * @throws Horde_Exception
     */
    public static function loadBackends()
    {
        global $registry;

        $config = $registry->loadConfigFile('backends.php', 'backends', 'ingo');
        if (empty($config->config['backends']) ||
            !is_array($config->config['backends'])) {
            throw new Ingo_Exception(_("No backends configured in backends.php"));
        }

        $out = array();
        foreach ($config->config['backends'] as $key => $val) {
            if (empty($val['disabled'])) {
                $out[$key] = $val;
            }
        }

        return $out;
    }

    /**
     * Return Ingo's initial page.
     *
     * @return Horde_Url  URL object.
     */
    public static function getInitialPage()
    {
        global $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_SMARTMOBILE:
            return Horde::url('smartmobile.php');

        default:
            return Ingo_Basic_Filters::url();
        }
    }

}
