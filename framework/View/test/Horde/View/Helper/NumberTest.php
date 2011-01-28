<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_NumberTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->helper = new Horde_View_Helper_Number(new Horde_View());
    }

    public function testNumberToHumanSize()
    {
        $this->assertEquals('0 Bytes',   $this->helper->numberToHumanSize(0));
        $this->assertEquals('0 Bytes',   $this->helper->numberToHumanSize(0));
        $this->assertEquals('1 Byte',    $this->helper->numberToHumanSize(1));
        $this->assertEquals('3 Bytes',   $this->helper->numberToHumanSize(3.14159265));
        $this->assertEquals('123 Bytes', $this->helper->numberToHumanSize(123.0));
        $this->assertEquals('123 Bytes', $this->helper->numberToHumanSize(123));
        $this->assertEquals('1.2 KB',    $this->helper->numberToHumanSize(1234));
        $this->assertEquals('12.1 KB',   $this->helper->numberToHumanSize(12345));
        $this->assertEquals('1.2 MB',    $this->helper->numberToHumanSize(1234567));
        $this->assertEquals('1.1 GB',    $this->helper->numberToHumanSize(1234567890));
        $this->assertEquals('1.1 TB',    $this->helper->numberToHumanSize(1234567890123));
        $this->assertEquals('444 KB',    $this->helper->numberToHumanSize(444 * 1024));
        $this->assertEquals('1023 MB',   $this->helper->numberToHumanSize(1023 * 1048576));
        $this->assertEquals('3 TB',      $this->helper->numberToHumanSize(3 * 1099511627776));
        $this->assertEquals('1.18 MB',   $this->helper->numberToHumanSize(1234567, 2));
        $this->assertEquals('3 Bytes',   $this->helper->numberToHumanSize(3.14159265, 4));
        $this->assertEquals("123 Bytes", $this->helper->numberToHumanSize("123"));
        $this->assertNull($this->helper->numberToHumanSize('x'));
        $this->assertNull($this->helper->numberToHumanSize(null));
    }

}
