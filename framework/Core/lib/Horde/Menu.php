<?php
/**
 * The Horde_Menu:: class provides standardized methods for creating menus in
 * Horde applications.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Menu
{
    /* TODO */
    const MASK_NONE = 0;
    const MASK_HELP = 1;
    const MASK_LOGIN = 2;
    const MASK_PREFS = 4;
    const MASK_PROBLEM = 8;
    const MASK_ALL = 15;

    /* TODO */
    const POS_LAST = 999;

    /**
     * Menu array.
     *
     * @var array
     */
    protected $_menu = array();

    /**
     * Mask defining what general Horde links are shown in this Menu.
     *
     * @var integer
     */
    protected $_mask;

    /**
     * Constructor
     */
    public function __construct($mask = self::MASK_ALL)
    {
        /* Menuitem mask. */
        $this->_mask = $mask;

        /* Location of the menufile. */
        $this->_menufile = $GLOBALS['registry']->get('fileroot') . '/config/menu.php';
    }

    /**
     * Add an item to the menu array.
     *
     * @param string $url        String containing the value for the hyperlink.
     * @param string $text       String containing the label for this menu
     *                           item.
     * @param string $icon       String containing the filename of the image
     *                           icon to display for this menu item.
     * @param string $icon_path  If the icon lives in a non-default directory,
     *                           where is it?
     * @param string $target     If the link needs to open in another frame or
     *                           window, what is its name?
     * @param string $onclick    Onclick javascript, if desired.
     * @param string $class      CSS class for the menu item.
     *
     * @return integer  The id (NOT guaranteed to be an array index) of the
     *                  item just added to the menu.
     */
    public function add($url, $text, $icon = '', $icon_path = null,
                        $target = '', $onclick = null, $class = null)
    {
        $pos = count($this->_menu);
        if (!$pos || ($pos - 1 != max(array_keys($this->_menu)))) {
            $pos = count($this->_menu);
        }

        $this->_menu[$pos] =
            array(
                'url' => $url,
                'text' => $text,
                'icon' => $icon,
                'icon_path' => $icon_path,
                'target' => $target,
                'onclick' => $onclick,
                'class' => $class
            );

        return $pos;
    }

    /**
     * Add an item to the menu array.
     *
     * @param array $item  The item to add.  Valid keys:
     * <pre>
     * 'class' - (string) CSS classname.
     * 'icon' - (string) Filename of the image icon.
     * 'icon_path' - (string) Non-default directory path for icon.
     * 'onclick' - (string) Onclick javascript.
     * 'target' - (string) HREF target parameter.
     * 'text' - (string) Label.
     * 'url' - (string) Hyperlink.
     * </pre>
     *
     * @return integer  The id (NOT guaranteed to be an array index) of the
     *                  item just added to the menu.
     */
    public function addArray($item)
    {
        $pos = count($this->_menu);
        if (!$pos || ($pos - 1 != max(array_keys($this->_menu)))) {
            $pos = count($this->_menu);
        }

        $this->_menu[$pos] = array_merge(array(
            'class' => '',
            'icon' => '',
            'icon_path' => null,
            'onclick' => null,
            'target' => '',
            'text' => '',
            'url' => ''
        ), $item);

        return $pos;
    }

    /**
     * TODO
     */
    public function setPosition($id, $pos)
    {
        if (!isset($this->_menu[$id]) || isset($this->_menu[$pos])) {
            return false;
        }

        $item = $this->_menu[$id];
        unset($this->_menu[$id]);
        $this->_menu[$pos] = $item;

        return true;
    }

    /**
     * Return the unordered list representing the list of menu items. Styling
     * is done through CSS.
     *
     * @return string  An unordered list of menu elements that can be entirely
     *                 styled with CSS.
     */
    public function render()
    {
        global $conf, $registry, $prefs;

        $app = $registry->getApp();

        if ($this->_mask !== self::MASK_NONE) {
            /* Add any custom menu items. */
            $this->addSiteLinks();

            /* Add any app menu items. */
            $this->addAppLinks();
        }

        /* Add preferences link. */
        if (($this->_mask & self::MASK_PREFS) &&
            $this->showService('prefs') &&
            ($url = Horde::getServiceLink('prefs', $app))) {
            $this->add($url, _("_Preferences"), 'prefs.png');
        }

        /* Add problem link. */
        if (($this->_mask & self::MASK_PROBLEM) &&
            $this->showService('problem') &&
            ($problem_link = Horde::getServiceLink('problem', $app))) {
            $this->add($problem_link, _("Problem"), 'problem.png');
        }

        /* Add help link. */
        if (($this->_mask & self::MASK_HELP) &&
            $this->showService('help') &&
            ($help_link = Horde::getServiceLink('help', $app))) {
            Horde::
            $this->add($help_link, _("Help"), 'help_index.png', null, 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;', 'helplink');
        }

        /* Login/Logout. */
        if ($this->_mask & self::MASK_LOGIN) {
            /* If the sidebar isn't always shown, but is sometimes
             * shown, then logout links should be to the parent
             * frame. */
            $auth_target = null;
            if ($conf['menu']['always'] || $prefs->getValue('show_sidebar')) {
                $auth_target = '_parent';
            }

            if ($registry->getAuth()) {
                if ((!$prefs->getValue('show_sidebar') || $this->showService('logout')) &&
                    ($logout_link = Horde::getServiceLink('logout', $app))) {
                    $this->add($logout_link, _("_Log out"), 'logout.png', null, $auth_target, null, '__noselection');
                }
            } else {
                if ($this->showService('login') &&
                    ($login_link = Horde::getServiceLink('login', $app))) {
                    $this->add($login_link->add('url', Horde::selfUrl(true, true, true)), _("_Log in"), 'login.png', null, $auth_target, null, '__noselection');
                }
            }
        }

        /* No need to return an empty list if there are no menu
         * items. */
        if (!count($this->_menu)) {
            return '';
        }

        /* Sort to match explicitly set positions. */
        ksort($this->_menu);
        if (!empty($registry->nlsconfig['rtl'][$GLOBALS['language']])) {
            $this->_menu = array_reverse($this->_menu);
        }

        $menu_view = $prefs->getValue('menu_view');
        $output = '<ul>';
        foreach ($this->_menu as $m) {
            /* Check for separators. */
            if ($m == 'separator') {
                $output .= "\n<li class=\"separator\">&nbsp;</li>";
                continue;
            }

            /* Item class and selected indication. */
            if (!isset($m['class'])) {
                /* Try to match the item's path against the current
                 * script filename as well as other possible URLs to
                 * this script. */
                if (self::isSelected($m['url'])) {
                    $m['class'] = 'current';
                }
            } elseif ($m['class'] === '__noselection') {
                unset($m['class']);
            }

            /* Icon. */
            $icon = '';
            if ($menu_view == 'icon' || $menu_view == 'both') {
                if (empty($m['icon_path'])) {
                    $m['icon_path'] = null;
                }
                $icon = Horde::img($m['icon'], Horde::stripAccessKey($m['text']), '', $m['icon_path']) . '<br />';
            }

            /* Link. */
            $accesskey = Horde::getAccessKey($m['text']);
            $link = $m['url']->setRaw(false)->link(
                array('title' => $menu_view == 'icon' ? Horde::stripAccessKey($m['text']) : '',
                      'class' => isset($m['class']) ? $m['class'] : '',
                      'target' => $m['target'],
                      'onclick' => $m['onclick'],
                      'accesskey' => $accesskey));

            $output .= sprintf("\n<li>%s%s%s</a></li>",
                               $link, $icon, ($menu_view != 'icon') ? Horde::highlightAccessKey($m['text'], $accesskey) : '');
        }

        return $output . '</ul>';
    }

    /**
     * Any links to other Horde applications defined in an application's config
     * file by the $conf['menu']['apps'] array are added to the menu array.
     */
    public function addAppLinks()
    {
        global $conf, $registry;

        if (isset($conf['menu']['apps']) && is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                if ($registry->get('status', $app) != 'inactive' && $registry->hasPermission($app, Horde_Perms::SHOW)) {
                    try {
                        $this->add(Horde::url($registry->getInitialPage($app)), $registry->get('name', $app), $registry->get('icon', $app), '');
                    } catch (Horde_Exception $e) {}
                }
            }
        }
    }

    /**
     * Add any other links found in $this->_menufile to be included in the
     * menu.
     */
    public function addSiteLinks()
    {
        foreach ($this->getSiteLinks() as $menuitem) {
            $this->addArray($menuitem);
        }
    }

    /**
     * Get the list of site links to add to the menu.
     *
     * @return array  A list of menu items to add.
     */
    public function getSiteLinks()
    {
        if (is_readable($this->_menufile)) {
            include $this->_menufile;
            if (isset($_menu) && is_array($_menu)) {
                return $_menu;
            }
        }

        return array();
    }

    /**
     * Checks to see if the current url matches the given url.
     *
     * @return boolean  Whether the given URL is the current location.
     */
    static public function isSelected($url)
    {
        $server_url = parse_url($_SERVER['PHP_SELF']);
        $check_url = parse_url($url);

        /* Try to match the item's path against the current script
           filename as well as other possible URLs to this script. */
        if (isset($check_url['path']) &&
            (($check_url['path'] == $server_url['path']) ||
             ($check_url['path'] . 'index.php' == $server_url['path']) ||
             ($check_url['path'] . '/index.php' == $server_url['path']))) {
            return true;
        }

        return false;
    }

    /**
     * TODO
     *
     * @param string $type       The type of link.
     * <pre>
     * The following must be defined in Horde's menu config, or else they
     * won't be displayed in the menu:
     * 'help', 'problem', 'logout', 'login', 'prefs'
     * </pre>
     *
     * @return boolean  True if the link is to be shown.
     */
    static public function showService($type)
    {
        global $conf;

        if (!in_array($type, array('help', 'problem', 'logout', 'login', 'prefs'))) {
            return true;
        }

        if (empty($conf['menu']['links'][$type])) {
            return false;
        }

        switch ($conf['menu']['links'][$type]) {
        case 'all':
            return true;

        case 'authenticated':
            return (bool)$GLOBALS['registry']->getAuth();

        default:
        case 'never':
            return false;
        }
    }

}
