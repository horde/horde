<?php
/**
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Block_New extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("New users");
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
            'limit' => array(
                'name' => _("Limit"),
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

        $new = $GLOBALS['folks_driver']->getNewUsers($this->_params['limit']);
        if ($new instanceof PEAR_Error) {
            return $new;
        }

        $list = array();
        foreach ($new as $u) {
            $list[] = $u['user_uid'];
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
