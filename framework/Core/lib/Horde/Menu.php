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
     * Menu array.
     *
     * @var array
     */
    protected $_menu = array();

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
     */
    public function add($url, $text, $icon = '', $icon_path = null,
                        $target = '', $onclick = null, $class = null)
    {
        $this->_menu[] = array(
            'url' => ($url instanceof Horde_Url) ? $url : new Horde_Url($url),
            'text' => $text,
            'icon' => $icon,
            'icon_path' => $icon_path,
            'target' => $target,
            'onclick' => $onclick,
            'class' => $class
        );
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
        if (!isset($item['url'])) {
            $item['url'] = new Horde_Url();
        } elseif (!($item['url'] instanceof Horde_Url)) {
            $item['url'] = new Horde_Url($item['url']);
        }

        $this->_menu[] = array_merge(array(
            'class' => '',
            'icon' => '',
            'icon_path' => null,
            'onclick' => null,
            'target' => '',
            'text' => ''
        ), $item);
    }

    /**
     * Return the rendered representation of the menu items.
     *
     * @return Horde_View_Sidebar  Sidebar view of menu elements.
     */
    public function render()
    {
        /* Add any custom menu items. */
        $this->addSiteLinks();

        /* Sort to match explicitly set positions. */
        ksort($this->_menu);
        if ($GLOBALS['registry']->nlsconfig->curr_rtl) {
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

        foreach ($this->_menu as $m) {
            /* Check for separators. */
            if ($m == 'separator') {
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

}
