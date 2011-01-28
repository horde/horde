<?php
/**
 * Create Wicked base tables (as of Wicked 1.x).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Wicked
 */
class WickedBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('wicked_pages', $tableList)) {
            $t = $this->createTable('wicked_pages', array('primaryKey' => false));
            $t->column('page_id', 'integer', array('null' => false));
            $t->column('page_name', 'string', array('limit' => 100, 'null' => false));
            $t->column('page_text', 'text');
            $t->column('page_hits', 'integer', array('default' => 0));
            $t->column('page_majorversion', 'integer', array('null' => false));
            $t->column('page_minorversion', 'integer', array('null' => false));
            $t->column('version_created', 'integer', array('null' => false));
            $t->column('change_author', 'string');
            $t->column('change_log', 'text');
            $t->primaryKey(array('page_id'));
            $t->end();

            $this->addIndex('wicked_pages', array('page_name'), array('unique' => true));
        }

        if (!in_array('wicked_history', $tableList)) {
            $t = $this->createTable('wicked_history', array('primaryKey' => false));
            $t->column('page_id', 'integer', array('null' => false));
            $t->column('page_name', 'string', array('limit' => 100, 'null' => false));
            $t->column('page_text', 'text');
            $t->column('page_majorversion', 'integer', array('null' => false));
            $t->column('page_minorversion', 'integer', array('null' => false));
            $t->column('version_created', 'integer', array('null' => false));
            $t->column('change_author', 'string');
            $t->column('change_log', 'text');
            $t->primaryKey(array('page_id', 'page_majorversion', 'page_minorversion'));
            $t->end();
            $this->addIndex('wicked_history', array('page_name'));
            $this->addIndex('wicked_history', array('page_majorversion', 'page_minorversion'));
        }

        if (!in_array('wicked_attachments', $tableList)) {
            $t = $this->createTable('wicked_attachments', array('primaryKey' => false));
            $t->column('page_id', 'integer', array('null' => false));
            $t->column('attachment_name', 'string', array('limit' => 100, 'null' => false));
            $t->column('attachment_hits', 'integer', array('default' => 0));
            $t->column('attachment_majorversion', 'integer', array('null' => false));
            $t->column('attachment_minorversion', 'integer', array('null' => false));
            $t->column('attachment_created', 'integer', array('null' => false));
            $t->column('change_author', 'string');
            $t->column('change_log', 'text');
            $t->primaryKey(array('page_id', 'attachment_name'));
            $t->end();
        }

        if (!in_array('wicked_attachment_history', $tableList)) {
            $t = $this->createTable('wicked_attachment_history', array('primaryKey' => false));
            $t->column('page_id', 'integer', array('null' => false));
            $t->column('attachment_name', 'string', array('limit' => 100, 'null' => false));
            $t->column('attachment_majorversion', 'integer', array('null' => false));
            $t->column('attachment_minorversion', 'integer', array('null' => false));
            $t->column('attachment_created', 'integer', array('null' => false));
            $t->column('change_author', 'string');
            $t->column('change_log', 'text');
            $t->primaryKey(array('page_id', 'attachment_name', 'attachment_majorversion', 'attachment_minorversion'));
            $t->end();
            $this->addIndex('wicked_attachment_history', array('attachment_majorversion', 'attachment_minorversion'));
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('wicked_pages');
        $this->dropTable('wicked_history');
        $this->dropTable('wicked_attachments');
        $this->dropTable('wicked_attachment_history');
    }
}
