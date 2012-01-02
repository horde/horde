<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'ansel';

    /**
     */
    protected $_versions = array(
        '2.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '2.0':
            /* Upgrade myansel_layout preference. */
            $bu = new Horde_Core_Block_Upgrade();
            $bu->upgrade('myansel_layout');
            break;
        }
    }

}
