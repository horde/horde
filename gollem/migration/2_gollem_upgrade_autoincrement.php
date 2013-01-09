<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class GollemUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('gollem_shares', 'share_id', 'autoincrementKey');
        try {
            $this->dropTable('gollem_shares_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('gollem_shares', 'share_id', 'integer', array('null' => false));
    }

}