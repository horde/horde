<?php
/**
 * Add fields for handling smart lists as shares.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradesmartlists extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('nag_shares', 'attribute_issmart', 'integer', array('default' => 0));
        $this->addColumn('nag_shares', 'attribute_search', 'text');
        $this->addColumn('nag_sharesng', 'attribute_issmart', 'integer', array('default' => 0));
        $this->addColumn('nag_sharesng', 'attribute_search', 'text');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('nag_shares', 'attribute_issmart');
        $this->removeColumn('nag_sharesng', 'attribute_issmart');
        $this->removeColumn('nag_shares', 'attribute_search');
        $this->removeColumn('nag_sharesng', 'attribute_search');
    }

}
