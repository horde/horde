<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Mnemo
 */

/**
 * Fixes the length on the memo_desc column.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Mnemo
 */
class MnemoFixDescSize extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('mnemo_memos', 'memo_desc', 'string', array('limit' => 255, 'null' => false));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('mnemo_memos', 'memo_desc', 'string', array('limit' => 64, 'null' => false));
    }
}
