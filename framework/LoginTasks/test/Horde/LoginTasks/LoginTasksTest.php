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
    public function testTheTasksAreRun()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array('Horde_LoginTasks_Stub_Task'));
        $tasks->runTasks();
        $this->assertEquals(
            'Horde_LoginTasks_Stub_Task',
            Horde_LoginTasks_Stub_Task::$executed[0]
        );
    }

    public function testNoTasksAreRanIfTheTasklistIsEmpty()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks();
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testNoTasksAreRanIfTheTasklistIsCompleted()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(array('Horde_LoginTasks_Stub_Task'));
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
            )
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
            array('Horde_LoginTasks_Stub_Year'),
            $date
        );
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['year']--;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Year'),
            $date
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
            array('Horde_LoginTasks_Stub_Month'),
            $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mon']--;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Month'),
            $date
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
            array('Horde_LoginTasks_Stub_Week'),
            $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mday'] = $date['mday'] - 7;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Week'),
            $date
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
            array('Horde_LoginTasks_Stub_Day'),
            $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );

        $date['mday'] = $date['mday'] - 1;

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Day'),
            $date
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
            array('Horde_LoginTasks_Stub_Task'),
            $date
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
            array('Horde_LoginTasks_Stub_First')
        );
        $tasks->runTasks();
        $this->assertEquals(
            array('Horde_LoginTasks_Stub_First'),
            Horde_LoginTasks_Stub_Task::$executed
        );

        Horde_LoginTasks_Stub_Task::$executed = array();
        $date = getdate();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_First'),
            $date
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testTasksThatRunOnceAreNotExecutedMoreThanOnce()
    {
        Horde_LoginTasks_Stub_Once::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Once'
        );
        $tasks->runTasks();
        $this->assertEquals(
            array('Horde_LoginTasks_Stub_Once'),
            Horde_LoginTasks_Stub_Task::$executed
        );

        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array('Horde_LoginTasks_Stub_Once'),
            true
        );
        $tasks->runTasks();
        $this->assertEquals(
            array(),
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    public function testAllTasksGetRunIfNoTasksRequiresDisplay()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
            )
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

    public function testTheLastTimeOfCompletingTheLoginTasksWillBeStoredOnceAllTasksWereExcecuted()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
            )
        );
        $tasks->runTasks();
        $this->assertTrue(Horde_LoginTasks_Stub_Backend::$lastRun['test'] > time() - 10);
    }

    public function testAllTasksToBeRunBeforeTheFirstTaskRequiringDisplayGetExecutedInABatch()
    {
        Horde_LoginTasks_Stub_Task::$executed = array();
        $tasks = $this->_getLoginTasks(
            array(
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_High',
                'Horde_LoginTasks_Stub_Notice',
            )
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
            )
        );
        $this->assertContains(
            'URL',
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
            )
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
            )
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
            )
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
            )
        );
        $tasks->runTasks(false, 'redirect');
        $tasks->displayTasks();
        $this->assertContains(
            'URL',
            (string) $tasks->runTasks(true)
        );
        $this->assertNull(
            $tasks->runTasks(false)
        );
        $tasks->displayTasks();
        $this->assertEquals(
            'redirect',
            $tasks->runTasks(true)
        );
    }

    public function testConfirmSeriesDisplay()
    {
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
            )
        );
        $this->assertContains(
            'URL',
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
        $this->assertContains(
            'URL',
            (string) $tasks->runTasks(true)
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
                'Horde_LoginTasks_Stub_Notice'
            ),
            $classes
        );
        $this->assertContains(
            'URL',
            (string) $tasks->runTasks(true)
        );
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_Notice'
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
                'Horde_LoginTasks_Stub_ConfirmTwo',
            ),
            $classes
        );
        $_POST['logintasks_confirm_0'] = true;
        $this->assertContains(
            'URL',
            (string) $tasks->runTasks(true)
        );
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_ConfirmTwo',
                'Horde_LoginTasks_Stub_TaskTwo',
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
        $_POST = array();
        $tasklist = $tasks->displayTasks();
        $classes = array();
        foreach ($tasklist as $task) {
            $classes[] = get_class($task);
        }
        asort($classes);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmThree',
            ),
            $classes
        );
        $_POST['logintasks_confirm_0'] = true;
        $this->assertContains(
            'URL',
            (string) $tasks->runTasks(true)
        );
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_ConfirmNo',
                'Horde_LoginTasks_Stub_Confirm',
                'Horde_LoginTasks_Stub_Task',
                'Horde_LoginTasks_Stub_Notice',
                'Horde_LoginTasks_Stub_ConfirmTwo',
                'Horde_LoginTasks_Stub_TaskTwo',
                'Horde_LoginTasks_Stub_ConfirmThree',
            ),
            Horde_LoginTasks_Stub_Task::$executed
        );
        $_POST = array();
        $tasklist = $tasks->displayTasks();
        $classes = array();
        foreach ($tasklist as $task) {
            $classes[] = get_class($task);
        }
        asort($classes);
        $this->assertEquals(
            array(
                'Horde_LoginTasks_Stub_NoticeTwo',
            ),
            $classes
        );
        $this->assertContains(
            'redirect',
            (string) $tasks->runTasks(true)
        );
        $this->assertEquals(
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
            Horde_LoginTasks_Stub_Task::$executed
        );
    }

    private function _getLoginTasks(array $tasks = array(), $last_run = false)
    {
        $last_time = $last_run;
        if ($last_time && !is_bool($last_time)) {
            $last_time = array(
                'test' => mktime(
                              $last_run['hours'],
                              $last_run['minutes'],
                              $last_run['seconds'],
                              $last_run['mon'],
                              $last_run['mday'],
                              $last_run['year']
                          )
            );
        }

        $tasklist = array();
        foreach ($tasks as $val) {
            $tasklist[$val] = 'test';
        }

        return new Horde_LoginTasks(
            new Horde_LoginTasks_Stub_Backend($tasklist, $last_time)
        );
    }

}
