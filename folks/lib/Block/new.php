<?php

$block_name = _("New users");

/**
 * $Id: new.php 1019 2008-10-31 08:18:10Z duck $
 *
 * @package Folks
 * @author Duck <duck@obala.net>
 */
class Horde_Block_Folks_new extends Horde_Block {

    var $_app = 'folks';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("New users");
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

        $new = $GLOBALS['folks_driver']->getNewUsers($this->_params['limit']);
        if ($new instanceof PEAR_Error) {
            return $new;
        }

        $html = '';

        foreach ($new as $user) {
            $html .= '<a href="' . Folks::getUrlFor('user', $user['user_uid']) . '">' . $user['user_uid'] . '</a> ';
        }

        return $html;
    }
}