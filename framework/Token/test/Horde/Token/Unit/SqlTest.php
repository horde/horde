<?php
/**
 * Test the SQL based token backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Token
 */

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Token
 */
class Horde_Token_Unit_SqlTest extends Horde_Token_BackendTestCase
{
    private static $_db;

    public static function setUpBeforeClass()
    {
        $factory_db = new Horde_Test_Factory_Db();

        try {
            self::$_db = $factory_db->create(array(
                'migrations' => array(
                    'migrationsPath' => __DIR__ . '/../../../../migration/Horde/Token'
                )
            ));
        } catch (Horde_Test_Exception $e) {
            return;
        }
    }

    public function setUp()
    {
        if (!isset(self::$_db)) {
            $this->markTestSkipped('Sqlite not available.');
        }
    }

    protected function _getBackend(array $params = array())
    {
        $params = array_merge(
            array(
                'secret' => 'abc',
                'db' => self::$_db
            ),
            $params
        );
        return new Horde_Token_Sql($params);
    }

}
