<?php
/**
 * Driver test base.
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
 * Driver test base.
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
class Kronolith_Integration_Driver_Base extends Kronolith_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static protected $setup;

    /**
     * @static Kronolith_Driver
     */
    static protected $driver;

    /**
     * Event type to be used (depends on driver).
     *
     * @static string
     */
    static protected $type;

    /**
     * List of tasks added during the test.
     */
    private $_added = array();

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicKronolithSetup(self::$setup);
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
                self::$driver->deleteEvent($added);
            } catch (Horde_Exception_NotFound $e) {
            }
        }
    }

    private function _add(Kronolith_Event $event)
    {
        $id = self::$driver->saveEvent($event);
        $this->_added[] = $id;
        return $id;
    }

    public function testRecurrence()
    {
        $this->_add($this->_getRecurringEvent());
        $start = new Horde_Date(259200);
        $end   = new Horde_Date(345600);
        $this->assertEquals(
            1,
            count(self::$driver->listEvents($start, $end, array('show_recurrence' => true)))
        );
    }

    public function testRecurrenceException()
    {
        $this->_add($this->_getRecurringEvent());
        $start = new Horde_Date(86400);
        $end   = new Horde_Date(172800);
        $this->assertEquals(
            array(),
            self::$driver->listEvents($start, $end, array('show_recurrence' => true))
        );
    }

    private function _getRecurringEvent()
    {
        $class = 'Kronolith_Event_' . self::$type;
        $event = new $class(self::$driver);
        $event->title = 'test';
        $event->start = new Horde_Date(0);
        $event->end = new Horde_Date(14400);
        $event->recurrence = new Horde_Date_Recurrence($event->start);
        $event->recurrence->fromHash(
            array(
                'interval' => 1,
                'cycle' => 'daily',
                'range-type' => 'number',
                'range' => 4,
                'exceptions' => array(
                    '19700102',
                    '19700103'
                )
            )
        );
        return $event;
    }
}
