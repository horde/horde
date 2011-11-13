<?php
/**
 * Add bookmark_dt to the trean_bookmarks table.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class AddBookmarkDt extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('trean_bookmarks');
        $cols = $t->getColumns();
        if (!in_array('bookmark_dt', array_keys($cols))) {
            $this->addColumn('trean_bookmarks', 'bookmark_dt', 'datetime');
        }
    }

    public function down()
    {
        $this->removeColumn('trean_bookmarks', 'bookmark_dt');
    }
}
