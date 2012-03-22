<?php
/**
 * Test the OWA parser.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @author     Mathieu Parent <math.parent@gmail.com>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the OWA parser.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @author     Mathieu Parent <math.parent@gmail.com>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Freebusy_Helper_OwaTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('America/Los_Angeles');
    }

    public function testStringConstruction()
    {
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa(
            file_get_contents(
                __DIR__ . '/../../../fixtures/owa_freebusy.xml'
            )
        );
        $this->assertContains('a:response', (string) $owa);

    }

    public function testStreamConstruction()
    {
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa(
            fopen(
                __DIR__ . '/../../../fixtures/owa_freebusy.xml', 'r'
            )
        );
        $this->assertContains('a:response', (string) $owa);

    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testInvalidConstruction()
    {
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa('NOPE');
    }


    public function testConversionOne()
    {
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa(
            fopen(
                __DIR__ . '/../../../fixtures/owa_freebusy.xml', 'r'
            )
        );
        $result = $owa->convert(
            new Horde_Date('2009-09-25T00:00:00-07:00'),
            new Horde_Date('2009-09-26T00:00:00-07:00'),
            30
        );
        $this->assertEquals(
            array (
                0 => '2009-09-25 13:00:00 - 2009-09-25 14:00:00: busy',
                1 => '2009-09-25 16:00:00 - 2009-09-25 19:00:00: busy'
            ),
            $this->_readable($result['user1@example.com'])
        );
    }

    public function testConversionTwo()
    {
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa(
            fopen(
                __DIR__ . '/../../../fixtures/owa_freebusy.xml', 'r'
            )
        );
        $result = $owa->convert(
            new Horde_Date('2009-09-25T00:00:00-07:00'),
            new Horde_Date('2009-09-26T00:00:00-07:00'),
            30
        );
        $this->assertEquals(
            array (
                0 => '2009-09-25 03:00:00 - 2009-09-25 05:00:00: busy',
                1 => '2009-09-25 12:30:00 - 2009-09-25 14:30:00: tentative'
            ),
            $this->_readable($result['user2@example.com'])
        );
    }

    private function _readable($result)
    {
        $strings = array();
        foreach ($result as $element) {
            $strings[] = sprintf(
                '%s - %s: %s',
                $element['start-date'],
                $element['end-date'],
                $element['show-time-as']
            );
        }
        return $strings;
    }
}