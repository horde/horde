<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @package    Lock
 * @subpackage UnitTests
 */
class Horde_Lock_Storage_SqlTest extends Horde_Lock_Storage_TestBase
{
    protected static $_migrationDir;

    public static function setUpBeforeClass()
    {
        self::$_migrationDir = __DIR__ . '/../../../../migration/Horde/Lock';

        if (!is_dir(self::$_migrationDir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            self::$_migrationDir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Lock/migration';
            error_reporting(E_ALL | E_STRICT);
        }
    }

    protected function _getBackend()
    {
        $factory_db = new Horde_Test_Factory_Db();

        try {
            $db = $factory_db->create(array(
                'migrations' => array(
                    'migrationsPath' => self::$_migrationDir
                )
            ));
        } catch (Horde_Test_Exception $e) {
            $this->markTestSkipped('Test DB not available.');
        }

        return new Horde_Lock_Sql(array(
            'db' => $db
        ));
    }

}
