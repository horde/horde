<?php
/**
 * The Horde_Core_Ui_Tabs:: class manages and renders a tab-like interface.
 *
 * Copyright 2001-2003 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ui_Tabs extends Horde_Core_Ui_Widget
{
    /**
     * The array of tabs.
     *
     * @var array
     */
    protected $_tabs = array();

    /**
     * Adds a tab to the interface.
     *
     * @param string $title    The text which appears on the tab.
     * @param Horde_Url $link  The target page.
     * @param mixed $params    Either a string value to set the tab variable to,
     *                         or a hash of parameters. If an array, the tab
     *                         variable can be set by the 'tabname' key.
     */
    public function addTab($title, $link, $params = array())
    {
        if (!is_array($params)) {
            $params = array('tabname' => $params);
        }

        $this->_tabs[] = array_merge(array('title' => $title,
                                           'link' => $link->copy(),
                                           'tabname' => null,
                                           'img' => null,
                                           'class' => null),
                                     $params);
    }

    /**
     * Returns the title of the tab with the specified name.
     *
     * @param string $tabname  The name of the tab.
     *
     * @return string  The tab's title.
     */
    public function getTitleFromAction($tabname)
    {
        foreach ($this->_tabs as $tab) {
            if ($tab['tabname'] == $tabname) {
                return $tab['title'];
            }
        }

        return null;
    }

    /**
     * Renders the tabs.
     *
     * @param string $active_tab  If specified, the name of the active tab. If
     *                            not, the active tab is determined
     *                            automatically.
     * @param string $class       The CSS class of the tabset.
     */
    public function render($active_tab = null, $class = 'tabset')
    {
        $html = "<div class=\"$class\"><ul>\n";

        $active = $_SERVER['PHP_SELF'] . $this->_vars->get($this->_name);

        foreach ($this->_tabs as $tab) {
            $link = $this->_addPreserved($tab['link']);
            if (!is_null($this->_name) && !is_null($tab['tabname'])) {
                $link->add($this->_name, $tab['tabname']);
            }

            $classes = array();
            if (isset($tab['class'])) {
                $classes[] = $tab['class'];
            }
            if ((!is_null($active_tab) && $active_tab == $tab['tabname']) ||
                ($active == $tab['link'] . $tab['tabname'])) {
                $classes[] = 'horde-active';
            }
            $class = $classes
                ? (' class="' . implode(' ', $classes) . '"')
                : '';

            $id = '';
            if (!empty($tab['id'])) {
                $id = ' id="' . htmlspecialchars($tab['id']) . '"';
            }

            if (!isset($tab['target'])) {
                $tab['target'] = '';
            }

            if (!isset($tab['onclick'])) {
                $tab['onclick'] = '';
            }

            $accesskey = Horde::getAccessKey($tab['title']);

            if (!empty($tab['img'])) {
                $img = Horde::img($tab['img']);
            } else {
                $img = '';
            }

            $html .= '<li' . $class . $id . '>'
                . $link->link(array('target' => $tab['target'], 'onclick' => $tab['onclick'], 'accesskey' => $accesskey))
                . $img . Horde::highlightAccessKey(str_replace(' ', '&nbsp;', $tab['title']), $accesskey)
                . "</a> </li>\n";
        }

        return $html . "</ul></div><br class=\"clear\" />\n";
    }

}
