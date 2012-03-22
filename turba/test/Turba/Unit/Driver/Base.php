<?php
/**
 * Driver test base.
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
require_once __DIR__ . '/../../TestCase.php';

/**
 * Driver test base.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Turba_Unit_Driver_Base extends Turba_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static $setup;

    /**
     * @static Turba_Driver
     */
    static $driver;

    /**
     * List of tasks added during the test.
     */
    private $_added = array();

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicTurbaSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        self::$driver = null;
        self::tearDownBasicTurbaSetup();
        self::tearDownShares();
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        $error = self::$setup->getError();
        if (!empty($error)) {
            $this->markTestSkipped($error);
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        foreach ($this->_added as $added) {
            try {
                self::$driver->delete($added);
            } catch (Turba_Exception $e) {
            }
        }
    }

    private function _add($attributes)
    {
        $id = self::$driver->add($attributes);
        $this->_added[] = $id;
        return $id;
    }

    public function testAdd()
    {
        $id = $this->_add(array('lastname' => 'TEST'));
        $contact = self::$driver->getObject($id);
        $this->assertEquals('TEST', $contact->attributes['lastname']);
    }

    public function testNullSearch()
    {
        $this->assertInstanceOf(
            'Turba_List',
            self::$driver->search(array(), null, 'AND')
        );
    }
}
