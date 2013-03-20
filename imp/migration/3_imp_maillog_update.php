<?php
/**
 * Change IMP's maillog entries to use ':' delimiters.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class ImpMaillogUpdate extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();
        if (in_array('horde_histories', $tableList)) {
            $sql = 'SELECT history_id, object_uid FROM horde_histories WHERE object_uid LIKE \'imp.%\'';
            $this->announce('Loading existing history from the maillog.');
            $rows = $this->_connection->selectAll($sql);
            $sql = 'UPDATE horde_histories SET object_uid = ? WHERE history_id = ?';
            $this->announce('Updating entries. This may take some time.');
            foreach ($rows as $row) {
                $row['object_uid'] = implode(':', explode('.', $row['object_uid'], 3));
                $this->_connection->update($sql, array($row['object_uid'], $row['history_id']));
            }
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $tableList = $this->tables();
        if (in_array('horde_histories', $tableList)) {
            $sql = 'SELECT history_id, object_uid FROM horde_histories WHERE object_uid LIKE \'imp:%\'';
            $this->announce('Loading existing history from the maillog.');
            $rows = $this->_connection->selectAll($sql);
            $sql = 'UPDATE horde_histories SET object_uid = ? WHERE history_id = ?';
            $this->announce('Updating entries. This may take some time.');
            foreach ($rows as $row) {
                $row['object_uid'] = implode('.', explode(':', $row['object_uid'], 3));
                $this->_connection->update($sql, array($row['object_uid'], $row['history_id']));
            }
        }
    }

}
