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

namespace Horde\Backup\Stub;

use ArrayIterator;
use Horde\Backup\Collection;
use Horde\Backup\User;
use Horde\Backup\Users;

/**
 * Horde_Registry_Application stub.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class Application
{
    public $userData = array(
        'john' => array(
            'calendars' => array(
                array(
                    'id' => 'id1',
                    'name' => 'Calendar 1',
                    'desc' => 'The first calendar.'
                ),
                array(
                    'id' => 'id2',
                    'name' => 'Calendar 2',
                    'desc' => 'The second calendar.'
                ),
            ),
            'events' => array(
                array(
                    'id' => 'event1',
                    'name' => 'John\'s Event',
                    'calendar' => 'id1'
                )
            )
        ),
        'jane' => array(
            'events' => array(
                array(
                    'id' => 'event2',
                    'name' => 'Jane\'s Event',
                    'calendar' => 'id1'
                )
            )
        )
    );

    public function backup(array $users = array())
    {
        if (!$users) {
            $users = array_keys($this->userData);
        }
        return new Users(
            new ArrayIterator($users),
            array($this, 'getUserBackup')
        );
    }

    public function getUserBackup($user)
    {
        $backup = new User($user);
        foreach ($this->userData[$user] as $type => $data) {
            $backup->collections[] = new Collection($data, $user, $type);
        }
        return $backup;
    }

    public function restore($user, Collection $data)
    {
    }
}
