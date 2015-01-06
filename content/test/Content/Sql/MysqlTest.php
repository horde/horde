<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Content_Sql_MysqlTest extends Content_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('CONTENT_SQL_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['content']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['content']['sql']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
