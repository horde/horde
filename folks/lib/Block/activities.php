<?php

$block_name = _("Friends activities");

/**
 * $Id: friends.php 1019 2008-10-31 08:18:10Z duck $
 *
 * @package Folks
 * @author Duck <duck@obala.net>
 */
class Horde_Block_Folks_activities extends Horde_Block {

    var $_app = 'folks';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Friends activities");
    }

    /**
     * The parameters of block
     *
     * @return array   The parameters
     */
    function _params()
    {
        return array('limit' => array('name' => _("Number of activities to display"),
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
        require_once FOLKS_BASE . '/lib/Friends.php';

        $friends_driver = Folks_Friends::singleton();
        $friend_list = $friends_driver->getFriends();
        if ($friend_list instanceof PEAR_Error) {
            return $friend_list;
        }

        // Get friends activities
        $list = array();
        foreach ($friend_list as $user) {
            $activities = $GLOBALS['folks_driver']->getActivity($user);
            if ($activities instanceof PEAR_Error) {
                return $activities;
            }
            foreach ($activities as $activity) {
                $list[$activity['activity_date']] = $activity;
            }
        }
        krsort($list);
        $list = array_slice($list, 0, $this->_params['limit']);

        Horde::addScriptFile('stripe.js', 'horde');

        ob_start();
        require FOLKS_TEMPLATES . '/block/activities.php';
        return ob_get_clean();
    }
}
