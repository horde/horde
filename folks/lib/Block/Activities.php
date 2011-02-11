<?php
/**
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Block_Activities extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Friends activities");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'limit' => array(
                'name' => _("Number of activities to display"),
                'type' => 'int',
                'default' => 10
            )
        );
    }

    /**
     */
    protected function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

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
