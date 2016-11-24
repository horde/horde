<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Vilma
 */
class VilmaUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('vilma_domains', 'domain_id', 'autoincrementKey');
        $this->changeColumn('vilma_users', 'user_id', 'autoincrementKey');
        $this->changeColumn('vilma_virtuals', 'virtual_id', 'autoincrementKey');
        try {
            $this->dropTable('vilma_domains_seq');
        } catch (Horde_Db_Exception $e) {
        }
        try {
            $this->dropTable('vilma_users_seq');
        } catch (Horde_Db_Exception $e) {
        }
        try {
            $this->dropTable('vilma_virtuals_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('vilma_domains', 'domain_id', 'integer', array('null' => false));
        $this->changeColumn('vilma_users', 'user_id', 'integer', array('null' => false));
        $this->changeColumn('vilma_virtuals', 'virtual_id', 'integer', array('null' => false));
    }

}