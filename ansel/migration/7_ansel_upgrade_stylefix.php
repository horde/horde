<?php
/**
 * Upgrade to Ansel 2 style schema
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeStylefix extends Horde_Db_Migration_Base
{
    public function up()
    {
        // noop - fix is now included in original 3_ migration.
    }

    /**
     * Downgrade, though all style information will be lost and reverted to
     * 'ansel_default'.
     */
    public function down()
    {
        // noop
    }

}