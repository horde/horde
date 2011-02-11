<?php
/**
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Block_Recent extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Recent visitors");
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

        $list = $GLOBALS['folks_driver']->getRecentVisitors($this->_params['limit']);
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
