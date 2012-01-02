<?php
/**
 * Test the core Mnemo class with a SQL backend.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/mnemo
 * @license    http://www.horde.org/licenses/apache
 */

/**
 * Test the core Mnemo class with a SQL backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/mnemo
 * @license    http://www.horde.org/licenses/apache
 */
class Mnemo_Unit_Mnemo_Sql_Base extends Mnemo_Unit_Mnemo_Base
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::createSqlShares(self::$setup);
    }
}
