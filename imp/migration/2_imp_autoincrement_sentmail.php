<?php
/**
 * Change sentmail_id column to autoincrement.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class ImpAutoIncrementSentmail extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('imp_sentmail', 'sentmail_id', 'autoincrementKey');
        try {
            $this->dropTable('imp_sentmail_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->changeColumn('imp_sentmail', 'sentmail_id', 'bigint', array('autoincrement' => false));
    }

}
