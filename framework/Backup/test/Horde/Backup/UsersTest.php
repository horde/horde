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

use ArrayIterator;
use Horde_Test_Case as TestCase;
use Horde\Backup\User;
use Horde\Backup\Users;

/**
 * Testing \Horde\Backup\Users
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class UsersTest extends TestCase
{
    public function testIterator()
    {
        $callback = function($user) {
            return new User($user);
        };
        $users = new Users(
            new ArrayIterator(array('john', 'jane')), $callback
        );
        $this->assertInstanceOf('Iterator', $users);
        $this->assertTrue($users->valid());
        $this->assertEquals('john', $users->key());
        $user = $users->current();
        $this->assertInstanceOf('\Horde\Backup\User', $user);
        $this->assertEquals('john', $user->user);
        $this->assertCount(2, $users);
        $count = 0;
        foreach ($users as $name => $user) {
            $count++;
            $this->assertInternalType('string', $name);
            $this->assertInstanceOf('\Horde\Backup\User', $user);
        }
        $this->assertEquals(2, $count);
        $this->assertInstanceOf('\Horde\Backup\User', $user);
        $this->assertEquals('jane', $user->user);
        $this->assertEquals('jane', $name);
    }
}
