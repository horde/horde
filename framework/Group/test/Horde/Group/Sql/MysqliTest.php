<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Group_Sql_MysqliTest extends Horde_Group_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('GROUP_SQL_MYSQLI_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['group']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['group']['sql']['mysqli']);
            parent::setUpBeforeClass();
        }
    }
}
