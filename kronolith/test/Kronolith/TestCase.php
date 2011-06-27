<?php
/**
 * Basic Kronolith test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Basic Kronolith test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Kronolith_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    protected function getKolabDriver()
    {
        $GLOBALS['registry'] = new Kronolith_Stub_Registry();
        $GLOBALS['injector'] = $this->getInjector();
        $kolab_factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => array(
                    'username' => 'test@example.com',
                    'host' => 'localhost',
                    'port' => 143,
                    'data' => array(
                        'user/test' => array(
                            'permissions' => array('anyone' => 'alrid')
                        )
                    )
                )
            )
        );
        $this->storage = $kolab_factory->create();
        $GLOBALS['injector']->setInstance('Horde_Kolab_Storage', $this->storage);
        $GLOBALS['injector']->setInstance('Horde_History', new Horde_History_Mock('test@example.com'));
        $GLOBALS['injector']->setInstance('Horde_Group', new Horde_Group_Mock());
        $GLOBALS['prefs'] = new Horde_Prefs('kronolith', new Horde_Prefs_Storage_Null('test'));
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $GLOBALS['kronolith_shares'] = new Horde_Share_Kolab(
            'kronolith', 'test@example.com', new Horde_Perms_Null(), new Horde_Group_Mock()
        );
        $GLOBALS['kronolith_shares']->setStorage($this->storage);
        $this->share = $GLOBALS['kronolith_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Calendar of Tester"
        );
        $GLOBALS['kronolith_shares']->addShare($this->share);
        $this->other_share = $GLOBALS['kronolith_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Other Notepad of Tester"
        );
        $GLOBALS['kronolith_shares']->addShare($this->other_share);
        $GLOBALS['all_calendars'] = array();
        foreach (Kronolith::listInternalCalendars() as $id => $calendar) {
            $GLOBALS['all_calendars'][$id] = new Kronolith_Calendar_Internal(array('share' => $calendar));
        }
        return Kronolith::getDriver('Kolab', $this->share->getName());
    }
}