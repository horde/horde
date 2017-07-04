<?php
/**
 * Test base for the SQL driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * Test base for the SQL driver.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Turba_Unit_Driver_Sql_Base extends Turba_Unit_Driver_Base
{
    static $callback;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::getDb();
        self::$driver = self::createSqlDriverWithShares(self::$setup);
    }

    protected static function getDb()
    {
        call_user_func_array(self::$callback, array());
    }

    public function testDuplicateDetectionFromAsWithNoEmail()
    {
        $eas_obj = new Horde_ActiveSync_Message_Contact(array(
            'device' => new Horde_ActiveSync_Device(
                new Horde_ActiveSync_State_Sql(array(
                    'db' => self::$setup->getInjector()
                        ->getInstance('Horde_Db_Adapter')
                ))
            )
        ));
        $eas_obj->firstname = 'Firstname';
        $eas_obj->fileas = 'Firstname';
        $eas_obj->homephonenumber = '+55555555';
        $hash = self::$driver->fromASContact($eas_obj);
        self::$driver->add($hash);
        unset($hash['phototype']);
        $result = self::$driver->search($hash, array());
        $this->assertEquals(1, count($result));
    }
}
