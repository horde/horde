<?php
/**
 * Remove non-internal channels.
 *
 * Copyright 2010-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Jonah
 */
class JonahUpgradeDropNonInternal extends Horde_Db_Migration_Base
{

    public function up()
    {
        $sql = 'DELETE FROM jonah_channels WHERE channel_type > 0';
        $this->delete($sql);
        $this->removeColumn('jonah_channels', 'channel_type');
    }

    public function down()
    {
        $this->addColumn('jonah_channels', 'channel_type', 'integer');
    }
}