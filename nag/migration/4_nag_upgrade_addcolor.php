<?php
/**
 * Add color attribute to shares.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradeAddColor extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('nag_shares');
        $cols = $t->getColumns();
        if (!in_array('attribute_color', array_keys($cols))) {
            $this->addColumn('nag_shares', 'attribute_color', 'string', array('limit' => 7));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('nag_shares', 'attribute_color');
    }

}