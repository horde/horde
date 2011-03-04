<?php
/**
 * Create Ingo base tables.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Ingo
 */
class IngoUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ingo_rules', 'rule_id', 'primaryKey');
        try {
            $this->dropTable('ingo_rules_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('ingo_shares', 'share_id', 'primaryKey');
        try {
            $this->dropTable('ingo_shares_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->changeColumn('ingo_rules', 'rule_id', 'integer', array('null' => false));
        $this->changeColumn('ingo_shares', 'share_id', 'integer', array('null' => false));
    }

}
