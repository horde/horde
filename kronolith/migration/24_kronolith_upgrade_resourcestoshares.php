<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

require_once __DIR__ . '/../lib/Kronolith.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeResourcesToShares extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('kronolith_sharesng', 'attribute_email','text');
        $this->addColumn('kronolith_sharesng', 'attribute_members','text');
        $this->addColumn('kronolith_sharesng', 'attribute_response_type','integer');
        $this->addColumn('kronolith_sharesng', 'attribute_type', 'integer');
        $this->addColumn('kronolith_sharesng', 'attribute_isgroup', 'boolean', array('default' => false));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_sharesng', 'attribute_email');
        $this->removeColumn('kronolith_sharesng', 'attribute_members');
        $this->removeColumn('kronolith_sharesng', 'attribute_response_type');
        $this->removeColumn('kronolith_sharesng', 'attribute_type');
        $this->removeColumn('kronolith_sharesng', 'attribute_isgroup');

    }

}
