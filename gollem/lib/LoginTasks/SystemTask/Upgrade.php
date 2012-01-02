<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'gollem';

    /**
     */
    protected $_versions = array(
        '2.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        global $prefs;

        switch ($version) {
        case '2.0':
            /* Upgrade to the new preferences format. */
            if (!$prefs->isDefault('columns')) {
                $cols = $prefs->getValue('columns');
                if (!is_array(json_decode($cols))) {
                    $prefs->setValue('columns', json_encode(explode("\t", $cols)));
                }
            }
            break;
        }
    }

}
