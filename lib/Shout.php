<?php

@define(SHOUT_ASTERISK_BRANCH, "ou=Asterisk");
@define(SHOUT_USERS_BRANCH, "ou=Customers");
@define(SHOUT_USER_OBJECTCLASS, "asteriskUser");

@define(SHOUT_CONTEXT_ALL, 0);
@define(SHOUT_CONTEXT_CUSTOMERS, 1 << 0);
@define(SHOUT_CONTEXT_EXTENSIONS, 1 << 1);
@define(SHOUT_CONTEXT_MOH, 1 << 2);
@define(SHOUT_CONTEXT_CONFERENCE, 1 << 3);

// {{{ Class Shout
class Shout
{

    // {{{ getMenu method
    /**
     * Build Shout's list of menu items.
     *
     * @access public
     */
    function getMenu($returnType = 'object')
    {
        global $conf, $context, $section, $action;

        require_once 'Horde/Menu.php';

        $menu = &new Menu(HORDE_MENU_MASK_ALL);

        if (isset($context) && isset($section) && $section == "users" &&
            Shout::checkRights("shout:contexts:$context:users",
                PERMS_EDIT, 1)) {
            $url = Horde::applicationUrl("users.php");
            $url = Util::addParameter($url, array('context' => $context,
                                                  'section' => $section,
                                                  'action' => 'add'));
            
            # Goofy hack to make the icon make a little more sense
            # when editing/deleting users
            if (!isset($action)) {
                $icontitle = "Add";
            } else {
                $icontitle = $action;
                $icontitle[0] = strtoupper($action[0]);
            }
            # End goofy hack

            $menu->add($url, _("$icontitle User"), "add-user.gif");
        }

        if (isset($context) && isset($section) && $section == "dialplan" &&
            Shout::checkRights("shout:contexts:$context:dialplan",
                PERMS_EDIT, 1)) {
            $url = Horde::applicationUrl("dialplan.php");
            $url = Util::addParameter($url, array('context' => $context,
                                                  'section' => $section,
                                                  'action' => 'add'));
            
            # Goofy hack to make the icon make a little sense
            # when editing/deleting users
            if (!isset($action)) {
                $icontitle = "Add";
            } else {
                $icontitle = $action;
                $icontitle[0] = strtoupper($action[0]);
            }
            # End goofy hack

            $menu->add($url, _("$icontitle Extension"), "add-extension.gif");
        }
        
        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }
    // }}}

    // {{{
    /**
     * Generate the tabs at the top of each Shout pages
     *
     * @param &$vars Reference to the passed in variables
     *
     * @return object Horde_UI_Tabs
     */
    function &getTabs($context, &$vars)
    {
        global $shout;
        if (!Auth::isAdmin("shout", PERMS_SHOW|PERMS_READ)) {
            return false;
        }

        $permprefix = "shout:contexts:$context";

        $tabs = &new Horde_UI_Tabs('section', $vars);

        if (Shout::checkRights("$permprefix:users", null, 1) &&
            $shout->checkContextType($context, "users")) {
            $tabs->addTab(_("Users"),
                    Horde::applicationUrl("index.php?context=$context"),
                    'users');
        }

        if (Shout::checkRights("$permprefix:dialplan", null, 1) &&
            $shout->checkContextType($context, "dialplan")) {
            $tabs->addTab(_("Dial Plan"),
                Horde::applicationUrl('index.php'), 'dialplan');
        }

        if (Shout::checkRights("$permprefix:conference", null, 1) &&
            $shout->checkContextType($context, "conference")) {
            $tabs->addTab(_("Conference Rooms"),
                Horde::applicationUrl('index.php'), 'conference');
        }

       if (Shout::checkRights("$permprefix:moh", null, 1) &&
            $shout->checkContextType($context, "moh")) {
            $tabs->addTab(_("Music on Hold"),
                Horde::applicationUrl('index.php'), 'moh');
        }

        if (Auth::isAdmin("shout:system", PERMS_SHOW|PERMS_READ)) {
            $tabs->addTab(_("System Settings"),
                Horde::applicationUrl('index.php'), 'system');
        }

        if (Auth::isAdmin("shout:superadmin", PERMS_SHOW|PERMS_READ)) {
            $tabs->addTab(_("Security"),
                Horde::applicationUrl('index.php'), 'security');
        }

        return $tabs;
    }

    // {{{
    /**
     * Checks for the given permissions for the current user on the given
     * permission.  Optionally check for higher-level permissions and ultimately
     * test for superadmin priveleges.
     *
     * @param string $permname Name of the permission to check
     *
     * @param optional int $permmask Bitfield of permissions to check for
     *
     * @param options int $numparents Check for the same permissions this
     *                                many levels up the tree
     *
     * @return boolean the effective permissions for the user.
     */
    function checkRights($permname, $permmask = null, $numparents = 0)
    {
        if ($permmask == null) {
            $permmask = PERMS_SHOW|PERMS_READ;
        }

        # Default deny all permissions
        $user = 0;
        $superadmin = 0;

        $superadmin = Auth::isAdmin("shout:superadmin", $permmask);

        while ($numparents >= 0) {
            $tmpuser = Auth::isAdmin($permname, $permmask);
            $user = $user | $tmpuser;
            if ($numparents > 0) {
                $pos = strrpos($permname, ':');
                if ($pos) {
                    $permname = substr($permname, 0, $pos);
                }
            }
            $numparents--;
        }

        $test = $superadmin | $user;
        return $test;
    }
    // }}}

    function getContextTypes()
    {
        return array(SHOUT_CONTEXT_CUSTOMERS => _("Customers"),
                     SHOUT_CONTEXT_EXTENSIONS => _("Dialplan"),
                     SHOUT_CONTEXT_MOH => _("Music On Hold"),
                     SHOUT_CONTEXT_CONFERENCE => _("Conference Calls"));
    }

    /**
     * Given an integer value of permissions returns an array
     * representation of the integer.
     *
     * @param integer $int  The integer representation of permissions.
     */
    function integerToArray($int)
    {
        static $array = array();
        if (isset($array[$int])) {
            return $array[$int];
        }

        $array[$int] = array();

        /* Get the available perms array. */
        $types = Shout::getContextTypes();

        /* Loop through each perm and check if its value is included in the
         * integer representation. */
        foreach ($types as $val => $label) {
            if ($int & $val) {
                $array[$int][$val] = true;
            }
        }

        return $array[$int];
    }

}
// }}}