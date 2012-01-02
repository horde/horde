<?php
/**
 * Change columns to autoincrement.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Whups
 */
class WhupsAutoIncrementShares extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('whups_shares', 'share_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_shares_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('whups_shares', 'share_id', 'integer', array('null' => false));
    }
}
