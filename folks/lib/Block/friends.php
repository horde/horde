<?php

$block_name = _("Friends");

/**
 * $Id: friends.php 1019 2008-10-31 08:18:10Z duck $
 *
 * @package Folks
 * @author Duck <duck@obala.net>
 */
class Horde_Block_Folks_friends extends Horde_Block {

    var $_app = 'folks';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Friends");
    }

    /**
     * The parameters of block
     *
     * @return array   The parameters
     */
    function _params()
    {
        $params = array('display' => array('name' => _("Show friends that are"),
                                            'type' => 'enum',
                                            'default' => 'online',
                                            'values' => array('all' => _("All"),
                                                            'online' => _("Online"),
                                                            'offline' => _("Offline"))));
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $friends = $GLOBALS['folks_driver']->getFriends(Auth::getAuth());
        if ($friends instanceof PEAR_Error) {
            return $friends;
        }

        $users = $GLOBALS['folks_driver']->getOnlineUsers();
        if ($users instanceof PEAR_Error) {
            return $users;
        }

        $online = '';
        $offline = '';

        foreach ($friends as $friend) {
            if (array_key_exists($friend, $users)) {
                $online .= '<a href="' . Folks::getUrlFor('user', $friend) . '">' . $friend . '</a> ';
            } else {
                $offline .= '<a href="' . Folks::getUrlFor('user', $friend) . '">' . $friend . '</a> ';
            }
        }

        switch ($this->_params['display']) {

        case 'online':
            return $online;
            break;

        case 'offline':
            return $offline;
            break;

        default:
            return $online . $offline;
            break;
        }
    }
}