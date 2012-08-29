<?php
/**
 * Create Pastie base tables (as of Nag 2.x).
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Pastie
 */
class PastieBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('pastie_pastes', $tableList)) {
            $t = $this->createTable('pastie_pastes', array('autoincrementKey' => 'paste_id'));
            $t->column('paste_uuid', 'string', array('limit' => 40, 'null' => false));
            $t->column('paste_bin', 'string', array('limit' => 64, 'null' => false));
            $t->column('paste_title', 'string', array('limit' => 255));
            $t->column('paste_syntax', 'string', array('limit' => 16));
            $t->column('paste_content', 'text');
            $t->column('paste_owner', 'string', array('limit' => 255));
            $t->column('paste_timestamp', 'integer', array('null' => false));
            $t->end();
            $this->addIndex('paste_uuid', array('paste_uuid'));
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('pastie_pastes');
    }
}
