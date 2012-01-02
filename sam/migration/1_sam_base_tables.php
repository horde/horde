<?php
/**
 * Sam base tables.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Sam
 */
class SamBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        /* SpamAssassin table. */
        if (!in_array('userpref', $tableList)) {
            $t = $this->createTable('userpref', array('autoincrementKey' => 'prefid'));
            $t->column('username', 'string', array('limit' => 255, 'null' => false));
            $t->column('preference', 'string', array('limit' => 30, 'null' => false));
            $t->column('value', 'string', array('limit' => 100, 'null' => false));
            $t->end();

            $this->addIndex('userpref', array('username'));
        }

        /* Amavisd tables. */
        // local users
        if (!in_array('users', $tableList)) {
            $t = $this->createTable('users', array('autoincrementKey' => 'id'));
            $t->column('policy_id', 'int', array('default' => 1, 'null' => false));
            $t->column('email', 'string', array('limit' => 255, 'null' => false));
            $t->end();

            $this->addIndex('users', array('email'), array('unique' => true));
        }

        // any e-mail address, external or local, used as senders in wblist
        if (!in_array('mailaddr', $tableList)) {
            $t = $this->createTable('mailaddr', array('autoincrementKey' => 'id'));
            $t->column('email', 'string', array('limit' => 255, 'null' => false));
            $t->end();

            $this->addIndex('mailaddr', array('email'), array('unique' => true));
        }

        // per-recipient whitelist and/or blacklist,
        // puts sender and recipient in relation wb (white or blacklisted
        // sender)
        if (!in_array('wblist', $tableList)) {
            $t = $this->createTable('wblist', array('autoincrementKey' => false));
            // recipient: users.id
            $t->column('rid', 'int', array('null' => false));
            // sender: mailaddr.id
            $t->column('sid', 'int', array('null' => false));
            // W or Y / B or N
            $t->column('wb', 'string', array('limit' => 1, 'null' => false));
            $t->primaryKey(array('rid', 'sid'));
            $t->end();
        }

        if (!in_array('policy', $tableList)) {
            $t = $this->createTable('policy', array('autoincrementKey' => 'id'));
            // not used by amavisd-new
            $t->column('policy_name', 'string', array('limit' => 255));

            // Y/N
            $t->column('virus_lover', 'string', array('limit' => 1));
            // Y/N (optional field)
            $t->column('spam_lover', 'string', array('limit' => 1));
            // Y/N (optional field)
            $t->column('banned_files_lover', 'string', array('limit' => 1));
            // Y/N (optional field)
            $t->column('bad_header_lover', 'string', array('limit' => 1));

            // Y/N
            $t->column('bypass_virus_checks', 'string', array('limit' => 1));
            // Y/N
            $t->column('bypass_spam_checks', 'string', array('limit' => 1));
            // Y/N (optional field)
            $t->column('bypass_banned_checks', 'string', array('limit' => 1));
            // Y/N (optional field)
            $t->column('bypass_header_checks', 'string', array('limit' => 1));

            // Y/N (optional field)
            $t->column('spam_modifies_subj', 'string', array('limit' => 1));
            // (optional field)
            $t->column('spam_quarantine_to', 'string', array('limit' => 64, 'default' => null));

            // higher score inserts spam info headers
            $t->column('spam_tag_level', 'numeric');
            // higher score inserts 'declared spam' info header fields
            $t->column('spam_tag2_level', 'numeric', array('null' => false));
            // higher score activates evasive actions, e.g. reject/drop,
            // quarantine, ... (subject to final_spam_destiny setting)
            $t->column('spam_kill_level', 'numeric');

            // extension to add to the localpart of an address for detected
            // spam
            $t->column('addr_extension_spam', 'string', array('limit' => 32));
            // extension to add to the localpart of an address for detected
            // viruses
            $t->column('addr_extension_virus', 'string', array('limit' => 32));
            // extension to add to the localpart of an address for detected
            // banned files
            $t->column('addr_extension_banned', 'string', array('limit' => 32));
            $t->end();

            $this->addIndex('policy', array('policy_name'), array('unique' => true));
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('policy');
        $this->dropTable('wblist');
        $this->dropTable('mailaddr');
        $this->dropTable('users');

        $this->dropTable('userpref');
    }
}
