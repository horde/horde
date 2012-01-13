<?php
/**
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Whups
 */

/**
 * Adds a primary key to the whups_attributes table.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Whups
 */
class WhupsAddAttributeIndex extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addPrimaryKey('whups_attributes', array('ticket_id', 'attribute_id'));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removePrimaryKey('whups_attributes');
    }

}
