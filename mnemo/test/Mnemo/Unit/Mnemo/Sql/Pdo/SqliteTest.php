<?php
/**
 * Test the core Mnemo driver with a sqlite DB.
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
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the core Mnemo driver with a sqlite DB.
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
class Mnemo_Unit_Mnemo_Sql_Pdo_SqliteTest extends Mnemo_Unit_Mnemo_Sql_Base
{
    protected $backupGlobals = false;

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createSqlPdoSqlite(self::$setup);
        parent::setUpBeforeClass();
    }
}