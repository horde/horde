<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * Fixes the type of the parents column.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeDescriptionToText extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('kronolith_shares', 'attribute_desc', 'text');
        $this->changeColumn('kronolith_sharesng', 'attribute_desc', 'text');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('kronolith_shares', 'attribute_desc', 'string', array('limit' => 255));
        $this->changeColumn('kronolith_sharesng', 'attribute_desc', 'string', array('limit' => 255));
    }
}
