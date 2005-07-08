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
        global $conf, $page;

        require_once 'Horde/Menu.php';

        $menu = &new Menu(HORDE_MENU_MASK_ALL);

        if (@count($conf['menu']['pages'])) {
            foreach ($conf['menu']['pages'] as $pagename) {
                /* Determine who we should say referred us. */
                $curpage = isset($page) ? $page->pageName() : null;
                $referrer = Util::getFormData('referrer', $curpage);

                /* Determine if we should depress the button. We have to do
                 * this on our own because all the buttons go to the same .php
                 * file, just with different args. */
                if (!strstr($_SERVER['PHP_SELF'], 'prefs.php') &&
                    $curpage === _($pagename)) {
                    $cellclass = 'current';
                } else {
                    $cellclass = '__noselection';
                }

                /* Construct the URL. */
                $url = Horde::applicationUrl('display.php');
                $url = Util::addParameter($url, array('page' => $pagename,
                                                      'referrer' => $referrer));

                $menu->add($url, _($pagename), $pagename . '.png', null, null,
null, $cellclass);
            }
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

        if (Shout::checkRights("$permprefix:users") &&
            $shout->checkContextType($context, "users")) {
            $tabs->addTab(_("Users"),
                    Horde::applicationUrl("index.php?context=$context"),
                    'users');
        }

        if (Shout::checkRights("$permprefix:dialplan") &&
            $shout->checkContextType($context, "dialplan")) {
            $tabs->addTab(_("Dial Plan"),
                Horde::applicationUrl('index.php'), 'dialplan');
        }

        if (Shout::checkRights("$permprefix:conference") &&
            $shout->checkContextType($context, "conference")) {
            $tabs->addTab(_("Conference Rooms"),
                Horde::applicationUrl('index.php'), 'conference');
        }

       if (Shout::checkRights("$permprefix:moh") &&
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

    function checkRights($permname, $permmask = null)
    {
        if ($permmask == null) {
            $permmask = PERMS_SHOW|PERMS_READ;
        }

        $superadmin = Auth::isAdmin("shout:superadmin", $permmask);
        $user = Auth::isAdmin($permname, $permmask);
        $test = $superadmin | $user;
        if ($test) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

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