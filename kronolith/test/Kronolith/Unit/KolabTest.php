<?php
/**
 * Test the Kolab driver.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Kolab driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Kronolith_Unit_KolabTest extends Kronolith_TestCase
{
    public function testRecurrence()
    {
        $start = new Horde_Date(259200);
        $end   = new Horde_Date(345600);
        $this->assertEquals(
            1,
            count($this->_getDriverWithRecurrence()->listEvents($start, $end, true))
        );
    }

    public function testRecurrenceException()
    {
        $start = new Horde_Date(86400);
        $end   = new Horde_Date(172800);
        $this->assertEquals(
            array(),
            $this->_getDriverWithRecurrence()->listEvents($start, $end, true)
        );
    }

    private function _getDriverWithRecurrence()
    {
        $driver = $this->getKolabDriver();
        $object = array(
            'uid' => 1,
            'summary' => 'test',
            'start-date' => 0,
            'end-date' => 14400,
            'recurrence' => array(
                'interval' => 1,
                'cycle' => 'daily',
                'range-type' => 'number',
                'range' => 4,
                'exclusion' => array(
                    '1970-01-02',
                    '1970-01-03'
                )
            )
        );
        $this->storage->getData(
            $this->share->get('folder'),
            'event'
        )->create($object);
        return Kronolith::getDriver('Kolab', $this->share->getName());
    }

}
