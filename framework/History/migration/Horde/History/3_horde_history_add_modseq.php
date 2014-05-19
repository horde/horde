<?php
class HordeHistoryAddModSeq extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->createTable('horde_histories_modseq', array('autoincrementKey' => false));
        $t->column('history_modseq', 'integer', array('null' => false, 'default' => 0));
        $t->column('history_modseqempty', 'integer', array('null' => false, 'default' => 0));
        $t->end();
        $this->addColumn('horde_histories', 'history_modseq', 'integer', array('default' => 0, 'null' => false));

        $rows = $this->selectAll('SELECT history_id FROM horde_histories ORDER BY history_ts ASC');
        $seq = 1;

        $this->beginDbTransaction();
        foreach ($rows as $row) {
            $this->update(
                'UPDATE horde_histories SET history_modseq = ? WHERE history_id = ?',
                array($seq++, $row['history_id']));
        }
        if (!empty($rows)) {
            $this->insert('INSERT INTO horde_histories_modseq (history_modseq) VALUES(?)', array($seq - 1));
        }
        $this->commitDbTransaction();

        // Add the index after the new values are set for performance reasons.
        $this->addIndex('horde_histories', array('history_modseq'));

        // ...same with the autoincrement for this field.
        $this->changeColumn('horde_histories_modseq', 'history_modseq', 'autoincrementKey');
    }

    public function down()
    {
        $this->dropTable('horde_histories_modseq');
        $this->removeColumn('horde_histories', 'history_modseq');
    }

}
