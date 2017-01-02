<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Fixes the type of the parents column.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class IngoFixRuleOrder extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $sql = 'SELECT rule_id, rule_owner, rule_order FROM ingo_rules ORDER BY rule_owner, rule_order';
        $update = 'UPDATE ingo_rules SET rule_order = ? WHERE rule_owner = ? AND rule_id = ?';

        $results = $this->_connection->selectAll($sql);
        $owner = '';
        foreach ($results as $row) {
            if ($owner != $row['rule_owner']) {
                $owner = $row['rule_owner'];
                $order = 0;
            }
            if ($row['rule_order'] != $order++) {
                $this->_connection->update($update, array($order - 1, $owner, $row['rule_id']));
            }
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
    }

}
