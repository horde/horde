<?php
/**
 * Test the SQL driver with a sqlite DB.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the SQL driver with a sqlite DB.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Kronolith_Integration_Driver_Sql_Pdo_SqliteTest extends Kronolith_Integration_Driver_Sql_Base
{
    protected $backupGlobals = false;

    static public function setUpBeforeClass()
    {
        self::$callback = array(__CLASS__, 'getDb');
        parent::setUpBeforeClass();
        $migrator = new Horde_Db_Migration_Migrator(
            $GLOBALS['injector']->getInstance('Horde_Db_Adapter'),
            null,
            array(
                'migrationsPath' => __DIR__ . '/../../../../../../migration',
                'schemaTableName' => 'kronolith_test_schema'
            )
        );
        $migrator->up();

        list($share, $other_share) = self::_createDefaultShares();
        self::$driver = Kronolith::getDriver('Sql', $share->getName());
        self::$type = 'Sql';
    }

    static protected function getDb()
    {
        self::createSqlPdoSqlite(self::$setup);
    }
}