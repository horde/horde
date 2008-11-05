<?php

$block_name = _("Recent visitors");

/**
 * $Id: recent.php 1019 2008-10-31 08:18:10Z duck $
 *
 * @package Folks
 * @author Duck <duck@obala.net>
 */
class Horde_Block_Folks_recent extends Horde_Block {

    var $_app = 'folks';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Recent visitors");
    }

    /**
     * The parameters of block
     *
     * @return array   The parameters
     */
    function _params()
    {
        return array('limit' => array('name' => _("Limit"),
                                    'type' => 'int',
                                    'default' => 10));
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $recent = $GLOBALS['folks_driver']->getRecentVisitors($this->_params['limit']);
        if ($recent instanceof PEAR_Error) {
            return $recent;
        }

        $html = '';

        foreach ($recent as $user) {
            $html .= '<a href="' . Folks::getUrlFor('user', $user) . '">' . $user . '</a>';
        }

        return $html;
    }
}