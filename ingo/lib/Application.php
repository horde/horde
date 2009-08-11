<?php
/**
 * Ingo application API.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */
class Ingo_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        return array(
            'title' => array(
                'ingo:allow_rules' => _("Allow Rules"),
                'ingo:max_rules' => _("Maximum Number of Rules")
            ),
            'tree' => array(
                'ingo' => array(
                    'allow_rules' => false,
                    'max_rules' => false
                )
            ),
            'type' => array(
                'ingo:allow_rules' => 'boolean',
                'ingo:max_rules' => 'int'
            )
        );
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ingo::getMenu();
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @return mixed  true on success | PEAR_Error on failure
     */
    public function removeUserData($user)
    {
        if (!Horde_Auth::isAdmin() && $user != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("You are not allowed to remove user data."));
        }

        require_once dirname(__FILE__) . '/../lib/base.php';

        /* Remove all filters/rules owned by the user. */
        $result = $GLOBALS['ingo_storage']->removeUserData($user);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Now remove all shares owned by the user. */
        if (!empty($GLOBALS['ingo_shares'])) {
            /* Get the user's default share. */
            $share = $GLOBALS['ingo_shares']->getShare($user);
            if (is_a($share, 'PEAR_Error')) {
                Horde::logMessage($share, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $share;
            } else {
                $result = $GLOBALS['ingo_shares']->removeShare($share);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $result;
                }
            }

            /* Get a list of all shares this user has perms to and remove the
             * perms. */
            $shares = $GLOBALS['ingo_shares']->listShares($user);
            if (is_a($shares, 'PEAR_Error')) {
                Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
            foreach ($shares as $share) {
                $share->removeUser($user);
            }

            /* Get a list of all shares this user owns and has perms to delete
             * and remove them. */
            $shares = $GLOBALS['ingo_shares']->listShares($user, PERMS_DELETE, $user);
            if (is_a($shares, 'PEAR_Error')) {
                Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $shares;
            }
            foreach ($shares as $share) {
                $GLOBALS['ingo_shares']->removeShare($share);
            }
        }

        return true;
    }

}
