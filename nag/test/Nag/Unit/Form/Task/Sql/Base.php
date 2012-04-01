<?php
/**
 * Task form test base for the SQL driver.
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
 * Task form test base for the SQL driver.
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
abstract class Nag_Unit_Form_Task_Sql_Base extends Nag_Unit_Form_Task_Base
{
    static public function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::getDb();
        self::createSqlShares(self::$setup);
        list($share, $other_share) = self::_createDefaultShares();
    }

    /*abstract*/ static protected function getDb()
    {
        throw new Exception('This method must be extended by the sub-class.');
    }
}
