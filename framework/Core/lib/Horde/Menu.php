<?php
/**
 * The Horde_Menu:: class provides standardized methods for creating menus in
 * Horde applications.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Menu
{
    /**
     * Don't show any menu items.
     */
    const MASK_NONE = 0;

    /**
     * Show help menu item.
     */
    const MASK_HELP = 1;

    /**
     * Show preferences menu item.
     */
    const MASK_PREFS = 4;

    /**
     * Show problem reporting menu item.
     */
    const MASK_PROBLEM = 8;

    /**
     * Only show application specific menu items.
     */
    const MASK_BASE = 16;

    /**
     * Show all menu items.
     */
    const MASK_ALL = 31;

    /* TODO */
    const POS_LAST = 999;

    /**
     * Mask defining what menu items to show.
     *
     * @var integer
     */
    protected $_mask;

    /**
     * Menu array.
     *
     * @var array
     */
    protected $_menu = array();

    /**
     * Constructor.
     *
     * @param integer $mask  Display mask.
     */
    public function __construct($mask = self::MASK_ALL)
    {
        $this->setMask($mask);
    }

    /**
     * Sets the display mask.
     *
     * @param integer $mask  Display mask.
     */
    public function setMask($mask)
    {
        $this->_mask = $mask;
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

        $this->_menu[$pos] = array(
            'url' => ($url instanceof Horde_Url) ? $url : new Horde_Url($url),
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

        if (!isset($item['url'])) {
            $item['url'] = new Horde_Url();
        } elseif (!($item['url'] instanceof Horde_Url)) {
            $item['url'] = new Horde_Url($item['url']);
        }

        $this->_menu[$pos] = array_merge(array(
            'class' => '',
            'icon' => '',
            'icon_path' => null,
            'onclick' => null,
            'target' => '',
            'text' => ''
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
     * Return the rendered representation of the menu items.
     *
     * @return Horde_View_Sidebar  Sidebar view of menu elements.
     */
    public function render()
    {
        global $conf, $registry, $prefs;

        $app = $registry->getApp();

        if ($this->_mask !== self::MASK_NONE) {
            /* Add any custom menu items. */
            $this->addSiteLinks();
        }

        /* No need to return an empty list if there are no menu
         * items. */
        if (!count($this->_menu)) {
            return '';
        }

        /* Sort to match explicitly set positions. */
        ksort($this->_menu);
        if ($registry->nlsconfig->curr_rtl) {
            $this->_menu = array_reverse($this->_menu);
        }

        return $this->_render();
    }

    /**
     * Converts the menu to a sidebar view.
     *
     * @return Horde_View_Sidebar  Sidebar view of menu elements.
     */
    protected function _render()
    {
        $sidebar = $GLOBALS['injector']->getInstance('Horde_View_Sidebar');

        $container = 0;
        foreach ($this->_menu as $m) {
            /* Check for separators. */
            if ($m == 'separator') {
                $container++;
                continue;
            }

            $row = array(
                'cssClass' => $m['icon'],
                'url' => $m['url'],
                'label' => $m['text'],
                'target' => $m['target'],
                'onclick' => $m['onclick'],
            );

            /* Item class and selected indication. */
            if (!isset($m['class'])) {
                /* Try to match the item's path against the current
                 * script filename as well as other possible URLs to
                 * this script. */
                if ($this->isSelected($m['url'])) {
                    $row['selected'] = true;
                }
            } elseif ($m['class'] === '__noselection') {
                unset($m['class']);
            } elseif ($m['class'] === 'current') {
                $row['selected'] = true;
            } else {
                $row['class'] = $m['class'];
            }

            $sidebar->addRow($row);
        }

        return $sidebar;
    }

    /**
     * Add links found in the application's menu configuration.
     */
    public function addSiteLinks()
    {
        foreach ($this->getSiteLinks() as $item) {
            $this->addArray($item);
        }
    }

    /**
     * Get the list of site links to add to the menu.
     *
     * @return array  A list of menu items to add.
     */
    public function getSiteLinks()
    {
        $menufile = $GLOBALS['registry']->get('fileroot') . '/config/menu.php';

        if (is_readable($menufile)) {
            include $menufile;
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
        return isset($check_url['path']) &&
            (($check_url['path'] == $server_url['path']) ||
             ($check_url['path'] . 'index.php' == $server_url['path']) ||
             ($check_url['path'] . '/index.php' == $server_url['path']));
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
