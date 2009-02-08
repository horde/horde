<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_Repeater_TimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $t = new Horde_Date_Repeater_Time('4:00');
        $t->now = $this->now;

        $this->assertEquals('2006-08-16 16:00:00', (string)$t->next('future')->begin);
        $this->assertEquals('2006-08-17 04:00:00', (string)$t->next('future')->begin);

        $t = new Horde_Date_Repeater_Time('13:00');
        $t->now = $this->now;

        $this->assertEquals('2006-08-17 13:00:00', (string)$t->next('future')->begin);
        $this->assertEquals('2006-08-18 13:00:00', (string)$t->next('future')->begin);

        $t = new Horde_Date_Repeater_Time('0400');
        $t->now = $this->now;

        $this->assertEquals('2006-08-17 04:00:00', (string)$t->next('future')->begin);
        $this->assertEquals('2006-08-18 04:00:00', (string)$t->next('future')->begin);
    }

    public function testNextPast()
    {
        $t = new Horde_Date_Repeater_Time('4:00');
        $t->now = $this->now;
        $this->assertEquals('2006-08-16 04:00:00', (string)$t->next('past')->begin);
        $this->assertEquals('2006-08-15 16:00:00', (string)$t->next('past')->begin);

        $t = new Horde_Date_Repeater_Time('13:00');
        $t->now = $this->now;
        $this->assertEquals('2006-08-16 13:00:00', (string)$t->next('past')->begin);
        $this->assertEquals('2006-08-15 13:00:00', (string)$t->next('past')->begin);
    }

    public function testType()
    {
        $t = new Horde_Date_Repeater_Time('4');
        $this->assertEquals(14400, $t->type);

        $t = new Horde_Date_Repeater_Time('14');
        $this->assertEquals(50400, $t->type);

        $t = new Horde_Date_Repeater_Time('4:00');
        $this->assertEquals(14400, $t->type);

        $t = new Horde_Date_Repeater_Time('4:30');
        $this->assertEquals(16200, $t->type);

        $t = new Horde_Date_Repeater_Time('1400');
        $this->assertEquals(50400, $t->type);

        $t = new Horde_Date_Repeater_Time('0400');
        $this->assertEquals(14400, $t->type);

        $t = new Horde_Date_Repeater_Time('04');
        $this->assertEquals(14400, $t->type);

        $t = new Horde_Date_Repeater_Time('400');
        $this->assertEquals(14400, $t->type);
    }

}
