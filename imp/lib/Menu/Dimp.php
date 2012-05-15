<?php
/**
 * This class builds the menu entries for use within IMP's dynamic view
 * (dimp).
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Menu_Dimp extends Horde_Menu
{
    /**
     * Has render() been called yet?
     *
     * @var boolean
     */
    protected $_renderCalled = false;

    /**
     */
    public function render()
    {
        if (!$this->_renderCalled) {
            parent::render();

            $msort = array();
            foreach ($this->_menu as $k => $v) {
                if ($v != 'separator') {
                    $msort[$k] = $v['text'];
                }
            }

            asort($msort, SORT_LOCALE_STRING);

            $tmp = array();
            foreach (array_keys($msort) as $k) {
                $tmp[$k] = $this->_menu[$k];
            }
            $this->_menu = $tmp;

            $this->_renderCalled = true;
        }

        $out = '';

        foreach ($this->_menu as $k => $m) {
            // FIXME: solve the ajax view detection properly.
            if (empty($GLOBALS['conf']['menu']['apps_iframe']) ||
                (($m['icon'] instanceof Horde_Themes_Image) &&
                 $GLOBALS['registry']->hasView(Horde_Registry::VIEW_DYNAMIC, $m['icon']->app))) {
                $href = ' href="' . htmlspecialchars($m['url']) . '"';
            } else {
                $href = '';
            }
            $out .= '<li class="custom">' .
                Horde::img($m['icon'], Horde::stripAccessKey($m['text']), '', $m['icon_path'])
                . '<a id="sidebarapp_' . htmlspecialchars($k) . '"'
                . $href . '>' . htmlspecialchars($m['text']) . '</a></li>';
        }

        return $out;
    }

    /**
     */
    protected function _render()
    {
    }

    /**
     * Adds the necessary JS to the output string (list of keys -> URLs used
     * by DimpBase).
     */
    public function addJs()
    {
        $out = array();

        foreach ($this->_menu as $k => $v) {
            $url = new Horde_Url($v['url']);
            $out[$k] = strval($url->setRaw(true));
        }

        if (!empty($out)) {
            $GLOBALS['page_output']->addInlineJsVars(array(
                'DimpCore.conf.menu_urls' => $out
            ));
        }
    }

}
