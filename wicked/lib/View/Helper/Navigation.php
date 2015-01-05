<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @category Horde
 * @package  Wicked
 */

/**
 * View helper to display a page's breadcrumb navigation.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @category Horde
 * @package  Wicked
 * @since    2.1.0
 */
class Wicked_View_Helper_Navigation extends Horde_View_Helper_Base
{
    /**
     * Cached list of sub pages.
     *
     * @var array
     */
    protected $_subPages;

    /**
     * Returns a page's breadcrumb navigation.
     *
     * @param string $name  A page name with slashes for directory separators.
     *
     * @return string  A breadcrumb navigation.
     */
    public function breadcrumb($name)
    {
        global $wicked;

        $parts = $dirs = array();
        foreach (explode('/', $name) as $part) {
            $dirs[] = $part;
            $dir = implode('/', $dirs);
            $attributes = array();
            if (!$wicked->pageExists($dir)) {
                $attributes['class'] = 'newpage';
            }
            $parts[] = Wicked::url($dir)->link($attributes)
                . $this->h($part) . '</a>';
        }

        return implode('/', $parts);
    }

    /**
     * Returns a page's previous/next navigation.
     *
     * @param string $name  A page name with slashes for directory separators.
     *
     * @return string  A previous/next navigation.
     */
    public function navigation($name)
    {
        global $wicked;

        if (strpos($name, '/') === false) {
            return '';
        }

        $siblings = $wicked->searchTitles(substr($name, 0, strrpos($name, '/') + 1));
        usort(
            $siblings,
            function($a, $b) {
                if ($a['page_name'] == $b['page_name']) {
                    return 0;
                }
                return $a['page_name'] > $b['page_name'] ? 1 : -1;
            }
        );

        $left = $right = null;
        $found = false;
        $slashes = substr_count($name, '/');
        foreach ($siblings as $sibling) {
            if (substr_count($sibling['page_name'], '/') != $slashes) {
                continue;
            }
            if ($found) {
                $right = $sibling;
                break;
            }
            if ($sibling['page_name'] == $name) {
                $found = true;
                continue;
            }
            $left = $sibling;
        }

        $navigation = '';
        if ($left) {
            $navigation .= Wicked::url($left['page_name'])->link()
                . Horde::img('nav/left.png') . ' '
                . $this->h($left['page_name']) . '</a>';
            if ($right) {
                $navigation .= ' | ';
            }
        }
        if ($right) {
            $navigation .= Wicked::url($right['page_name'])->link()
                . $this->h($right['page_name']) . ' '
                . Horde::img('nav/right.png') . '</a>';
        }

        return $navigation;
    }

    /**
     * Returns whether a page has sub pages.
     *
     * @param string $name  A page name with slashes for directory separators.
     *
     * @return boolean  Whether the page has subpages.
     */
    public function hasSubPages($name)
    {
        return (boolean)count($this->_getSubPages($name));
    }

    /**
     * Returns the list of a page's sub pages.
     *
     * @param string $name  A page name with slashes for directory separators.
     *
     * @return string  A list of sub pages.
     */
    public function subPages($name)
    {
        $slashes = substr_count($name, '/') + 1;
        $pages = $this->_getSubPages($name);
        $children = array();
        foreach ($pages as $page) {
            $name = $page['page_name'];
            if (substr_count($name, '/') != $slashes) {
                continue;
            }
            $children[$name] = '<li>' . Wicked::url($name)->link()
                . $this->h($name) . '</a></li>';
        }

        if (!$children) {
            return '';
        }

        uksort($children, 'strcoll');
        return '<ul>' . implode('', $children) . '</ul>';
    }

    /**
     * Loads the list of a page's sub pages.
     *
     * @param string $name  A page name with slashes for directory separators.
     */
    protected function _getSubPages($name)
    {
        global $wicked;

        if (!isset($this->_subPages)) {
            $this->_subPages = $wicked->searchTitles($name . '/');
        }

        return $this->_subPages;
    }
}
