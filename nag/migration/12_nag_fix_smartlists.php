<?php
/**
 * Fix smart lists flag in PostrgreSQL (Bug #12101).
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagFixSmartlists extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->update('UPDATE nag_shares SET attribute_issmart = 0 WHERE attribute_issmart IS NULL');
        $this->update('UPDATE nag_sharesng SET attribute_issmart = 0 WHERE attribute_issmart IS NULL');
    }

    /**
     * Downgrade
     */
    public function down()
    {
    }
}
