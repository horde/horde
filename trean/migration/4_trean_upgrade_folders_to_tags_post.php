<?php
/**
 * Once folders have been converted to tags, remove old data
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
class TreanUpgradeFoldersToTagsPost extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->removeColumn('trean_bookmarks', 'bookmark_rating');
        $this->removeColumn('trean_bookmarks', 'folder_id');

        $this->changeColumn('trean_bookmarks', 'user_id', 'integer', array('unsigned' => true, 'null' => false));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->addColumn('trean_bookmarks', 'folder_id', 'integer');
        $this->addColumn('trean_bookmarks', 'bookmark_rating', 'integer', array('default' => 0));
    }
}
