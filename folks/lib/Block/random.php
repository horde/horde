<?php

$block_name = _("Random users");

/**
 * $Id: random.php 1019 2008-10-31 08:18:10Z duck $
 *
 * @package Folks
 * @author Duck <duck@obala.net>
 */
class Horde_Block_Folks_random extends Horde_Block {

    var $_app = 'folks';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Random users");
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
                                    'default' => 10),
                    'online' => array('name' => _("User is currently online?"),
                                        'type' => 'enum',
                                        'default' => 'online',
                                        'values' => array('online' => _("Online"),
                                                            'all' => _("Does not metter"))));
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $list = $GLOBALS['folks_driver']->getRandomUsers($this->_params['limit'],
                                                        $this->_params['online'] == 'online');
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
