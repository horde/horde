<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class Horde_Date_SpanTest extends PHPUnit_Framework_TestCase
{
    public function testWidth()
    {
        $s = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertEquals(60 * 60 * 24, $s->width());
    }

    public function testIncludes()
    {
        $s = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertTrue($s->includes(new Horde_Date('2006-08-16 12:00:00')));
        $this->assertFalse($s->includes(new Horde_Date('2006-08-15 00:00:00')));
        $this->assertFalse($s->includes(new Horde_Date('2006-08-18 00:00:00')));
    }

    public function testSpanMath()
    {
        $s = new Horde_Date_Span(new Horde_Date(1), new Horde_Date(2));
        $this->assertEquals(2, $s->add(1)->begin->timestamp());
        $this->assertEquals(3, $s->add(1)->end->timestamp());
        $this->assertEquals(0, $s->sub(1)->begin->timestamp());
        $this->assertEquals(1, $s->sub(1)->end->timestamp());
    }

}
