<?php
/**
 * Create Trean base tables (as of Trean 1.x).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Trean
 */
class TreanBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('trean_bookmarks', $tableList)) {
            $t = $this->createTable('trean_bookmarks', array('autoincrementKey' => 'bookmark_id'));
            $t->column('user_id', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('favicon_id', 'integer', array('unsigned' => true));
            $t->column('bookmark_url', 'string', array('limit' => 1024, 'null' => false));
            $t->column('bookmark_title', 'string', array('limit' => 255));
            $t->column('bookmark_description', 'string', array('limit' => 1024));
            $t->column('bookmark_clicks', 'integer', array('default' => 0));
            $t->column('bookmark_http_status', 'string', array('limit' => 5));
            $t->end();
            $this->addIndex('trean_bookmarks', array('user_id'));
            $this->addIndex('trean_bookmarks', array('bookmark_clicks'));
        }

        if (!in_array('trean_favicons', $tableList)) {
            $t = $this->createTable('trean_favicons', array('autoincrementKey' => 'favicon_id'));
            $t->column('favicon_url', 'text', array('null' => false));
            $t->column('favicon_updated', 'integer', array('null' => false, 'unsigned' => true));
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('trean_bookmarks');
        $this->dropTable('trean_favicons');
    }
}
