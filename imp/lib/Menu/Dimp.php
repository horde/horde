<?php
/**
 * This class builds the menu entries for use within IMP's dynamic view
 * (dimp).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
     * @param string $type  Either 'sidebar' or 'tabs'.
     */
    public function render($type)
    {
        if (!$this->_renderCalled) {
            parent::render();

            foreach ($this->_menu as $k => $v) {
                if ($v == 'separator') {
                    unset($this->_menu[$k]);
                }
            }

            $this->_renderCalled = true;
        }

        $out = '';

        foreach ($this->_menu as $k => $m) {
            switch ($type) {
            case 'sidebar':
                $out .= '<li class="custom">' .
                    Horde::img($m['icon'], Horde::stripAccessKey($m['text']), '', $m['icon_path']) .
                    '<a id="sidebarapp_' . htmlspecialchars($k) . '">' . htmlspecialchars($m['text']) .
                    '</a></li>';
                break;

            case 'tabs':
                $out .= '<li>' .
                    '<a class="applicationtab" id="apptab_' . htmlspecialchars($k) . '">' .
                    Horde::img($m['icon'], Horde::stripAccessKey($m['text']), '', $m['icon_path']) .
                    htmlspecialchars($m['text']) .
                    '</a></li>';
                break;
            }
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
            $out[$k] = strval($url->setRaw(true)->add('ajaxui', 1));
        }

        if (!empty($out)) {
            Horde::addInlineJsVars(array(
                'DIMP.conf.menu_urls' => $out
            ));
        }
    }

}
