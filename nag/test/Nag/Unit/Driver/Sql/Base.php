<?php
/**
 * Test base for the SQL driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test base for the SQL driver.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Nag_Unit_Driver_Sql_Base extends Nag_Unit_Driver_Base
{
    static $callback;

    static public function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::getDb();
        self::createSqlShares(self::$setup);
        list($share, $other_share) = self::_createDefaultShares();
        self::$driver = new Nag_Driver_Sql(
            $share->getName(), array('charset' => 'UTF-8')
        );
    }

    static protected function getDb()
    {
        call_user_func_array(self::$callback, array());
    }
}
