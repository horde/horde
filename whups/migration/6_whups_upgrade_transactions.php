<?php
/**
 * Normalize Whups Transactions
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
        $t->primaryKey(array('transaction_id'));
        $t->end();

        $this->_normalize();
        $this->changeColumn('whups_transactions', 'transaction_id', 'autoincrementKey');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->_denormalize();
        $this->dropTable('whups_transactions');
    }

    /**
     * Normalize the transaction data.
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
                // It's possible the same transaction id could have multiple
                // timestamps, so the above query won't filter out *all* the
                // duplicate transaction_ids, need to check to avoid
                // constraint violations.
                if ($this->selectValue('SELECT count(*) FROM whups_transactions WHERE transaction_id = ?', array($row['transaction_id'])) > 0) {
                    continue;
                }
                $this->insert($insert,
                              array($row['transaction_id'],
                                    $row['log_timestamp'],
                                    $row['user_id']),
                              null,
                              'transaction_id',
                              $row['transaction_id']);
            }
        } catch (Horde_Db_Exception $e) {
            $this->rollbackDbTransaction();
            throw $e;
        }

        $this->removeColumn('whups_logs', 'user_id');
        $this->removeColumn('whups_logs', 'log_timestamp');

        $this->commitDbTransaction();
    }

    /**
     * @TODO:
     */
    protected function _denormalize()
    {
        $this->addColumn('whups_logs', 'user_id', 'string', array('limit' => 255, 'null' => false));
        $this->addColumn('whups_logs', 'log_timestamp', 'integer', array('null' => false));

        $sql = 'SELECT * FROM whups_transactions';
        $rows = $this->selectAll($sql);
        $sql = 'UPDATE whups_logs SET user_id = ?, log_timestamp = ? WHERE transaction_id = ?';
        $this->beginDbTransaction();
        try {
            foreach ($rows as $row) {
                $this->update(
                    $sql,
                    array(
                        $row['transaction_user_id'],
                        $row['transaction_timestamp'],
                        $row['transaction_id']));
            }
        } catch (Horde_Db_Exception $e) {
            $this->rollbackDbTransaction();
            throw $e;
        }

        $this->commitDbTransaction();
    }
}
