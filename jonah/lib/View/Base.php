<?php
/*
 * Jonah_View:: class wraps display or the various channel and story views.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
abstract class Jonah_View_Base
{
    /**
     * Values to include in the view's scope
     *
     * @var array
     */
    protected $_params;

    /**
     * Const'r
     *
     * @param array $params  View parameters
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    protected function _exit($message)
    {
        extract($this->_params, EXTR_REFS);
        $notification->push(sprintf(_("Error fetching story: %s"), $message), 'horde.error');
        require $registry->get('templates', 'horde') . '/common-header.inc';
        $notification->notify(array('listeners' => 'status'));
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }

    /**
     * Render this view.
     *
     */
    abstract public function run();

}

