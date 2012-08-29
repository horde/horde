<?php
/**
 * Run the changes to migrate from folders to tags.
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
class TreanUpgradeFoldersToTags extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // This migration step used to migrate old DataTree folder
        // structures. DataTree has been removed since then.
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        // Not supported. One-way change. Also not destructive on its own.
    }
}
