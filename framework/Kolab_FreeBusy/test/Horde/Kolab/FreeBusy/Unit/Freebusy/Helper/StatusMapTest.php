<?php
/**
 * Test the status mappers.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the status mappers.
 *
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Freebusy_Helper_StatusMapTest
extends PHPUnit_Framework_TestCase
{
    public function testFreeIsFreeWithDefault()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Default();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_FREE,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_FREE)
        );
    }

    public function testCancelledIsFreeWithDefault()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Default();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_FREE,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_CANCELLED)
        );
    }

    public function testBusyIsBusyWithDefault()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Default();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_BUSY,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_BUSY)
        );
    }

    public function testTentativeIsBusyWithDefault()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Default();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_BUSY,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_TENTATIVE)
        );
    }

    public function testFreeIsFreeWithConfig()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Config();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_FREE,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_FREE)
        );
    }

    public function testCancelledIsFreeWithConfig()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Config();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_FREE,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_CANCELLED)
        );
    }

    public function testBusyIsBusyWithConfig()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Config();
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_BUSY,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_BUSY)
        );
    }

    public function testTentativeIsBusyTentativeWithConfig()
    {
        $mapper = new Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Config(
            array(
                Horde_Kolab_FreeBusy_Object_Event::STATUS_TENTATIVE =>
                Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_BUSY_TENTATIVE
            )
        );
        $this->assertEquals(
            Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap::STATUS_BUSY_TENTATIVE,
            $mapper->map(Horde_Kolab_FreeBusy_Object_Event::STATUS_TENTATIVE)
        );
    }
}