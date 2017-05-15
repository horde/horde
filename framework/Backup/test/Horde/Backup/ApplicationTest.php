<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */

namespace Horde\Backup;

use Horde_Test_Case as TestCase;
use Horde\Backup\Stub;

/**
 * Testing the Horde_Registry_Application API.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class ApplicationTest extends TestCase
{
    public function testBackupSingleUser()
    {
        $application = new Stub\Application();
        $users = $application->backup(array('john'));
        $this->assertInstanceOf('\Horde\Backup\Users', $users);
        $this->assertCount(1, $users);
        $user = $users->current();
        $this->assertInstanceOf('\Horde\Backup\User', $user);
        $this->assertEquals('john', $user->user);
        $this->assertInternalType('array', $user->collections);
        $this->assertCount(2, $user->collections);
        $this->assertInstanceOf('\Horde\Backup\Collection', $user->collections[0]);
        $this->assertInstanceOf('Iterator', $user->collections[0]);
        $this->assertEquals('calendars', $user->collections[0]->getType());
        foreach ($user->collections[0] as $key => $data) {
            $this->assertEquals(
                $application->userData['john']['calendars'][$key],
                $data
            );
        }
    }

    public function testBackupMultipleUsers()
    {
        $application = new Stub\Application();
        $users = $application->backup();
        $this->assertInstanceOf('\Horde\Backup\Users', $users);
        $this->assertCount(2, $users);
        $users->next();
        $user = $users->current();
        $this->assertInstanceOf('\Horde\Backup\User', $user);
        $this->assertEquals('jane', $user->user);
        $this->assertInternalType('array', $user->collections);
        $this->assertCount(1, $user->collections);
        $this->assertInstanceOf('\Horde\Backup\Collection', $user->collections[0]);
        $this->assertInstanceOf('Iterator', $user->collections[0]);
        $this->assertEquals('events', $user->collections[0]->getType());
        foreach ($user->collections[0] as $key => $data) {
            $this->assertEquals(
                $application->userData['jane']['events'][$key],
                $data
            );
        }
    }
}
