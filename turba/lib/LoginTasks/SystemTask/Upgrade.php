<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'turba';

    /**
     */
    protected $_versions = array(
        '3.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '3.0':
            $this->_upgradeAbookPrefs();
            break;
        }
    }

    /**
     * Upgrade to the new addressbook preferences.
     */
    protected function _upgradeAbookPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('addressbooks')) {
            $abooks = $prefs->getValue('addressbooks');
            if (!is_array(json_decode($abooks))) {
                $abooks = @explode("\n", $abooks);
                if (empty($abooks)) {
                    $abooks = array();
                }

                return $prefs->setValue('addressbooks', json_encode($abooks));
            }
        }
    }

}
