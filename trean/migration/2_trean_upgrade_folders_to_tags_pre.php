<?php
/**
 * Steps to perform to bring the database tables from the old folder structure
 * up to being ready for the new tags structure.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class TreanUpgradeFoldersToTagsPre extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('trean_bookmarks', 'bookmark_id', 'autoincrementKey');
        try {
            $this->dropTable('trean_bookmarks_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $t = $this->_connection->table('trean_bookmarks');
        $cols = $t->getColumns();

        if (!in_array('bookmark_dt', array_keys($cols))) {
            $this->addColumn('trean_bookmarks', 'bookmark_dt', 'datetime');
        }

        if (!in_array('user_id', array_keys($cols))) {
            $this->addColumn('trean_bookmarks', 'user_id', 'integer', array('unsigned' => true));
            $this->addIndex('trean_bookmarks', array('user_id'));
        }

        $this->changeColumn('trean_bookmarks', 'bookmark_clicks', 'integer', array('unsigned' => true, 'default' => 0));
        $this->changeColumn('trean_bookmarks', 'bookmark_description', 'string', array('limit' => 1024));
        $this->changeColumn('trean_bookmarks', 'bookmark_url', 'string', array('limit' => 1024));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('trean_bookmarks', 'user_id');
        $this->removeColumn('trean_bookmarks', 'bookmark_dt');
        $this->changeColumn('trean_bookmarks', 'bookmark_id', 'integer', array('null' => false));
        $this->changeColumn('trean_bookmarks', 'bookmark_url', 'string', array('limit' => 255));
        $this->changeColumn('trean_bookmarks', 'bookmark_description', 'string', array('limit' => 255));
    }
}
