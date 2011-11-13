<?php
/**
 * Add user_id to the trean_bookmarks table.
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
class AddUserId extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('trean_bookmarks');
        $cols = $t->getColumns();
        if (!in_array('user_id', array_keys($cols))) {
            $this->addColumn('trean_bookmarks', 'user_id', 'integer', array('null' => false, 'unsigned' => true));
        }
    }

    public function down()
    {
        $this->removeColumn('trean_bookmarks', 'user_id');
    }
}
