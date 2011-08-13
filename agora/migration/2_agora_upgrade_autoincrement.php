<?php
/**
 * Adds autoincrement flags.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Vilius Šumskas <vilius@lnk.lt>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Agora
 */
class AgoraUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('agora_files', 'file_id', 'autoincrementKey');
        try {
            $this->dropTable('agora_files_seq');
        } catch (Horde_Db_Exception $e) {}

        $this->changeColumn('agora_forums', 'forum_id', 'autoincrementKey');
        try {
            $this->dropTable('agora_forums_seq');
        } catch (Horde_Db_Exception $e) {}

        $this->changeColumn('agora_messages', 'message_id', 'autoincrementKey');
        try {
            $this->dropTable('agora_messages_seq');
        } catch (Horde_Db_Exception $e) {}
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('agora_files', 'file_id', 'integer', array('autoincrement' => false));
        $this->changeColumn('agora_forums', 'forum_id', 'integer', array('autoincrement' => false));
        $this->changeColumn('agora_messages', 'message_id', 'integer', array('autoincrement' => false));
    }

}
