<?php
/**
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Block_Random extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Random users");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'limit' => array(
                'name' => _("Limit"),
                'type' => 'int',
                'default' => 10
            ),
            'online' => array(
                'name' => _("User is currently online?"),
                'type' => 'enum',
                'default' => 'online',
                'values' => array(
                    'online' => _("Online"),
                    'all' => _("Does not metter")
                )
            )
        );
    }

    /**
     */
    protected function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $list = $GLOBALS['folks_driver']->getRandomUsers(
            $this->_params['limit'],
            $this->_params['online'] == 'online'
        );
        if ($list instanceof PEAR_Error) {
            return $list;
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
