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
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Test base for the SQL driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Nag_Unit_Driver_Sql_Base extends Nag_Unit_Driver_Base
{
    /**
     * @static Horde_Db_Adapter_Base
     */
    static $db;

    /**
     * @static Horde_Db_Migration_Migrator
     */
    static $migrator;

    public static function setUpBeforeClass()
    {
        // FIXME: get migration directory if not running from Git checkout.
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,
            array('migrationsPath' => dirname(__FILE__) . '/../../../../../migration',
                  'schemaTableName' => 'nag_test_schema'));

        self::$migrator->up();
        self::$driver = self::getSqlDriver(self::$db);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
        self::$db = null;
        parent::tearDownAfterClass();
    }
}
