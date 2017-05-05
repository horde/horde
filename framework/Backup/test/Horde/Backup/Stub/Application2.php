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

use Horde\Backup\Stub\Application;

/**
 * Horde_Registry_Application stub for a 2nd application.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class Application2 extends Application
{
    public $userData = array(
        'jane' => array(
            'addressbooks' => array(
                array(
                    'id' => 'id1',
                    'name' => 'Personal Address Book',
                )
            ),
            'contacts' => array(
                array(
                    'id' => 'contact1',
                    'name' => 'Contact Name',
                    'address' => array(
                        'street' => 'Mainstreet 1',
                        'city' => 'Capital City'
                    ),
                    'phone' => 123456,
                    'addressbook' => 'id1'
                )
            )
        )
    );
}