<?php
/**
 * Driver test base.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Driver test base.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Nag_Unit_Driver_Base extends Nag_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static $setup;

    /**
     * @static Nag_Driver
     */
    static $driver;

    /**
     * List of tasks added during the test.
     */
    private $_added = array();

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicNagSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        self::$driver = null;
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
            } catch (Horde_Exception_NotFound $e) {
            }
        }
    }

    private function _add($name, $desc, $start = 0, $due = 0, $priority = 0,
                 $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
                 array $methods = null, $uid = null, $parent = '', $private = false,
                 $owner = null, $assignee = null)
    {
        $id = self::$driver->add(
            $name, $desc, $start, $due, $priority,
            $estimate, $completed, $category, $alarm,
            $methods, $uid, $parent, $private,
            $owner, $assignee
        );
        $this->_added[] = $id[0];
        return $id;
    }

    public function testListTasks()
    {
        $this->_add('TEST','Some test task.');
        self::$driver->retrieve();
        $this->assertEquals(1, self::$driver->tasks->count());
    }

    public function testListSubTasks()
    {
        $id = $this->_add('TEST','Some test task.');
        $this->_add(
            'SUB', 'Some sub task.', 0, 0, 0, 0.0, 0, '', 0, null, null, $id[0]
        );
        self::$driver->retrieve();
        $this->assertEquals(2, self::$driver->tasks->count());
    }

    public function testDueTasks()
    {
        $due = time() + 20;
        $id = $this->_add('TEST','Some test task.', 0, $due);
        $result = self::$driver->get($id[0]);
        $this->assertEquals($due, $result->due);
    }

    public function testStartTasks()
    {
        $start = time() + 20;
        $id = $this->_add('TEST','Some test task.', $start);
        $result = self::$driver->get($id[0]);
        $this->assertEquals($start, $result->start);
    }

    public function testDelete()
    {
        $this->_add('TEST','Some test task.');
        $id = $this->_add('TEST','Some test task.');
        self::$driver->delete($id[0]);
        self::$driver->retrieve();
        $this->assertEquals(1, self::$driver->tasks->count());
    }

    public function testDeleteAll()
    {
        $this->_add('TEST','Some test task.');
        $this->_add('TEST','Some test task.');
        self::$driver->retrieve();
        $this->assertEquals(2, self::$driver->tasks->count());
        self::$driver->deleteAll();
        self::$driver->retrieve();
        $this->assertEquals(0, self::$driver->tasks->count());
    }
}
