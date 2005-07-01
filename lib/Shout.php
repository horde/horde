<?php

@define(SHOUT_ASTERISK_BRANCH, "ou=Asterisk");
@define(SHOUT_USERS_BRANCH, "ou=Customers");
@define(SHOUT_USER_OBJECTCLASS, "asteriskUser");

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
    function &getTabs(&$vars)
    {
        global $shout;
        if (!Auth::isAdmin("shout", PERMS_SHOW|PERMS_READ)) {
            return false;
        }
        $tabs = &new Horde_UI_Tabs('section', $vars);
        if (count($shout->contexts) > 1 ||
            Auth::isAdmin("shout:superadmin", PERMS_SHOW|PERMS_READ)) {
            $tabs->addTab(_("Contexts"),
                    Horde::applicationUrl('index.php'), 'contexts');
        }
        $tabs->addTab(_("Users"),
            Horde::applicationUrl('index.php'), 'users');
        $tabs->addTab(_("Music on Hold"),
            Horde::applicationUrl('index.php'), 'moh');
        // $tabs->addTab(_("Watch"), Horde::applicationUrl('ticket/watch.php'));
        if (Auth::isAdmin('shout:superadmin', PERMS_READ|PERMS_SHOW)) {
            $tabs->addTab(_("Global Settings"),
                Horde::applicationUrl('index.php'), 'global');
        }

        return $tabs;
    }

}
// }}}