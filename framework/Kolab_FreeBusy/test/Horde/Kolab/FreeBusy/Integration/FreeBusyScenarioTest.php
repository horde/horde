<?php
/**
 * Checks for the Kolab Free/Busy system.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/FreeBusy.php';

/**
 * Checks for the Kolab Free/Busy system.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_FreeBusy_FreeBusyScenarioTest extends Horde_Kolab_Test_FreeBusy
{
    /**
     * Test triggering a calendar folder.
     *
     * @scenario
     *
     * @return NULL
     */
    public function triggering()
    {
        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', 'Calendar')
            ->and('triggering the folder', 'wrobel@example.org/Calendar')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the result should be an object of type', 'Horde_Kolab_FreeBusy_View_vfb');
    }

    /**
     * Test fetching free/busy data.
     *
     * @scenario
     *
     * @return NULL
     */
    public function fetching()
    {
        $now = time();
        $event = array(
            'uid' => 1,
            'summary' => 'hello',
            'start-date' => $now,
            'end-date' => $now + 120,
        );

        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', 'Calendar')
            ->and('adding an event to a folder', $event, 'INBOX/Calendar')
            ->and('triggering the folder', 'wrobel@example.org/Calendar')
            ->and('fetching the free/busy information for', 'wrobel@example.org')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the fetch result should contain a free/busy time with summary', 'hello');
    }

    /**
     * Test fetching free/busy data as a foreign user should not contain
     * extended information.
     *
     * @scenario
     *
     * @return NULL
     */
    public function fetchingAsForeignUser()
    {
        $now = time();
        $event = array(
            'uid' => 1,
            'summary' => 'hello',
            'start-date' => $now,
            'end-date' => $now + 120,
        );

        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', 'Calendar')
            ->and('adding an event to a folder', $event, 'INBOX/Calendar')
            ->and('triggering the folder', 'wrobel@example.org/Calendar')
            ->and('logging in as a user with a password', 'test', 'test')
            ->and('fetching the free/busy information for', 'wrobel@example.org')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the fetch result should not contain a free/busy time with summary', 'hello');
    }

    /**
     * Test fetching free/busy data as a foreign user in group with read access
     * should contain extended information.
     *
     * @scenario
     *
     * @return NULL
     */
    public function fetchingAsForeignUserInSameGroup()
    {
        $now = time();
        $event = array(
            'uid' => 1,
            'summary' => 'hello',
            'start-date' => $now,
            'end-date' => $now + 120,
        );

        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', 'Calendar')
            ->and('allow a group full access to a folder', 'group@example.org', 'INBOX/Calendar')
            ->and('adding an event to a folder', $event, 'INBOX/Calendar')
            ->and('triggering the folder', 'wrobel@example.org/Calendar')
            ->and('logging in as a user with a password', 'test', 'test')
            ->and('fetching the free/busy information for', 'wrobel@example.org')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the fetch result should contain a free/busy time with summary', 'hello');
    }
}
