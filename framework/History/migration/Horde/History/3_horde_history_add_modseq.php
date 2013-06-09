<?php
class HordeHistoryAddModSeq extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->createTable('horde_histories_modseq', array('autoincrementKey' => 'history_modseq'));
        $t->column('history_modseqempty', 'integer', array('null' => false, 'default' => 0));
        $t->end();
        $this->addColumn('horde_histories', 'history_modseq', 'integer', array('default' => 0, 'null' => false));
        $this->addIndex('horde_histories', array('history_modseq'));

        $rows= $this->selectAll('SELECT history_id FROM horde_histories ORDER BY history_ts ASC');
        $seq = 1;
        foreach ($rows as $row) {
            $this->insert('INSERT INTO horde_histories_modseq (history_modseqempty) VALUES(0)');
            $this->update('UPDATE horde_histories SET history_modseq = ? WHERE history_id = ?',
                array($seq++, $row['history_id']));
        }
        $this->delete('DELETE FROM horde_histories_modseq WHERE history_modseq <> ?', array($seq - 1));
    }

    public function down()
    {
        $this->dropTable('horde_histories_modseq');
        $this->removeColumn('horde_histories', 'history_modseq');
    }

}
