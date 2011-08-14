<?php
/**
 * Create Klutz base tables (as of Klutz 2.x).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Klutz
 */
class KlutzBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('klutz_comics', $tableList)) {
            $t = $this->createTable('klutz_comics', array('autoincrementKey' => 'comicpic_id'));
            $t->column('comicpic_date', 'integer', array('null' => false));
            $t->column('comicpic_key', 'string', array('limit' => 255, 'null' => false));
            $t->column('comicpic_hash', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('klutz_comics', array('comicpic_date', 'comicpic_hash'));
            $this->addIndex('klutz_comics', array('comicpic_key'));
            $this->addIndex('klutz_comics', array('comicpic_hash'));
        }
    }

    public function down()
    {
        $this->dropTable('klutz_comics');
    }
}
