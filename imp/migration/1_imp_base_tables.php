<?php
/**
 * Create IMP base tables.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class ImpBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // Create: imp_sentmail
        $t = $this->createTable('imp_sentmail', array('primaryKey' => 'sentmail_id'));
        $t->column('sentmail_id', 'integer', array('autoincrement' => true));
        $t->column('sentmail_who', 'string', array('limit' => 255, 'null' => false));
        $t->column('sentmail_ts', 'bigint', array('null' => false));
        $t->column('sentmail_messageid', 'string', array('limit' => 255, 'null' => false));
        $t->column('sentmail_action', 'string', array('limit' => 32, 'null' => false));
        $t->column('sentmail_recipient', 'string', array('limit' => 255, 'null' => false));
        $t->column('sentmail_success', 'integer', array('null' => false));
        $t->end();

        $this->addIndex('imp_sentmail', array('sentmail_ts'));
        $this->addIndex('imp_sentmail', array('sentmail_who'));
        $this->addIndex('imp_sentmail', array('sentmail_success'));
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('imp_sentmail');
    }

}
