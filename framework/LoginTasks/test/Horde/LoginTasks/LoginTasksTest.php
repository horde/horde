<?php
/**
 * Test the LoginTasks class.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=LoginTasks
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Test the LoginTasks class.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=LoginTasks
 */

class Horde_LoginTasks_LoginTasksTest extends PHPUnit_Framework_TestCase
{
    public function testNoTasksAreRanIfNoUserIsAuthenticated()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array('Horde_LoginTasks_Stub_Task'));
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTheTasksAreRanIfTheUserIsAuthenticated()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array('Horde_LoginTasks_Stub_Task'), true);
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Task',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testNoTasksAreRanIfTheTasklistIsEmpty()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array(), true);
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testNoTasksAreRanIfTheTasklistIsCompleted()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array('Horde_LoginTasks_Stub_Task'), true);
        $tasks->runTasks();
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTasksWithHighPriorityAreExecutedBeforeTasksWithLowPriority()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
            ),
            true
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Task'
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTasksThatRepeatYearlyAreExecutedAtTheBeginningOfEachYear()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Year'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['year']--;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Year'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Year',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testTasksThatRepeatMonthlyAreExecutedAtTheBeginningOfEachMonth()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Month'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mon']--;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Month'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Month',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testTasksThatRepeatWeeklyAreExecutedAtTheBeginningOfEachWeek()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Week'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mday'] = $date['mday'] - 7;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Week'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Week',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testTasksThatRepeatDailyAreExecutedAtTheBeginningOfEachDay()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Day'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mday'] = $date['mday'] - 1;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Day'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Day',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testTasksThatRepeatEachLoginAreExecutedOnEachLogin()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Task'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array('Horde_LoginTasks_Stub_Task'),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTasksThatAreExecutedOnFirstLoginAreExecutedOnlyThen()
    {
        Horde_LoginTasks_Stub_First::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_First'), true, false
        );
        $tasks->runTasks();
        $this->assertEquals(
            array('Horde_LoginTasks_Stub_First'),
            Horde_LoginTasks_Stub_Task::$executed
        );

        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_First'), true, $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTasksThatRunOnceAreNotExecutedMoreThanOnce()
    {
        $prefs = new Horde_LoginTasks_Stub_Prefs();

        Horde_LoginTasks_Stub_Once::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Once'), true, false, $prefs
        );
        $tasks->runTasks();
        $this->assertEquals(
            array('Horde_LoginTasks_Stub_Once'),
            Horde_LoginTasks_Stub_Task::$executed
        );

        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Once'), true, false, $prefs
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testAllTasksGetRunIfNoTasksRequiresDisplay()
    {
        $prefs = new Horde_LoginTasks_Stub_Prefs();
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
            ),
            true,
            false,
            $prefs
        );
        $tasks->runTasks();
        $v = $prefs->getValue('last_logintasks');
        $this->assertTrue(!empty($v));
    }

    public function testTheLastTimeOfCompletingTheLoginTasksWillBeStoredOnceAllTasksWereExcecuted()
    {
        $prefs = new Horde_LoginTasks_Stub_Prefs();
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
            ),
            true,
            false,
            $prefs
        );
        $tasks->runTasks();
        $v = unserialize($prefs->getValue('last_logintasks'));
        $this->assertTrue($v['horde'] > time() - 10);
    }

    public function testAllTasksToBeRunBeforeTheFirstTaskRequiringDisplayGetExecutedInABatch()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
            ),
            true
        );
        $tasks->runTasks(false, null);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Task'
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTheFirstTaskRequiringDisplayRedirectsToTheLoginTasksUrl()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
            ),
            true
        );
        $this->assertContains(
            'http:///services/logintasks.php?app=test',
            (string) $tasks->runTasks(false, null)
        );
    }

    public function testADisplayTaskWillBeExecutedOnceDisplayed()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
            ),
            true
        );
        $tasks->runTasks(false, null);
        $tasklist = $tasks->displayTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Notice',
            get_class($tasklist[0])
        );
    }

    public function testSeveralSubsequentTasksWithTheSameDisplayOptionGetDisplayedTogether()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_NoticeTwo',
            ),
            true
        );
        $tasks->runTasks(false, null);
        $tasklist = $tasks->displayTasks();
        $classes = array();
        foreach ($tasklist as $task) {
            $classes[] = get_class($task);
        }
        asort($classes);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_NoticeTwo'
            ),
            $classes
        );
    }

    public function testSeveralSubsequentTasksWithTheSameDisplayOptionGetExecutedTogether()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_NoticeTwo',
            ),
            true
        );
        $tasks->runTasks(false, null);
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks->displayTasks();
        $tasks->runTasks(true, null);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_NoticeTwo',
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testAfterConfirmationOfADisplayedTaskTheUserIsRedirectedToTheUrlStoredBeforeDisplaying()
    {
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Notice',
            ),
            true
        );
        $tasks->runTasks(false, 'redirect');
        $tasks->displayTasks();
        $this->assertEquals(
            'redirect', $tasks->runTasks(true, null)
        );
    }

    public function testConfirmSeriesDisplay()
    {
        //$this->markTestIncomplete();
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_ConfirmTwo',
                'Horde_LoginTasks_Stub_TaskTwo',
                'Horde_LoginTasks_Stub_ConfirmThree',
                'Horde_LoginTasks_Stub_NoticeTwo',
            ),
            true
        );
        $this->assertContains(
            'http:///services/logintasks.php?app=test',
            (string) $tasks->runTasks(false, 'redirect')
        );
        $this->assertEquals(
            array(
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
        $tasklist = $tasks->displayTasks();
        $classes = array();
        foreach ($tasklist as $task) {
            $classes[] = get_class($task);
        }
        asort($classes);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm'
            ),
            $classes
        );
        $_POST['logintasks_confirm_0'] = true;
        $_POST['logintasks_confirm_1'] = true;
        $this->assertNull(
            $tasks->runTasks(true)
        );
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
        $_POST = array();
        $this->assertNull(
            $tasks->runTasks(false)
        );
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
        $tasklist = $tasks->displayTasks();
        $classes = array();
        foreach ($tasklist as $task) {
            $classes[] = get_class($task);
        }
        asort($classes);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_Confirm'
            ),
            $classes
        );
    }

    private function _getLoginTasks(
        array $tasks = array(),
        $authenticated = false,
        $last_run = false,
        $prefs = false
    ) {
        if ($authenticated) {
            $_SESSION['horde_auth']['userId'] = 'test';
        }
        $last_time = false;
        if ($last_run) {
            $last_time = mktime(
                $last_run['hours'],
                $last_run['minutes'],
                $last_run['seconds'],
                $last_run['mon'],
                $last_run['mday'],
                $last_run['year']
            );
            $last_time = serialize(
                array(
                    'test' => $last_time
                )
            );
        }
        if (empty($prefs)) {
            $GLOBALS['prefs'] = $this->getMock('Horde_Prefs', array(), array(), '', false, false);
            $GLOBALS['prefs']->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue($last_time));
        } else {
            $GLOBALS['prefs'] = $prefs;
        }
        $GLOBALS['registry'] = $this->getMock('Horde_Registry', array(), array(), '', false, false);
        $GLOBALS['registry']->expects($this->any())
            ->method('getAppDrivers')
            ->will($this->returnValue($tasks));
        return new Horde_LoginTasks(
            new Horde_LoginTasks_Stub_Backend(
                $GLOBALS['registry'],
                $GLOBALS['prefs'],
                'test'
            )
        );
    }
}
