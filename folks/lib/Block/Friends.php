<?php
/**
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Block_Friends extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Friends");
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
    }

    /**
     */
    protected function _params()
    {
        return array(
            'display' => array(
                'name' => _("Show friends that are"),
                'type' => 'enum',
                'default' => 'online',
                'values' => array(
                    'all' => _("All"),
                    'online' => _("Online"),
                    'offline' => _("Offline")
                )
            )
        );
    }

    /**
     */
    protected function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $friends_driver = Folks_Friends::singleton();
        $friends = $friends_driver->getFriends();
        if ($friends instanceof PEAR_Error) {
            return $friends;
        }

        $users = $GLOBALS['folks_driver']->getOnlineUsers();
        if ($users instanceof PEAR_Error) {
            return $users;
        }

        if (empty($this->_params['display']) || $this->_params['display'] == 'all') {
            $list = $friends;
        } else {
            $list = array();
            foreach ($friends as $friend) {
                if ($this->_params['display'] == 'online') {
                    if (array_key_exists($friend, $users)) {
                        $list[] = $friend;
                    }
                } elseif ($this->_params['display'] == 'offline') {
                    if (!array_key_exists($friend, $users)) {
                        $list[] = $friend;
                    }
                }
            }
        }

        // Prepare actions
        $actions = array(
            array('url' => Horde::url('user.php'),
                'id' => 'user',
                'name' => _("View profile")));
        if ($GLOBALS['registry']->hasInterface('letter')) {
            $actions[] = array('url' => $GLOBALS['registry']->callByPackage('letter', 'compose', ''),
                                'id' => 'user_to',
                                'name' => _("Send message"));
        }

        Horde::addScriptFile('stripe.js', 'horde');

        ob_start();
        require FOLKS_TEMPLATES . '/block/users.php';
        return ob_get_clean();
    }
}
