<?php
/**
 * Test the vEvent iCal handling.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Itip
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the vEvent iCal handling.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Unit_Event_VeventTest
extends PHPUnit_Framework_TestCase
{
    public function testGetMethodReturnsMethod()
    {
        $inv = Horde_Icalendar::newComponent('VEVENT', false);
        $inv->setAttribute('METHOD', 'TEST');
        $vevent = new Horde_Itip_Event_Vevent($inv);
        $this->assertEquals('TEST', $vevent->getMethod());
    }

    public function testGetMethodReturnsDefaultMethod()
    {
        $inv = Horde_Icalendar::newComponent('VEVENT', false);
        $vevent = new Horde_Itip_Event_Vevent($inv);
        $this->assertEquals('REQUEST', $vevent->getMethod());
    }

}
