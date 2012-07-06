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

    private function _add($task)
    {
        $id = self::$driver->add($task);
        $this->_added[] = $id[0];
        return $id;
    }

    public function testListTasks()
    {
        $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        self::$driver->retrieve();
        $this->assertEquals(1, self::$driver->tasks->count());
    }

    public function testListSubTasks()
    {
        $id = $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        $this->_add(array('name' => 'SUB',
                          'desc' => 'Some sub task.',
                          'parent' => $id[0]));
        self::$driver->retrieve();
        $this->assertEquals(2, self::$driver->tasks->count());
    }

    public function testDueTasks()
    {
        $due = time() + 20;
        $id = $this->_add(array('name' => 'TEST',
                                'desc' => 'Some test task.',
                                'due' => $due));
        $result = self::$driver->get($id[0]);
        $this->assertEquals($due, $result->due);
    }

    public function testStartTasks()
    {
        $start = time() + 20;
        $id = $this->_add(array('name' => 'TEST',
                                'desc' => 'Some test task.',
                                'start' => $start));
        $result = self::$driver->get($id[0]);
        $this->assertEquals($start, $result->start);
    }

    public function testRecurringTasks()
    {
        $due = time() - 1;
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $id = $this->_add(array('name' => 'TEST',
                                'desc' => 'Some test task.',
                                'due' => $due,
                                'recurrence' => $recurrence));
        $due = new Horde_Date($due);
        $result = self::$driver->get($id[0]);
        $next = $result->getNextDue();
        $this->assertInstanceOf('Horde_Date', $next);
        $this->assertEquals($due->timestamp(), $next->timestamp());
        $result->toggleComplete();
        $result->save();
        $result2 = self::$driver->get($id[0]);
        $due->mday++;
        $next = $result2->getNextDue();
        $this->assertInstanceOf('Horde_Date', $next);
        $this->assertEquals($due->timestamp(), $next->timestamp());
        $result2->toggleComplete();
        $result2->save();
        $result3 = self::$driver->get($id[0]);
        $due->mday++;
        $next = $result3->getNextDue();
        $this->assertInstanceOf('Horde_Date', $next);
        $this->assertEquals($due->timestamp(), $next->timestamp());
        $this->assertFalse($result3->recurrence->hasCompletion($due->year, $due->month, $due->mday));
        $due->mday--;
        $this->assertTrue($result3->recurrence->hasCompletion($due->year, $due->month, $due->mday));
        $due->mday--;
        $this->assertTrue($result3->recurrence->hasCompletion($due->year, $due->month, $due->mday));
    }

    public function testModify()
    {
        $id = $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        self::$driver->modify($id[0], array('desc' => 'Modified'));
        $result = self::$driver->get($id[0]);
        $this->assertEquals('Modified', $result->desc);
        $result->name = 'MODIFIED';
        $result->save();
        $result2 = self::$driver->get($id[0]);
        $this->assertEquals('MODIFIED', $result2->name);
    }

    public function testDelete()
    {
        $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        $id = $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        self::$driver->delete($id[0]);
        self::$driver->retrieve();
        $this->assertEquals(1, self::$driver->tasks->count());
    }

    public function testDeleteAll()
    {
        $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        $this->_add(array('name' => 'TEST', 'desc' => 'Some test task.'));
        self::$driver->retrieve();
        $this->assertEquals(2, self::$driver->tasks->count());
        self::$driver->deleteAll();
        self::$driver->retrieve();
        $this->assertEquals(0, self::$driver->tasks->count());
    }
}
