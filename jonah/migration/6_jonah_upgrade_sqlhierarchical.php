<?php
/**
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license 
 * @package  Jonah
 */

require_once dirname(__FILE__) . '/../lib/Jonah.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license
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
