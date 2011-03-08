<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class Horde_Date_Repeater_MonthNameTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $mays = new Horde_Date_Repeater_MonthName('may');
        $mays->now = $this->now;

        $nextMay = $mays->next('future');
        $this->assertEquals('2007-05-01 00:00:00', (string)$nextMay->begin);
        $this->assertEquals('2007-06-01 00:00:00', (string)$nextMay->end);

        $nextNextMay = $mays->next('future');
        $this->assertEquals('2008-05-01 00:00:00', (string)$nextNextMay->begin);
        $this->assertEquals('2008-06-01 00:00:00', (string)$nextNextMay->end);

        $decembers = new Horde_Date_Repeater_MonthName('december');
        $decembers->now = $this->now;

        $nextDecember = $decembers->next('future');
        $this->assertEquals('2006-12-01 00:00:00', (string)$nextDecember->begin);
        $this->assertEquals('2007-01-01 00:00:00', (string)$nextDecember->end);
    }

    public function testNextPast()
    {
        $mays = new Horde_Date_Repeater_MonthName('may');
        $mays->now = $this->now;

        $this->assertEquals('2006-05-01 00:00:00', (string)$mays->next('past')->begin);
        $this->assertEquals('2005-05-01 00:00:00', (string)$mays->next('past')->begin);
    }

    public function testThis()
    {
        $octobers = new Horde_Date_Repeater_MonthName('october');
        $octobers->now = $this->now;

        $thisOctober = $octobers->this('future');
        $this->assertEquals('2006-10-01 00:00:00', (string)$thisOctober->begin);
        $this->assertEquals('2006-11-01 00:00:00', (string)$thisOctober->end);

        $aprils = new Horde_Date_Repeater_MonthName('april');
        $aprils->now = $this->now;

        $thisApril = $aprils->this('past');
        $this->assertEquals('2006-04-01 00:00:00', (string)$thisApril->begin);
        $this->assertEquals('2006-05-01 00:00:00', (string)$thisApril->end);
    }

}
