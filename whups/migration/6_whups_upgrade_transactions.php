<?php
/**
 * Normalize Whups Transactions
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Whups
 */
class WhupsUpgradeTransactions extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->createTable('whups_transactions', array('autoincrementKey' => false));
        $t->column('transaction_id', 'integer', array('null' => false));
        $t->column('transaction_timestamp', 'integer', array('null' => false));
        $t->column('transaction_user_id', 'string', array('limit' => 255, 'null' => false));
        $t->end();

        $this->_normalize();

        $this->changeColumn('whups_transactions', 'transaction_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_logs_seq');
        } catch (Horde_Db_Exception $e) {}
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('whups_transactions');
        // @TODO

    }

    /**
     * Normalize the tranasaction data.
     */
    protected function _normalize()
    {
        $this->beginDbTransaction();
        $sql = 'SELECT DISTINCT transaction_id, log_timestamp, user_id from whups_logs ORDER BY transaction_id;';
        $rows = $this->selectAll($sql);
        $insert = 'INSERT INTO whups_transactions (transaction_id, '
        . 'transaction_timestamp, transaction_user_id) VALUES(?, ?, ?)';

        try {
            foreach ($rows as $row) {
                $this->insert($insert,
                    array(
                        $row['transaction_id'],
                        $row['log_timestamp'],
                        $row['user_id']));
            }
        } catch (Horde_Db_Exception $e) {
            $this->rollbackDbTransaction();
            Horde::fatal($e->getMessage());
        }

        //$this->removeColumn('whups_logs', 'user_id');
        //$this->removeColumn('whups_logs', 'transaction_timestamp');

        $this->commitDbTransaction();
    }

    /**
     * @TODO:
     */
    protected function _denormalize()
    {

    }
}
