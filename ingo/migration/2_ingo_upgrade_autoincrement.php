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
        $this->changeColumn('ingo_rules', 'rule_id', 'integer', array('null' => false, 'autoincrement' => true, 'default' => null));
        $this->changeColumn('ingo_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => true));
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
