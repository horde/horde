<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Ingo
 */
class Ingo_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'ingo';

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
            /* Upgrade to the new preferences storage format. */
            $upgrade_prefs = array(
                'rules'
            );

            $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
            break;
        }
    }

}
