<?php
/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Jonah
 */
class JonahUpgradeSqlhierarchical extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('jonah_shares', 'share_parents','text');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('jonah_shares', 'share_parents');
    }

}
