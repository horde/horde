<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
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
        '4.2'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.2':
            $this->_upgradeColumnsPref();
            break;
        }
    }

    /**
     * Changes category columns to tag columns in the browse view.
     */
    protected function _upgradeColumnsPref()
    {
        $newColumns = array();
        foreach (Turba::getColumns() as $source => $columns) {
            if (($pos = array_search('category', $columns)) !== false) {
                array_splice($columns, $pos, 1, '__tags');
            }
            array_unshift($columns, $source);
            $newColumns[] = implode("\t", $columns);
        }
        $GLOBALS['prefs']->setValue('columns', implode("\n", $newColumns));
    }
}
