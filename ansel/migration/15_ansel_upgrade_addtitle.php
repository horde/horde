<?php
/**
 * Fix column type of ansel_shares_users.user_uid.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeAddtitle extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('ansel_images', 'image_title', 'string', array('limit' => 255));
        $this->announce('Copying image_filename to image_title.', 'cli.message');
        // Not really a great comparison, but this will maintain the current
        // image "titles" as displayed by Ansel 2
        $this->_connection->update('UPDATE ansel_images SET image_title = image_caption');
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->removeColumn('ansel_images', 'image_title');
    }
}
